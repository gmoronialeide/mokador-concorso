<?php

namespace Tests\Feature\Jobs;

use App\Enums\PlayStatus;
use App\Enums\VerificationType;
use App\Jobs\VerifyPlayAutomatically;
use App\Models\Play;
use App\Models\Store;
use App\Models\User;
use App\Services\Ocr\Exceptions\OcrApiException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerifyPlayAutomaticallyJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.concorso_start_date', '2026-04-01');
        config()->set('app.concorso_end_date', '2026-04-28');
        config()->set('services.azure_docintel.endpoint', 'https://fake.cognitiveservices.azure.com');
        config()->set('services.azure_docintel.key', 'test-key');
        config()->set('services.azure_docintel.api_version', '2024-11-30');
    }

    public function test_missing_receipt_image_bans_play(): void
    {
        $user = User::factory()->create();
        $play = Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => '',
            'played_at' => now(),
        ]);

        (new VerifyPlayAutomatically($play))->handle(
            app(\App\Services\Ocr\AzureDocumentIntelligence::class),
            app(\App\Services\Ocr\ReceiptExtractor::class),
            app(\App\Services\Ocr\PlayVerifier::class),
        );

        $play->refresh();
        $this->assertSame(PlayStatus::Banned, $play->status);
        $this->assertSame(VerificationType::Auto, $play->verification_type);
        $this->assertStringContainsString('scontrino mancante', (string) $play->notes);
    }

    public function test_ocr_empty_leaves_pending_with_note(): void
    {
        $this->fakeReceipt($user, $store, $play);

        Http::fake([
            '*:analyze*' => Http::response('', 202, ['Operation-Location' => 'https://fake.cognitiveservices.azure.com/op/1']),
            'https://fake.cognitiveservices.azure.com/op/1' => Http::sequence()
                ->push($this->emptyDocs(), 200)
                ->push($this->emptyDocs(), 200),
        ]);

        (new VerifyPlayAutomatically($play))->handle(
            app(\App\Services\Ocr\AzureDocumentIntelligence::class),
            app(\App\Services\Ocr\ReceiptExtractor::class),
            app(\App\Services\Ocr\PlayVerifier::class),
        );

        $play->refresh();
        $this->assertSame(PlayStatus::Pending, $play->status);
        $this->assertSame(VerificationType::Auto, $play->verification_type);
        $this->assertStringContainsString('OCR non riuscito', (string) $play->notes);
    }

    public function test_valid_receipt_validates_play(): void
    {
        $this->fakeReceipt($user, $store, $play);

        Http::fake([
            '*:analyze*' => Http::response('', 202, ['Operation-Location' => 'https://fake.cognitiveservices.azure.com/op/2']),
            'https://fake.cognitiveservices.azure.com/op/2' => Http::response(
                json_decode(file_get_contents(base_path('tests/fixtures/azure/receipt_valid.json')), true),
                200,
            ),
        ]);

        (new VerifyPlayAutomatically($play))->handle(
            app(\App\Services\Ocr\AzureDocumentIntelligence::class),
            app(\App\Services\Ocr\ReceiptExtractor::class),
            app(\App\Services\Ocr\PlayVerifier::class),
        );

        $play->refresh();
        $this->assertSame(PlayStatus::Validated, $play->status);
        $this->assertSame(VerificationType::Auto, $play->verification_type);
        $this->assertNull($play->notes);
        $this->assertNotEmpty($play->ocr_raw);
    }

    public function test_job_throws_on_api_exception(): void
    {
        $this->fakeReceipt($user, $store, $play);

        Http::fake([
            '*:analyze*' => Http::response('boom', 500),
        ]);

        $this->expectException(OcrApiException::class);

        (new VerifyPlayAutomatically($play))->handle(
            app(\App\Services\Ocr\AzureDocumentIntelligence::class),
            app(\App\Services\Ocr\ReceiptExtractor::class),
            app(\App\Services\Ocr\PlayVerifier::class),
        );
    }

    private function fakeReceipt(?User &$user, ?Store &$store, ?Play &$play): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('receipts/test.jpg', 'FAKE-BYTES');

        $user = User::factory()->create();
        $store = Store::factory()->create([
            'vat_number' => '01234567890',
            'cap' => '48015',
            'city' => 'CERVIA',
            'sign_name' => 'MOKADOR CAFFE',
        ]);
        $play = Play::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'store_code' => $store->code,
            'receipt_image' => 'receipts/test.jpg',
            'played_at' => now(),
            'status' => PlayStatus::Pending,
        ]);
    }

    private function emptyDocs(): array
    {
        return [
            'status' => 'succeeded',
            'analyzeResult' => ['documents' => []],
        ];
    }
}
