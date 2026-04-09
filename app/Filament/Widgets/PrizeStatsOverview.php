<?php

namespace App\Filament\Widgets;

use App\Enums\PlayStatus;
use App\Models\Play;
use App\Models\User;
use App\Models\WinningSlot;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class PrizeStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = Carbon::today();

        return [
            Stat::make('Utenti registrati', User::count())
                ->description('Oggi: ' . User::whereDate('created_at', $today)->count()),
            Stat::make('Giocate totali', Play::count())
                ->description('Oggi: ' . Play::whereDate('played_at', $today)->count()),
            Stat::make('Premi assegnati', WinningSlot::where('is_assigned', true)->count() . ' / ' . WinningSlot::count())
                ->color('success'),
            Stat::make('Non assegnati (scaduti)', WinningSlot::where('is_assigned', false)->whereDate('scheduled_date', '<', $today)->count())
                ->color(WinningSlot::where('is_assigned', false)->whereDate('scheduled_date', '<', $today)->count() > 0 ? 'danger' : 'success'),
            Stat::make('Giocate bannate', Play::where('status', PlayStatus::Banned)->count())
                ->color('danger'),
            Stat::make('In verifica', Play::where('status', PlayStatus::Pending)->count())
                ->color('warning'),
        ];
    }
}
