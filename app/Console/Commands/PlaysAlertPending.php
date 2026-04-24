<?php

namespace App\Console\Commands;

use App\Enums\PlayStatus;
use App\Mail\PendingPlaysAlert;
use App\Models\Play;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class PlaysAlertPending extends Command
{
    protected $signature = 'plays:alert-pending';

    protected $description = 'Mail daily breakdown of Play pending manual verification.';

    public function handle(): int
    {
        $today = Carbon::now('Europe/Rome')->toDateString();

        $plays = Play::query()
            ->whereDate('played_at', $today)
            ->where('status', PlayStatus::Pending)
            ->whereNotNull('notes')
            ->where('notes', '!=', '')
            ->get();

        if ($plays->isEmpty()) {
            $this->info('No pending plays to alert.');

            return self::SUCCESS;
        }

        $breakdown = $this->breakdown($plays);
        $recipients = (array) config('services.concorso_alerts.recipients', []);

        if ($recipients === []) {
            $this->warn('No recipients configured.');

            return self::FAILURE;
        }

        Mail::to($recipients)->send(new PendingPlaysAlert($plays->count(), $breakdown, $today));

        $this->info("Alert sent: {$plays->count()} plays.");

        return self::SUCCESS;
    }

    private function breakdown(Collection $plays): array
    {
        $categories = [
            'data' => 'data scontrino',
            'importo' => 'importo',
            'punto vendita' => 'punto vendita',
            'OCR' => 'OCR non riuscito',
            'confidence' => 'confidence bassa',
            'store' => 'store non assegnato',
        ];

        $counts = array_fill_keys(array_keys($categories), 0);

        foreach ($plays as $play) {
            $notes = (string) $play->notes;
            foreach ($categories as $key => $needle) {
                if (str_contains($notes, $needle)) {
                    $counts[$key]++;
                }
            }
        }

        return array_filter($counts);
    }
}
