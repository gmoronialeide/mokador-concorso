<?php

namespace App\Filament\Pages;

use App\Models\Prize;
use App\Models\PrizeSchedule;
use App\Models\WinningSlot;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PrizeSummary extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-trophy';

    protected static string | \UnitEnum | null $navigationGroup = 'Concorso';

    protected static ?string $navigationLabel = 'Riepilogo Premi';

    protected static ?string $title = 'Riepilogo Premi Settimanali';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.prize-summary';

    /** Griglia programmazione settimanale (dalla tabella prize_schedule). */
    public function getScheduleGrid(): array
    {
        $prizes = Prize::orderBy('code')->get();
        $schedules = PrizeSchedule::all()->groupBy('prize_id');

        $grid = [];
        foreach ($prizes as $prize) {
            $activeDays = $schedules->get($prize->id, collect())->pluck('day_of_week')->toArray();
            $grid[] = [
                'code' => $prize->code,
                'name' => $prize->name,
                'days' => $activeDays,
                'per_week' => count($activeDays),
                'total' => count($activeDays) * 4,
            ];
        }

        return $grid;
    }

    /** Riepilogo premi con conteggi. */
    public function getPrizes(): Collection
    {
        return Prize::query()
            ->withCount([
                'winningSlots as total_slots',
                'winningSlots as assigned_slots' => fn ($q) => $q->where('is_assigned', true),
                'winningSlots as expired_slots' => fn ($q) => $q
                    ->where('is_assigned', false)
                    ->where('scheduled_date', '<', Carbon::today()),
            ])
            ->orderBy('code')
            ->get();
    }

    /** Le 4 settimane del concorso. */
    public function getWeeks(): array
    {
        $start = Carbon::parse(config('app.concorso_start_date'));
        $weeks = [];

        for ($w = 0; $w < 4; $w++) {
            $weekStart = $start->copy()->addWeeks($w);
            $weekEnd = $weekStart->copy()->addDays(6);

            $days = [];
            for ($d = 0; $d < 7; $d++) {
                $date = $weekStart->copy()->addDays($d);
                $days[] = $date;
            }

            $weeks[] = [
                'number' => $w + 1,
                'label' => 'Settimana ' . ($w + 1),
                'range' => $weekStart->format('d/m') . ' – ' . $weekEnd->format('d/m/Y'),
                'days' => $days,
            ];
        }

        return $weeks;
    }

    /** Tutti gli slot raggruppati per data. */
    public function getAllSlotsByDate(): Collection
    {
        return WinningSlot::with(['prize', 'play.user'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get()
            ->groupBy(fn (WinningSlot $slot) => $slot->scheduled_date->format('Y-m-d'));
    }

    /** Premi ordinati per codice (per le tabelle settimanali). */
    public function getPrizesOrdered(): Collection
    {
        return Prize::orderBy('code')->get();
    }

    public function getToday(): string
    {
        return Carbon::today()->format('Y-m-d');
    }
}
