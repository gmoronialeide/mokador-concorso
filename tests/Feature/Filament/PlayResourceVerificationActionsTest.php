<?php

namespace Tests\Feature\Filament;

use App\Enums\PlayStatus;
use App\Enums\VerificationType;
use App\Filament\Resources\PlayResource\Pages\ListPlays;
use App\Models\Admin;
use App\Models\Play;
use App\Models\User;
use Filament\Tables\Columns\IconColumn;
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

    public function test_ban_with_next_id_mounts_receipt_on_next_record(): void
    {
        $play1 = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
        ]);
        $play2 = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
        ]);

        $component = Livewire::test(ListPlays::class)
            ->callTableAction('ban', $play1, data: ['ban_reason' => 'fake'], arguments: ['nextId' => $play2->id]);

        $this->assertSame(PlayStatus::Banned, $play1->fresh()->status);

        $mountedActions = $component->instance()->mountedActions;
        $this->assertNotEmpty($mountedActions);
        $this->assertSame('receipt', $mountedActions[0]['name'] ?? null);
        $this->assertSame((string) $play2->id, $mountedActions[0]['context']['recordKey'] ?? null);
    }

    public function test_ban_preserves_ids_snapshot_for_chained_receipt_mount(): void
    {
        $play1 = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
        ]);
        $play2 = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
        ]);

        $component = Livewire::test(ListPlays::class)
            ->callTableAction('ban', $play1, data: ['ban_reason' => 'x'], arguments: [
                'nextId' => $play2->id,
                'ids' => [$play1->id, $play2->id],
            ]);

        $mountedActions = $component->instance()->mountedActions;
        $this->assertSame('receipt', $mountedActions[0]['name'] ?? null);
        $this->assertSame([$play1->id, $play2->id], $mountedActions[0]['arguments']['ids'] ?? null);
    }

    public function test_unban_with_next_id_mounts_receipt_on_next_record(): void
    {
        $play1 = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Banned,
            'ban_reason' => 'x',
            'banned_at' => now(),
        ]);
        $play2 = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
        ]);

        $component = Livewire::test(ListPlays::class)
            ->callTableAction('unban', $play1, arguments: ['nextId' => $play2->id]);

        $this->assertSame(PlayStatus::Validated, $play1->fresh()->status);

        $mountedActions = $component->instance()->mountedActions;
        $this->assertNotEmpty($mountedActions);
        $this->assertSame('receipt', $mountedActions[0]['name'] ?? null);
        $this->assertSame((string) $play2->id, $mountedActions[0]['context']['recordKey'] ?? null);
    }

    public function test_validate_with_next_id_mounts_receipt_on_next_record(): void
    {
        $play1 = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
        ]);
        $play2 = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
        ]);

        $component = Livewire::test(ListPlays::class)
            ->callTableAction('validate', $play1, arguments: ['nextId' => $play2->id]);

        $this->assertSame(PlayStatus::Validated, $play1->fresh()->status);

        $mountedActions = $component->instance()->mountedActions;
        $this->assertNotEmpty($mountedActions);
        $this->assertSame('receipt', $mountedActions[0]['name'] ?? null);
        $this->assertSame((string) $play2->id, $mountedActions[0]['context']['recordKey'] ?? null);
    }

    public function test_validate_without_next_id_does_not_remount(): void
    {
        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
        ]);

        $component = Livewire::test(ListPlays::class)
            ->callTableAction('validate', $play);

        $this->assertSame(PlayStatus::Validated, $play->fresh()->status);
        $this->assertEmpty($component->instance()->mountedActions);
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

    public function test_list_page_verification_column_is_icon_with_tooltip(): void
    {
        Play::factory()->create([
            'user_id' => User::factory(),
            'verification_type' => VerificationType::Manual,
        ]);

        $component = Livewire::test(ListPlays::class);
        $component->assertOk();

        $column = $component->instance()->getTable()->getColumn('verification_type');

        $this->assertInstanceOf(IconColumn::class, $column);
        $this->assertSame('Manuale', $column->getTooltip(VerificationType::Manual));
        $this->assertSame('Automatica', $column->getTooltip(VerificationType::Auto));
        $this->assertSame('—', $column->getTooltip(null));
    }
}
