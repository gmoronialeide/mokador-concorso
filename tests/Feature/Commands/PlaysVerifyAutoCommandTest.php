<?php

namespace Tests\Feature\Commands;

use App\Enums\PlayStatus;
use App\Enums\VerificationType;
use App\Jobs\VerifyPlayAutomatically;
use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PlaysVerifyAutoCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_only_eligible_plays(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        // eligible x3
        for ($i = 0; $i < 3; $i++) {
            Play::create([
                'user_id' => $user->id,
                'store_code' => 'STORE01',
                'receipt_image' => 'receipts/a.jpg',
                'played_at' => now(),
                'status' => PlayStatus::Pending,
            ]);
        }

        // already auto-verified
        Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/a.jpg',
            'played_at' => now(),
            'status' => PlayStatus::Pending,
            'verification_type' => VerificationType::Auto,
        ]);

        // has notes
        Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/a.jpg',
            'played_at' => now(),
            'status' => PlayStatus::Pending,
            'notes' => 'controllo automatico: non torna data',
        ]);

        // validated
        Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/a.jpg',
            'played_at' => now(),
            'status' => PlayStatus::Validated,
        ]);

        $this->artisan('plays:verify-auto')->assertSuccessful();

        Queue::assertPushed(VerifyPlayAutomatically::class, 3);
    }

    public function test_respects_limit_option(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            Play::create([
                'user_id' => $user->id,
                'store_code' => 'STORE01',
                'receipt_image' => 'receipts/a.jpg',
                'played_at' => now(),
                'status' => PlayStatus::Pending,
            ]);
        }

        $this->artisan('plays:verify-auto', ['--limit' => 5])->assertSuccessful();

        Queue::assertPushed(VerifyPlayAutomatically::class, 5);
    }
}
