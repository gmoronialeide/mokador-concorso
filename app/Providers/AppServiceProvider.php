<?php

namespace App\Providers;

use App\Models\Play;
use App\Observers\PlayObserver;
use App\Services\Ocr\AzureDocumentIntelligence;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AzureDocumentIntelligence::class, fn () => new AzureDocumentIntelligence(
            rtrim((string) config('services.azure_docintel.endpoint'), '/'),
            (string) config('services.azure_docintel.key'),
            (string) config('services.azure_docintel.api_version'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Play::observe(PlayObserver::class);
    }
}
