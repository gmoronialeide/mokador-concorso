<?php

namespace App\Console\Commands;

use App\Models\Store;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportStores extends Command
{
    protected $signature = 'stores:import {file* : Path to the Excel file} {--sheet=0 : Sheet index (0-based)}';

    protected $description = 'Import stores from an Excel file';

    public function handle(): int
    {
        $file = implode(' ', (array) $this->argument('file'));

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getSheet((int) $this->option('sheet'));
        $rows = $sheet->toArray(null, true, true, true);

        // Remove header row
        $header = array_shift($rows);
        $this->info('Columns found: ' . implode(', ', array_filter($header)));

        $created = 0;
        $skipped = 0;

        foreach ($rows as $index => $row) {
            $code = trim((string) ($row['B'] ?? ''));

            if ($code === '') {
                $skipped++;

                continue;
            }

            $data = [
                'code' => $code,
                'name' => trim((string) ($row['C'] ?? '')),
                'sign_name' => trim((string) ($row['D'] ?? '')),
                'vat_number' => trim((string) ($row['E'] ?? '')),
                'address' => trim((string) ($row['F'] ?? '')),
                'city' => trim((string) ($row['G'] ?? '')),
                'province' => strtoupper(trim((string) ($row['H'] ?? ''))),
                'agent' => trim((string) ($row['A'] ?? '')) ?: null,
                'is_active' => true,
            ];

            Store::create($data);
            $created++;
        }

        $this->info("Import completed: {$created} created, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
