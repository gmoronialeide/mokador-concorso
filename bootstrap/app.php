<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('queue:work --stop-when-empty --tries=3 --timeout=90')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('plays:verify-auto')
            ->everyFifteenMinutes()
            ->timezone('Europe/Rome')
            ->withoutOverlapping();

        $schedule->command('plays:alert-pending')
            ->dailyAt('10:00')
            ->timezone('Europe/Rome');

        $schedule->command('plays:alert-pending')
            ->dailyAt('17:00')
            ->timezone('Europe/Rome');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->booted(function () {
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('play', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });
    })
    ->create();
