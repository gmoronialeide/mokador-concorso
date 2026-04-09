<?php

namespace App\Console\Commands;

use App\Models\Prize;
use App\Models\PrizeSchedule;
use App\Models\WinningSlot;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateWinningSlots extends Command
{
    protected $signature = 'concorso:generate-slots
                            {--dry-run : Mostra la distribuzione senza inserire record}
                            {--reset : Elimina tutti gli slot esistenti prima di generare}';

    protected $description = 'Genera i 104 slot vincenti distribuiti nei 28 giorni del concorso';

    public function handle(): int
    {
        $startDate = Carbon::parse(config('app.concorso_start_date'));
        $endDate = Carbon::parse(config('app.concorso_end_date'));
        $days = (int) $startDate->diffInDays($endDate) + 1;

        if ($days !== 28) {
            $this->error("Il concorso deve durare esattamente 28 giorni (trovati: {$days})");

            return self::FAILURE;
        }

        $prizes = Prize::all()->keyBy('code');
        if ($prizes->count() !== 5) {
            $this->error('Devono esistere esattamente 5 premi (A-E). Verifica la migrazione seed_production_data.');

            return self::FAILURE;
        }

        // Legge la griglia premi dal DB (tabella prize_schedule)
        $schedule = PrizeSchedule::all()
            ->groupBy(fn (PrizeSchedule $ps) => $prizes->firstWhere('id', $ps->prize_id)?->code)
            ->map(fn ($group) => $group->pluck('day_of_week')->toArray())
            ->toArray();

        if (empty($schedule)) {
            $this->error('Griglia premi vuota. Verifica la migrazione create_prize_schedule_table.');

            return self::FAILURE;
        }

        $isDryRun = $this->option('dry-run');
        $isReset = $this->option('reset');

        if ($isReset && ! $isDryRun) {
            $assignedCount = WinningSlot::where('is_assigned', true)->count();
            if ($assignedCount > 0) {
                $this->error("Impossibile resettare: {$assignedCount} slot già assegnati. Il concorso è in corso.");

                return self::FAILURE;
            }
            WinningSlot::query()->delete();
            $this->info('Slot esistenti eliminati.');
        }

        if (! $isDryRun && ! $isReset && WinningSlot::count() > 0) {
            $this->error('Esistono già degli slot. Usa --reset per rigenerare.');

            return self::FAILURE;
        }

        $slots = [];
        $summary = [];
        $minGapMinutes = 50;

        for ($day = 0; $day < 28; $day++) {
            $date = $startDate->copy()->addDays($day);
            $dayOfWeek = (int) $date->isoFormat('E'); // 1=Lun, 7=Dom

            // Raccoglie i premi attivi per questo giorno
            $dayPrizes = [];
            foreach ($schedule as $prizeCode => $activeDays) {
                if (in_array($dayOfWeek, $activeDays)) {
                    $dayPrizes[] = $prizeCode;
                }
            }

            if (empty($dayPrizes)) {
                continue;
            }

            // Genera orari con distanza minima di 50 minuti
            // Primo giorno: 07:00–11:59 (420–719 min), altri: 00:00–11:59 (0–719 min)
            $minMinute = ($day === 0) ? 420 : 0;  // 420 = 7*60
            $maxMinute = 719;                       // 719 = 11*60+59
            // Max 2 slot prima delle 07:00 (tranne primo giorno che parte da 07:00)
            $maxBeforeSeven = ($day === 0) ? 0 : 2;
            $times = $this->generateSpacedTimes(count($dayPrizes), $minMinute, $maxMinute, $minGapMinutes, $maxBeforeSeven);

            // Mescola l'ordine dei premi così non sono sempre A, B, C...
            shuffle($dayPrizes);

            foreach ($dayPrizes as $i => $prizeCode) {
                $totalMinutes = $times[$i];
                $time = sprintf('%02d:%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60, random_int(0, 59));

                $slots[] = [
                    'prize_id' => $prizes[$prizeCode]->id,
                    'scheduled_date' => $date->format('Y-m-d'),
                    'scheduled_time' => $time,
                    'is_assigned' => false,
                    'play_id' => null,
                    'assigned_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $summary[$prizeCode] = ($summary[$prizeCode] ?? 0) + 1;
            }
        }

        $totalSlots = count($slots);

        $this->info("Distribuzione generata: {$totalSlots} slot totali");
        $this->table(
            ['Premio', 'Codice', 'Slot generati', 'Atteso'],
            collect($summary)->map(fn ($count, $code) => [
                $prizes[$code]->name,
                $code,
                $count,
                $prizes[$code]->total_quantity,
            ])->values()->toArray()
        );

        if ($totalSlots !== 104) {
            $this->error("Totale slot errato: {$totalSlots} (attesi 104)");

            return self::FAILURE;
        }

        foreach ($summary as $code => $count) {
            if ($count !== $prizes[$code]->total_quantity) {
                $this->error("Premio {$code}: {$count} slot generati, attesi {$prizes[$code]->total_quantity}");

                return self::FAILURE;
            }
        }

        if ($isDryRun) {
            $this->warn('Dry run: nessun record inserito.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($slots) {
            WinningSlot::insert($slots);
        });

        Log::channel('daily')->info('Slot vincenti generati', [
            'total' => $totalSlots,
            'distribution' => $summary,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);

        $this->info('Slot vincenti inseriti con successo.');

        return self::SUCCESS;
    }

    /**
     * Genera N orari casuali (in minuti dal mezzanotte) con:
     * - distanza minima garantita tra slot consecutivi
     * - max N slot prima delle 07:00
     *
     * @return array<int> Minuti ordinati in modo crescente
     */
    private function generateSpacedTimes(int $count, int $minMinute, int $maxMinute, int $gap, int $maxBeforeSeven = PHP_INT_MAX): array
    {
        $sevenAm = 420; // 7 * 60
        $maxAttempts = 200;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $times = [];
            for ($i = 0; $i < $count; $i++) {
                $times[] = random_int($minMinute, $maxMinute);
            }
            sort($times);

            // Check distanza minima
            $valid = true;
            for ($i = 1; $i < $count; $i++) {
                if ($gap > $times[$i] - $times[$i - 1]) {
                    $valid = false;
                    break;
                }
            }

            if (! $valid) {
                continue;
            }

            // Check max slot prima delle 07:00
            $beforeSeven = count(array_filter($times, fn ($t) => $t < $sevenAm));
            if ($beforeSeven > $maxBeforeSeven) {
                continue;
            }

            return $times;
        }

        // Fallback: distribuzione uniforme con gap garantito
        $times = [];
        $rangePerSlot = intdiv($maxMinute - $minMinute, $count);
        for ($i = 0; $i < $count; $i++) {
            $slotMin = $minMinute + ($i * $rangePerSlot);
            $slotMax = min($slotMin + $rangePerSlot - $gap, $maxMinute);
            $times[] = random_int($slotMin, max($slotMin, $slotMax));
        }

        sort($times);

        return $times;
    }
}
