<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Griglia premi giornaliera/settimanale.
 * Definisce per ogni premio in quale giorno della settimana è disponibile.
 *
 * Fonte: "Premi giornalieri_Mokador ti porta in vacanza.xlsx"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prize_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prize_id')->constrained('prizes')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week')->comment('1=Lunedì, 7=Domenica (ISO 8601)');
            $table->unsignedTinyInteger('quantity')->default(1)->comment('Premi disponibili in quel giorno');
            $table->timestamps();

            $table->unique(['prize_id', 'day_of_week']);
        });

        // Popola la griglia dal foglio Excel
        $this->seedSchedule();
    }

    public function down(): void
    {
        Schema::dropIfExists('prize_schedule');
    }

    private function seedSchedule(): void
    {
        $prizes = DB::table('prizes')->pluck('id', 'code');
        $now = now();

        // Griglia da Excel:
        // A (caffè espresso oro):     Lun Mar Mer Gio Ven Sab Dom = tutti
        // B (100% arabica):           Lun Mar Mer Gio Ven Sab Dom = tutti
        // C (bicchierini a cuore):    Lun     Mer     Ven Sab Dom
        // D (t-shirt):                    Mar Mer Gio     Sab
        // E (grembiuli):              Lun         Gio     Sab
        $schedule = [
            'A' => [1, 2, 3, 4, 5, 6, 7],
            'B' => [1, 2, 3, 4, 5, 6, 7],
            'C' => [1, 3, 5, 6, 7],
            'D' => [2, 3, 4, 6],
            'E' => [1, 4, 6],
        ];

        $rows = [];
        foreach ($schedule as $code => $days) {
            foreach ($days as $day) {
                $rows[] = [
                    'prize_id' => $prizes[$code],
                    'day_of_week' => $day,
                    'quantity' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('prize_schedule')->insert($rows);
    }
};
