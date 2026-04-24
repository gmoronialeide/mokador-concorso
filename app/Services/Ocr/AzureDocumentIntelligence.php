<?php

namespace App\Services\Ocr;

use App\Services\Ocr\Exceptions\OcrApiException;
use App\Services\Ocr\Exceptions\OcrTimeoutException;
use Illuminate\Support\Facades\Http;

class AzureDocumentIntelligence
{
    public function __construct(
        private string $endpoint,
        private string $key,
        private string $apiVersion,
    ) {}

    public function analyze(string $filePath, string $model): array
    {
        $submitUrl = "{$this->endpoint}/documentintelligence/documentModels/{$model}:analyze?api-version={$this->apiVersion}";

        $submit = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->key,
            'Content-Type' => 'application/octet-stream',
        ])->withBody(file_get_contents($filePath), 'application/octet-stream')
            ->post($submitUrl);

        if ($submit->status() !== 202) {
            throw new OcrApiException("Submit failed HTTP {$submit->status()}: {$submit->body()}");
        }

        $operationUrl = (string) $submit->header('Operation-Location');
        if ($operationUrl === '') {
            throw new OcrApiException('Operation-Location header missing');
        }

        return $this->poll($operationUrl);
    }

    public function analyzeReceiptOrInvoice(string $filePath): array
    {
        $result = $this->analyze($filePath, 'prebuilt-receipt');
        $documents = $result['analyzeResult']['documents'] ?? [];

        if ($documents === []) {
            $result = $this->analyze($filePath, 'prebuilt-invoice');
        }

        return $result;
    }

    private function poll(string $operationUrl, int $maxAttempts = 30): array
    {
        $status = 'running';
        $result = null;
        $attempts = 0;

        while (in_array($status, ['running', 'notStarted'], true) && $attempts < $maxAttempts) {
            sleep(1);
            $attempts++;

            $poll = Http::withHeaders(['Ocp-Apim-Subscription-Key' => $this->key])
                ->get($operationUrl);

            if (! $poll->successful()) {
                throw new OcrApiException("Poll failed HTTP {$poll->status()}: {$poll->body()}");
            }

            $result = $poll->json();
            $status = $result['status'] ?? 'unknown';
        }

        if ($status !== 'succeeded') {
            throw new OcrTimeoutException("Analysis not completed after {$maxAttempts}s: {$status}");
        }

        return $result;
    }
}
