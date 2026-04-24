<?php

namespace App\Console\Commands;

use App\Enums\PlayStatus;
use App\Jobs\VerifyPlayAutomatically;
use App\Models\Play;
use Illuminate\Console\Command;

class PlaysVerifyAuto extends Command
{
    protected $signature = 'plays:verify-auto {--limit=100}';

    protected $description = 'Dispatch VerifyPlayAutomatically jobs for eligible Pending plays.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $plays = Play::query()
            ->where('status', PlayStatus::Pending)
            ->whereNull('verification_type')
            ->where(fn ($q) => $q->whereNull('notes')->orWhere('notes', ''))
            ->with('store')
            ->limit($limit)
            ->get();

        foreach ($plays as $play) {
            VerifyPlayAutomatically::dispatch($play);
        }

        $this->info("Dispatched {$plays->count()} verification jobs.");

        return self::SUCCESS;
    }
}
