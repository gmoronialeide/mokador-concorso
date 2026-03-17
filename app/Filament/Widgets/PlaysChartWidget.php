<?php

namespace App\Filament\Widgets;

use App\Models\Play;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class PlaysChartWidget extends ChartWidget
{
    protected ?string $heading = 'Giocate per giorno';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $start = Carbon::parse(config('app.concorso_start_date'));
        $end = Carbon::parse(config('app.concorso_end_date'));
        $today = Carbon::today();

        // Mostra solo fino a oggi (o fine concorso)
        $rangeEnd = $today->lessThan($end) ? $today : $end;

        $labels = [];
        $plays = [];
        $wins = [];

        $date = $start->copy();
        while ($date->lessThanOrEqualTo($rangeEnd)) {
            $dateStr = $date->format('Y-m-d');
            $labels[] = $date->format('d/m');
            $plays[] = Play::whereDate('played_at', $dateStr)->count();
            $wins[] = Play::whereDate('played_at', $dateStr)->where('is_winner', true)->count();
            $date->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Giocate',
                    'data' => $plays,
                    'borderColor' => '#9D4A15',
                    'backgroundColor' => 'rgba(157, 74, 21, 0.1)',
                ],
                [
                    'label' => 'Vincite',
                    'data' => $wins,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
