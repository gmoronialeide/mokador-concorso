<?php

namespace Tests\Feature\Commands;

use App\Enums\PlayStatus;
use App\Mail\PendingPlaysAlert;
use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PlaysAlertPendingCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.concorso_alerts.recipients', [
            'a@test.com',
            'b@test.com',
            'c@test.com',
        ]);
    }

    public function test_no_alert_when_no_pending_plays(): void
    {
        Mail::fake();

        $this->artisan('plays:alert-pending')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_sends_to_configured_recipients_with_subject_and_breakdown(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/a.jpg',
            'played_at' => Carbon::now('Europe/Rome'),
            'status' => PlayStatus::Pending,
            'notes' => 'controllo automatico: non torna data scontrino (2025-01-01)',
        ]);

        Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/b.jpg',
            'played_at' => Carbon::now('Europe/Rome'),
            'status' => PlayStatus::Pending,
            'notes' => 'controllo automatico: non torna data scontrino (2024-01-01)',
        ]);

        Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/c.jpg',
            'played_at' => Carbon::now('Europe/Rome'),
            'status' => PlayStatus::Pending,
            'notes' => 'controllo automatico: non torna importo (0,50€)',
        ]);

        // Excluded: not today
        Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/d.jpg',
            'played_at' => Carbon::now('Europe/Rome')->subDays(2),
            'status' => PlayStatus::Pending,
            'notes' => 'controllo automatico: non torna importo',
        ]);

        // Excluded: no notes
        Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/e.jpg',
            'played_at' => Carbon::now('Europe/Rome'),
            'status' => PlayStatus::Pending,
        ]);

        $today = Carbon::now('Europe/Rome')->toDateString();

        $this->artisan('plays:alert-pending')->assertSuccessful();

        Mail::assertSent(PendingPlaysAlert::class, function (PendingPlaysAlert $mail) use ($today) {
            $hasAll = $mail->hasTo('a@test.com')
                && $mail->hasTo('b@test.com')
                && $mail->hasTo('c@test.com');

            return $hasAll
                && $mail->count === 3
                && $mail->date === $today
                && ($mail->breakdown['data'] ?? 0) === 2
                && ($mail->breakdown['importo'] ?? 0) === 1
                && str_contains($mail->envelope()->subject, "Giocate da verificare: 3 ({$today})");
        });
    }

    public function test_fails_when_no_recipients_configured(): void
    {
        Mail::fake();
        config()->set('services.concorso_alerts.recipients', []);

        $user = User::factory()->create();
        Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/a.jpg',
            'played_at' => Carbon::now('Europe/Rome'),
            'status' => PlayStatus::Pending,
            'notes' => 'controllo automatico: non torna data scontrino',
        ]);

        $this->artisan('plays:alert-pending')->assertFailed();

        Mail::assertNothingSent();
    }
}
