<?php

namespace App\Console\Commands;

use App\Enums\PlayStatus;
use App\Jobs\VerifyPlayAutomatically;
use App\Models\Play;
use Illuminate\Console\Command;

class PlaysVerifyAuto extends Command
{
    protected $signature = 'plays:verify-auto {--limit=100}';

    protected $description = 'Run VerifyPlayAutomatically synchronously for eligible Pending plays.';

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
            VerifyPlayAutomatically::dispatchSync($play);
        }

        $this->info("Processed {$plays->count()} plays synchronously.");

        return self::SUCCESS;
    }
}
