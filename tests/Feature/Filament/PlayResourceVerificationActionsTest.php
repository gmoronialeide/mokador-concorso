<?php

namespace Tests\Feature\Filament;

use App\Enums\PlayStatus;
use App\Enums\VerificationType;
use App\Filament\Resources\PlayResource\Pages\ListPlays;
use App\Models\Admin;
use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlayResourceVerificationActionsTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::first();
        $this->actingAs($this->admin, 'admin');
    }

    public function test_validate_action_sets_verification_type_manual(): void
    {
        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
            'verification_type' => null,
        ]);

        Livewire::test(ListPlays::class)
            ->callTableAction('validate', $play)
            ->assertHasNoTableActionErrors();

        $fresh = $play->fresh();
        $this->assertSame(PlayStatus::Validated, $fresh->status);
        $this->assertSame(VerificationType::Manual, $fresh->verification_type);
    }

    public function test_ban_action_sets_verification_type_manual(): void
    {
        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
            'verification_type' => null,
        ]);

        Livewire::test(ListPlays::class)
            ->callTableAction('ban', $play, data: ['ban_reason' => 'scontrino non valido'])
            ->assertHasNoTableActionErrors();

        $fresh = $play->fresh();
        $this->assertSame(PlayStatus::Banned, $fresh->status);
        $this->assertSame('scontrino non valido', $fresh->ban_reason);
        $this->assertSame(VerificationType::Manual, $fresh->verification_type);
    }

    public function test_unban_action_sets_verification_type_manual(): void
    {
        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Banned,
            'ban_reason' => 'prev',
            'banned_at' => now(),
            'verification_type' => null,
        ]);

        Livewire::test(ListPlays::class)
            ->callTableAction('unban', $play)
            ->assertHasNoTableActionErrors();

        $fresh = $play->fresh();
        $this->assertSame(PlayStatus::Validated, $fresh->status);
        $this->assertNull($fresh->ban_reason);
        $this->assertNull($fresh->banned_at);
        $this->assertSame(VerificationType::Manual, $fresh->verification_type);
    }

    public function test_validate_overwrites_auto_with_manual(): void
    {
        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
            'verification_type' => VerificationType::Auto,
        ]);

        Livewire::test(ListPlays::class)
            ->callTableAction('validate', $play)
            ->assertHasNoTableActionErrors();

        $this->assertSame(VerificationType::Manual, $play->fresh()->verification_type);
    }

    public function test_view_play_page_renders_verification_section(): void
    {
        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'verification_type' => VerificationType::Manual,
        ]);

        $this->get(route('filament.admin.resources.plays.view', ['record' => $play]))
            ->assertOk()
            ->assertSee('Verifica')
            ->assertSee('Manuale');
    }
}
