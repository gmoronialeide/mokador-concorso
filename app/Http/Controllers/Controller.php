<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;

abstract class Controller
{
    protected function contestStatus(): array
    {
        $now = Carbon::now();
        $startDate = Carbon::parse(config('app.concorso_start_date'));
        $endDate = Carbon::parse(config('app.concorso_end_date'));

        return [
            'contestNotStarted' => $now->lt($startDate),
            'contestEnded' => $now->gt($endDate->copy()->endOfDay()),
            'startDate' => $startDate,
        ];
    }
}
