<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TestOcrReceipt extends Command
{
    protected $signature = 'ocr:test {file : Path relativo a storage/app/private o path assoluto}';

    protected $description = 'POC Azure Document Intelligence prebuilt-receipt su uno scontrino.';

    public function handle(): int
    {
        $endpoint = rtrim((string) config('services.azure_docintel.endpoint'), '/');
        $key = (string) config('services.azure_docintel.key');
        $apiVersion = (string) config('services.azure_docintel.api_version');

        if ($endpoint === '' || $key === '') {
            $this->error('AZURE_DOCINTEL_ENDPOINT / AZURE_DOCINTEL_KEY mancanti in .env');

            return self::FAILURE;
        }

        $input = (string) $this->argument('file');
        $path = str_starts_with($input, '/') ? $input : Storage::disk('local')->path($input);

        if (! is_file($path)) {
            $this->error("File non trovato: {$path}");

            return self::FAILURE;
        }

        $this->info("File: {$path} (".number_format(filesize($path)).' bytes)');

        $submitUrl = "{$endpoint}/documentintelligence/documentModels/prebuilt-receipt:analyze?api-version={$apiVersion}";

        $this->line('→ Invio scontrino ad Azure...');

        $submit = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $key,
            'Content-Type' => 'application/octet-stream',
        ])->withBody(file_get_contents($path), 'application/octet-stream')
            ->post($submitUrl);

        if ($submit->status() !== 202) {
            $this->error("Submit fallito HTTP {$submit->status()}: ".$submit->body());

            return self::FAILURE;
        }

        $operationUrl = $submit->header('Operation-Location');
        if ($operationUrl === '') {
            $this->error('Operation-Location header mancante');

            return self::FAILURE;
        }

        $this->line("→ Polling: {$operationUrl}");

        $status = 'running';
        $result = null;
        $attempts = 0;
        $maxAttempts = 30;

        while (in_array($status, ['running', 'notStarted'], true) && $attempts < $maxAttempts) {
            sleep(1);
            $attempts++;

            $poll = Http::withHeaders(['Ocp-Apim-Subscription-Key' => $key])->get($operationUrl);

            if (! $poll->successful()) {
                $this->error("Poll fallito HTTP {$poll->status()}: ".$poll->body());

                return self::FAILURE;
            }

            $result = $poll->json();
            $status = $result['status'] ?? 'unknown';
            $this->line("  [{$attempts}] status={$status}");
        }

        if ($status !== 'succeeded') {
            $this->error("Analisi non completata: {$status}");
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::FAILURE;
        }

        $document = $result['analyzeResult']['documents'][0] ?? null;
        if ($document === null) {
            $this->warn('Nessun documento riconosciuto come scontrino.');
            $this->line(json_encode($result['analyzeResult'] ?? $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $fields = $document['fields'] ?? [];

        $extract = function (string $name) use ($fields): array {
            $f = $fields[$name] ?? null;
            if ($f === null) {
                return ['value' => null, 'confidence' => null];
            }

            $value = $f['valueString']
                ?? $f['valueDate']
                ?? $f['valueTime']
                ?? $f['valueNumber']
                ?? $f['valueCurrency']['amount']
                ?? $f['valuePhoneNumber']
                ?? $f['content']
                ?? null;

            return ['value' => $value, 'confidence' => $f['confidence'] ?? null];
        };

        $this->newLine();
        $this->info('=== Campi estratti ===');
        $this->line("docType:         {$document['docType']} (conf ".($document['confidence'] ?? 'n/d').')');

        foreach ([
            'MerchantName',
            'MerchantAddress',
            'MerchantPhoneNumber',
            'MerchantTaxId',
            'TransactionDate',
            'TransactionTime',
            'Subtotal',
            'TotalTax',
            'Total',
            'ReceiptType',
        ] as $name) {
            $row = $extract($name);
            $value = $row['value'] === null ? '—' : (is_scalar($row['value']) ? $row['value'] : json_encode($row['value']));
            $conf = $row['confidence'] === null ? '' : ' (conf '.number_format((float) $row['confidence'], 3).')';
            $this->line(sprintf('%-22s %s%s', $name.':', $value, $conf));
        }

        $items = $fields['Items']['valueArray'] ?? [];
        $this->line('Items:                  '.count($items).' voci');
        foreach ($items as $idx => $item) {
            $desc = $item['valueObject']['Description']['valueString']
                ?? $item['valueObject']['Description']['content']
                ?? '—';
            $qty = $item['valueObject']['Quantity']['valueNumber'] ?? '—';
            $price = $item['valueObject']['Price']['valueCurrency']['amount']
                ?? $item['valueObject']['TotalPrice']['valueCurrency']['amount']
                ?? '—';
            $confD = $item['valueObject']['Description']['confidence'] ?? null;
            $this->line(sprintf('  [%d] %s | qty=%s | price=%s | confDesc=%s',
                $idx + 1, $desc, $qty, $price,
                $confD === null ? '—' : number_format((float) $confD, 3)
            ));
        }

        $this->newLine();
        $this->info('=== Validazione concorso ===');

        $start = config('concorso.start_date') ?? env('CONCORSO_START_DATE');
        $end = config('concorso.end_date') ?? env('CONCORSO_END_DATE');

        $date = $extract('TransactionDate')['value'];
        if ($date !== null && $start && $end) {
            $inWindow = $date >= $start && $date <= $end;
            $this->line('Data nel concorso:     '.($inWindow ? 'SI' : 'NO')." ({$date} vs {$start} → {$end})");
        } else {
            $this->line('Data nel concorso:     N/D (data mancante o env non configurato)');
        }

        $merchant = strtoupper((string) $extract('MerchantName')['value']);
        $this->line("Merchant:              {$merchant}");

        $itemDescriptions = [];
        foreach ($items as $item) {
            $d = $item['valueObject']['Description']['valueString']
                ?? $item['valueObject']['Description']['content']
                ?? '';
            if ($d !== '') {
                $itemDescriptions[] = strtoupper($d);
            }
        }
        $joined = implode(' | ', $itemDescriptions);
        $hasMokador = str_contains($joined, 'MOKADOR');
        $hasCaffe = str_contains($joined, 'CAFFE') || str_contains($joined, 'CAFFÈ');
        $this->line('Item contiene "mokador": '.($hasMokador ? 'SI' : 'NO'));
        $this->line('Item contiene "caffe":   '.($hasCaffe ? 'SI' : 'NO'));

        $confidences = array_filter(array_map(
            fn ($n) => $extract($n)['confidence'],
            ['MerchantName', 'TransactionDate', 'Total'],
        ));
        $avg = $confidences !== [] ? array_sum($confidences) / count($confidences) : 0.0;
        $this->line('Confidence media campi chiave: '.number_format($avg, 3));

        if ($this->option('verbose') || $this->getOutput()->isVerbose()) {
            $this->newLine();
            $this->info('=== JSON completo documento ===');
            $this->line(json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return self::SUCCESS;
    }
}
