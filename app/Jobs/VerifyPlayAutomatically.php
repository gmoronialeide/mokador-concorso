<?php

namespace App\Jobs;

use App\Models\Play;
use App\Services\Ocr\AzureDocumentIntelligence;
use App\Services\Ocr\PlayVerifier;
use App\Services\Ocr\ReceiptExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class VerifyPlayAutomatically implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(public Play $play) {}

    public function handle(
        AzureDocumentIntelligence $azure,
        ReceiptExtractor $extractor,
        PlayVerifier $verifier,
    ): void {
        $play = $this->play->fresh();
        if ($play === null) {
            return;
        }

        if (empty($play->receipt_image)) {
            $result = $verifier->verify($play, null);
            $play->update([
                'status' => $result->status,
                'verification_type' => $result->type,
                'notes' => $result->noteString() ?: null,
            ]);

            return;
        }

        $path = Storage::path($play->receipt_image);
        $raw = $azure->analyzeReceiptOrInvoice($path);
        $doc = $extractor->fromAzureResponse($raw);
        $result = $verifier->verify($play, $doc);

        $play->update([
            'status' => $result->status,
            'verification_type' => $result->type,
            'notes' => $result->noteString() ?: null,
            'ocr_raw' => $raw,
        ]);
    }
}
