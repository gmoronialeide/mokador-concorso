<?php

namespace Tests\Unit\Services\Ocr;

use App\Models\Play;
use App\Models\Store;
use App\Services\Ocr\ExtractedDocument;
use App\Services\Ocr\PlayVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PlayVerifierVatLookupTest extends TestCase
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

    public function test_vat_mismatch_with_unique_active_match_in_db_adds_lookup_note(): void
    {
        $userStore = Store::factory()->create([
            'vat_number' => '01111111111',
            'sign_name' => 'STORE UTENTE',
            'name' => 'Store Utente SRL',
        ]);
        $matchedStore = Store::factory()->create([
            'vat_number' => '02222222222',
            'sign_name' => 'STORE MATCH',
            'name' => 'Store Match SRL',
            'is_active' => true,
        ]);

        $play = $this->makePlayFor($userStore);
        $doc = $this->sampleDoc(['merchantVat' => '02222222222']);

        $result = $this->verifier->verify($play, $doc);

        $this->assertContains('non torna punto vendita', $result->notes);
        $this->assertContains(
            "P.IVA scontrino corrisponde a store: STORE MATCH (#{$matchedStore->id})",
            $result->notes,
        );
    }

    public function test_vat_mismatch_with_unique_inactive_match_appends_inactive_suffix(): void
    {
        $userStore = Store::factory()->create(['vat_number' => '01111111111']);
        $matchedStore = Store::factory()->create([
            'vat_number' => '02222222222',
            'sign_name' => 'STORE INATTIVO',
            'name' => 'Store Inattivo SRL',
            'is_active' => false,
        ]);

        $play = $this->makePlayFor($userStore);
        $doc = $this->sampleDoc(['merchantVat' => '02222222222']);

        $result = $this->verifier->verify($play, $doc);

        $this->assertContains(
            "P.IVA scontrino corrisponde a store: STORE INATTIVO (#{$matchedStore->id}) [inattivo]",
            $result->notes,
        );
    }

    public function test_vat_mismatch_with_multiple_matches_lists_all(): void
    {
        $userStore = Store::factory()->create(['vat_number' => '01111111111']);
        $a = Store::factory()->create([
            'vat_number' => '02222222222',
            'sign_name' => 'STORE A',
            'is_active' => true,
        ]);
        $b = Store::factory()->create([
            'vat_number' => '02222222222',
            'sign_name' => 'STORE B',
            'is_active' => false,
        ]);
        $c = Store::factory()->create([
            'vat_number' => '02222222222',
            'sign_name' => 'STORE C',
            'is_active' => true,
        ]);

        $play = $this->makePlayFor($userStore);
        $doc = $this->sampleDoc(['merchantVat' => '02222222222']);

        $result = $this->verifier->verify($play, $doc);

        $expected = "P.IVA scontrino corrisponde a 3 store: STORE A (#{$a->id}), STORE B (#{$b->id}) [inattivo], STORE C (#{$c->id})";
        $this->assertContains($expected, $result->notes);
    }

    public function test_vat_mismatch_with_no_match_in_db_adds_not_in_db_note(): void
    {
        $userStore = Store::factory()->create(['vat_number' => '01111111111']);
        Store::factory()->create(['vat_number' => '03333333333']);

        $play = $this->makePlayFor($userStore);
        $doc = $this->sampleDoc(['merchantVat' => '09999999999']);

        $result = $this->verifier->verify($play, $doc);

        $this->assertContains('non torna punto vendita', $result->notes);
        $this->assertContains('P.IVA scontrino non in DB stores', $result->notes);
    }

    public function test_no_vat_in_doc_no_lookup_note(): void
    {
        $userStore = Store::factory()->create([
            'vat_number' => '',
            'sign_name' => 'MOKADOR CAFFE',
            'city' => 'CERVIA',
        ]);
        Store::factory()->create(['vat_number' => '02222222222']);

        $play = $this->makePlayFor($userStore);
        $doc = $this->sampleDoc([
            'merchantName' => 'BAR DIVERSO',
            'merchantAddress' => 'VIA ALTROVE 1 00100 ROMA RM',
            'merchantVat' => null,
        ]);

        $result = $this->verifier->verify($play, $doc);

        $this->assertContains('non torna punto vendita', $result->notes);
        foreach ($result->notes as $note) {
            $this->assertStringNotContainsString('P.IVA scontrino', $note);
        }
    }

    public function test_user_store_without_vat_and_name_city_fail_triggers_lookup(): void
    {
        $userStore = Store::factory()->create([
            'vat_number' => '',
            'sign_name' => 'MOKADOR CAFFE',
            'city' => 'CERVIA',
        ]);
        $matched = Store::factory()->create([
            'vat_number' => '02222222222',
            'sign_name' => 'STORE MATCH',
            'is_active' => true,
        ]);

        $play = $this->makePlayFor($userStore);
        $doc = $this->sampleDoc([
            'merchantName' => 'BAR DIVERSO',
            'merchantAddress' => 'VIA ALTROVE 1 00100 ROMA RM',
            'merchantVat' => '02222222222',
        ]);

        $result = $this->verifier->verify($play, $doc);

        $this->assertContains(
            "P.IVA scontrino corrisponde a store: STORE MATCH (#{$matched->id})",
            $result->notes,
        );
    }

    public function test_vat_match_with_user_store_returns_ok_no_lookup(): void
    {
        $userStore = Store::factory()->create(['vat_number' => '02222222222']);
        Store::factory()->create(['vat_number' => '02222222222']);

        $play = $this->makePlayFor($userStore);
        $doc = $this->sampleDoc(['merchantVat' => '02222222222']);

        $result = $this->verifier->verify($play, $doc);

        $this->assertSame([], $result->notes);
    }

    #[DataProvider('vatNormalizationProvider')]
    public function test_vat_normalization_matches_store(string $ocrVat): void
    {
        $userStore = Store::factory()->create(['vat_number' => '09999999999']);
        $matched = Store::factory()->create([
            'vat_number' => '01234567890',
            'sign_name' => 'STORE NORM',
            'is_active' => true,
        ]);

        $play = $this->makePlayFor($userStore);
        $doc = $this->sampleDoc(['merchantVat' => $ocrVat]);

        $result = $this->verifier->verify($play, $doc);

        $this->assertContains(
            "P.IVA scontrino corrisponde a store: STORE NORM (#{$matched->id})",
            $result->notes,
        );
    }

    /** @return array<string, array{0: string}> */
    public static function vatNormalizationProvider(): array
    {
        return [
            'with IT prefix' => ['IT01234567890'],
            'plain digits' => ['01234567890'],
            'with dots' => ['01.234.567.890'],
            'with label' => ['P.IVA: 01234567890'],
        ];
    }

    private function makePlayFor(Store $store): Play
    {
        $play = Play::factory()->make([
            'user_id' => 1,
            'receipt_image' => 'receipts/x.jpg',
            'store_id' => $store->id,
            'store_code' => $store->code,
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
