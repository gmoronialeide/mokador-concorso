<?php

namespace Tests\Unit\Services\Ocr;

use App\Enums\PlayStatus;
use App\Enums\VerificationType;
use App\Models\Play;
use App\Models\Store;
use App\Services\Ocr\ExtractedDocument;
use App\Services\Ocr\PlayVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayVerifierTest extends TestCase
{
    use RefreshDatabase;

    private PlayVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verifier = new PlayVerifier();

        config()->set('app.concorso_start_date', '2026-04-01');
        config()->set('app.concorso_end_date', '2026-04-28');
    }

    public function test_missing_receipt_image_returns_banned(): void
    {
        $play = Play::factory()->make(['user_id' => 1, 'receipt_image' => null]);

        $result = $this->verifier->verify($play, $this->sampleDoc());

        $this->assertSame(PlayStatus::Banned, $result->status);
        $this->assertSame(VerificationType::Auto, $result->type);
        $this->assertSame(['scontrino mancante'], $result->notes);
    }

    public function test_null_doc_returns_banned(): void
    {
        $store = $this->sampleStore();
        $play = $this->samplePlay($store);

        $result = $this->verifier->verify($play, null);

        $this->assertSame(PlayStatus::Banned, $result->status);
        $this->assertSame(VerificationType::Auto, $result->type);
        $this->assertSame(['scontrino non riconosciuto'], $result->notes);
    }

    public function test_happy_path_vat_match_validates(): void
    {
        $store = $this->sampleStore(['vat_number' => '01234567890']);
        $play = $this->samplePlay($store);

        $doc = $this->sampleDoc([
            'merchantVat' => '01234567890',
            'merchantConfidence' => 0.95,
        ]);

        $result = $this->verifier->verify($play, $doc);

        $this->assertSame(PlayStatus::Validated, $result->status);
        $this->assertSame([], $result->notes);
    }

    public function test_happy_path_name_fuzzy_validates(): void
    {
        $store = $this->sampleStore([
            'vat_number' => null,
            'cap' => null,
            'city' => null,
            'sign_name' => 'MOKADOR CAFFE',
            'name' => 'Mokador Caffe SRL',
        ]);
        $play = $this->samplePlay($store);

        $doc = $this->sampleDoc([
            'merchantName' => 'MOKADOR CAFFE',
            'merchantAddress' => 'VIA ROMA 12 48015 CERVIA RA',
            'merchantConfidence' => 0.95,
        ]);

        $result = $this->verifier->verify($play, $doc);

        $this->assertSame(PlayStatus::Validated, $result->status);
        $this->assertSame([], $result->notes);
    }

    public function test_date_out_of_range_returns_banned(): void
    {
        $store = $this->sampleStore();
        $play = $this->samplePlay($store);

        $doc = $this->sampleDoc([
            'date' => '2025-01-01',
            'merchantVat' => $store->vat_number,
            'merchantConfidence' => 0.95,
        ]);

        $result = $this->verifier->verify($play, $doc);

        $this->assertSame(PlayStatus::Banned, $result->status);
        $this->assertSame(VerificationType::Auto, $result->type);
        $this->assertSame(['data scontrino fuori concorso (2025-01-01)'], $result->notes);
    }

    public function test_date_after_end_returns_banned(): void
    {
        $store = $this->sampleStore();
        $play = $this->samplePlay($store);

        $doc = $this->sampleDoc([
            'date' => '2026-04-29',
            'merchantVat' => $store->vat_number,
            'merchantConfidence' => 0.95,
        ]);

        $result = $this->verifier->verify($play, $doc);

        $this->assertSame(PlayStatus::Banned, $result->status);
        $this->assertSame(['data scontrino fuori concorso (2026-04-29)'], $result->notes);
    }

    public function test_date_out_of_range_skips_other_checks(): void
    {
        $store = $this->sampleStore(['vat_number' => '01234567890']);
        $play = $this->samplePlay($store);

        $doc = $this->sampleDoc([
            'date' => '2025-01-01',
            'total' => 0.10,
            'merchantVat' => '99999999999',
            'merchantConfidence' => 0.40,
        ]);

        $result = $this->verifier->verify($play, $doc);

        $this->assertSame(PlayStatus::Banned, $result->status);
        $this->assertCount(1, $result->notes);
        $this->assertSame('data scontrino fuori concorso (2025-01-01)', $result->notes[0]);
    }

    public function test_null_date_stays_pending(): void
    {
        $store = $this->sampleStore();
        $play = $this->samplePlay($store);

        $doc = $this->sampleDoc([
            'date' => null,
            'merchantVat' => $store->vat_number,
            'merchantConfidence' => 0.95,
        ]);

        $result = $this->verifier->verify($play, $doc);

        $this->assertSame(PlayStatus::Pending, $result->status);
        $this->assertContains('non torna data scontrino (N/D)', $result->notes);
    }

    public function test_total_below_one_adds_note(): void
    {
        $store = $this->sampleStore();
        $play = $this->samplePlay($store);

        $doc = $this->sampleDoc([
            'total' => 0.50,
            'merchantVat' => $store->vat_number,
            'merchantConfidence' => 0.95,
        ]);

        $result = $this->verifier->verify($play, $doc);

        $this->assertContains('non torna importo (0,50€)', $result->notes);
    }

    public function test_name_and_city_mismatch_adds_merchant_note(): void
    {
        $store = $this->sampleStore([
            'vat_number' => null,
            'city' => 'CERVIA',
            'sign_name' => 'MOKADOR CAFFE',
            'name' => 'Mokador Caffe SRL',
        ]);
        $play = $this->samplePlay($store);

        $doc = $this->sampleDoc([
            'merchantName' => 'BAR PINCO PALLINO',
            'merchantAddress' => 'VIA ALTROVE 99 00100 ROMA RM',
            'merchantConfidence' => 0.95,
        ]);

        $result = $this->verifier->verify($play, $doc);

        $this->assertContains('non torna punto vendita', $result->notes);
    }

    public function test_city_match_validates_when_name_fails(): void
    {
        $store = $this->sampleStore([
            'vat_number' => null,
            'city' => 'CERVIA',
            'sign_name' => 'MOKADOR CAFFE',
            'name' => 'Mokador Caffe SRL',
        ]);
        $play = $this->samplePlay($store);

        $doc = $this->sampleDoc([
            'merchantName' => 'BAR PINCO PALLINO',
            'merchantAddress' => 'VIA ROMA 12 48015 CERVIA RA',
            'merchantConfidence' => 0.95,
        ]);

        $result = $this->verifier->verify($play, $doc);

        $this->assertSame(PlayStatus::Validated, $result->status);
        $this->assertSame([], $result->notes);
    }

    public function test_low_confidence_adds_confidence_note(): void
    {
        $store = $this->sampleStore(['vat_number' => '01234567890']);
        $play = $this->samplePlay($store);

        $doc = $this->sampleDoc([
            'merchantVat' => '01234567890',
            'merchantConfidence' => 0.55,
        ]);

        $result = $this->verifier->verify($play, $doc);

        $this->assertContains('verifica manuale merchant (confidence bassa)', $result->notes);
    }

    public function test_multiple_fails_concatenate_with_newline(): void
    {
        $store = $this->sampleStore([
            'vat_number' => '01234567890',
        ]);
        $play = $this->samplePlay($store);

        $doc = $this->sampleDoc([
            'date' => '2026-04-15',
            'total' => 0.10,
            'merchantVat' => '99999999999',
            'merchantConfidence' => 0.95,
        ]);

        $result = $this->verifier->verify($play, $doc);

        $this->assertSame(PlayStatus::Pending, $result->status);
        $this->assertCount(3, $result->notes);
        $this->assertStringContainsString('controllo automatico: non torna importo', $result->noteString());
        $this->assertStringContainsString("\ncontrollo automatico: non torna punto vendita", $result->noteString());
        $this->assertStringContainsString("\ncontrollo automatico: P.IVA scontrino non in DB stores", $result->noteString());
    }

    private function sampleStore(array $overrides = []): Store
    {
        $store = Store::factory()->make(array_merge([
            'vat_number' => '01234567890',
            'cap' => '48015',
            'city' => 'CERVIA',
            'sign_name' => 'MOKADOR CAFFE',
            'name' => 'Mokador Caffe SRL',
        ], $overrides));
        $store->id = 1;

        return $store;
    }

    private function samplePlay(Store $store): Play
    {
        $play = Play::factory()->make([
            'user_id' => 1,
            'receipt_image' => 'receipts/x.jpg',
            'store_id' => $store->id,
            'store_code' => $store->code ?? 'STORE0001',
        ]);
        $play->setRelation('store', $store);

        return $play;
    }

    private function sampleDoc(array $overrides = []): ExtractedDocument
    {
        $defaults = [
            'type' => 'receipt',
            'merchantName' => 'MOKADOR CAFFE',
            'merchantAddress' => 'VIA ROMA 12 48015 CERVIA RA',
            'merchantVat' => null,
            'merchantConfidence' => 0.95,
            'date' => '2026-04-15',
            'total' => 5.40,
            'items' => [],
            'raw' => [],
        ];

        $values = array_merge($defaults, $overrides);

        return new ExtractedDocument(
            type: $values['type'],
            merchantName: $values['merchantName'],
            merchantAddress: $values['merchantAddress'],
            merchantVat: $values['merchantVat'],
            merchantConfidence: $values['merchantConfidence'],
            date: $values['date'],
            total: $values['total'],
            items: $values['items'],
            raw: $values['raw'],
        );
    }
}
