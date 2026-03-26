<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Importa i punti vendita dal file Excel di produzione.
 */
return new class extends Migration
{
    public function up(): void
    {
        $file = base_path('Elenco_Concorso_TOTALE_marzo 2026.xlsx');

        if (! file_exists($file)) {
            return;
        }

        $spreadsheet = IOFactory::load($file);
        $rows = $spreadsheet->getSheet(0)->toArray(null, true, true, true);

        // Remove header row
        array_shift($rows);

        $now = now();
        $batch = [];

        foreach ($rows as $row) {
            $code = trim((string) ($row['B'] ?? ''));

            if ($code === '') {
                continue;
            }

            $batch[] = [
                'code' => $code,
                'name' => trim((string) ($row['C'] ?? '')),
                'sign_name' => trim((string) ($row['D'] ?? '')),
                'vat_number' => trim((string) ($row['E'] ?? '')),
                'address' => trim((string) ($row['F'] ?? '')),
                'city' => trim((string) ($row['G'] ?? '')),
                'province' => strtoupper(trim((string) ($row['H'] ?? ''))),
                'agent' => trim((string) ($row['A'] ?? '')) ?: null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($batch, 100) as $chunk) {
            DB::table('stores')->insert($chunk);
        }
    }

    public function down(): void
    {
        DB::table('stores')->truncate();
    }
};
