<?php

namespace Tests\Feature\Filament;

use App\Enums\VerificationType;
use App\Filament\Resources\PlayResource\Pages\ListPlays;
use App\Models\Admin;
use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class PlayResourceVerificationFilterTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private User $user;

    private Play $autoPlay;

    private Play $manualPlay;

    private Play $nullPlay;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-23 10:00:00');

        $this->admin = Admin::first();
        $this->user = User::factory()->create();
        $this->actingAs($this->admin, 'admin');

        $this->autoPlay = Play::factory()->create([
            'user_id' => $this->user->id,
            'played_at' => now(),
            'verification_type' => VerificationType::Auto,
        ]);

        $this->manualPlay = Play::factory()->create([
            'user_id' => $this->user->id,
            'played_at' => now(),
            'verification_type' => VerificationType::Manual,
        ]);

        $this->nullPlay = Play::factory()->create([
            'user_id' => $this->user->id,
            'played_at' => now(),
            'verification_type' => null,
        ]);

        Carbon::setTestNow('2026-04-23 10:05:00');
    }

    public function test_filter_by_auto_shows_only_auto_plays(): void
    {
        Livewire::test(ListPlays::class)
            ->filterTable('verification_type', 'auto')
            ->assertCanSeeTableRecords([$this->autoPlay])
            ->assertCanNotSeeTableRecords([$this->manualPlay, $this->nullPlay]);
    }

    public function test_filter_by_manual_shows_only_manual_plays(): void
    {
        Livewire::test(ListPlays::class)
            ->filterTable('verification_type', 'manual')
            ->assertCanSeeTableRecords([$this->manualPlay])
            ->assertCanNotSeeTableRecords([$this->autoPlay, $this->nullPlay]);
    }

    public function test_filter_by_null_shows_only_unverified_plays(): void
    {
        Livewire::test(ListPlays::class)
            ->filterTable('verification_type', 'null')
            ->assertCanSeeTableRecords([$this->nullPlay])
            ->assertCanNotSeeTableRecords([$this->autoPlay, $this->manualPlay]);
    }
}
