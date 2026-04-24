<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\PlayResource\Pages\ListPlays;
use App\Models\Admin;
use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlayResourceSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::first();
        $this->user = User::factory()->create();
        $this->actingAs($this->admin, 'admin');
    }

    public function test_plays_created_before_mount_are_visible(): void
    {
        $existingPlay = Play::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(ListPlays::class)
            ->assertCanSeeTableRecords([$existingPlay]);
    }

    public function test_plays_created_after_mount_are_hidden(): void
    {
        $existingPlay = Play::factory()->create(['user_id' => $this->user->id]);

        $component = Livewire::test(ListPlays::class);

        $newPlay = Play::factory()->create(['user_id' => $this->user->id]);

        $component
            ->call('$refresh')
            ->assertCanSeeTableRecords([$existingPlay])
            ->assertCanNotSeeTableRecords([$newPlay]);
    }

    public function test_filtering_does_not_reveal_new_plays(): void
    {
        $existingPlay = Play::factory()->create([
            'user_id' => $this->user->id,
            'is_winner' => true,
        ]);

        $component = Livewire::test(ListPlays::class);

        $newWinningPlay = Play::factory()->create([
            'user_id' => $this->user->id,
            'is_winner' => true,
        ]);

        $component
            ->filterTable('is_winner', true)
            ->assertCanSeeTableRecords([$existingPlay])
            ->assertCanNotSeeTableRecords([$newWinningPlay]);
    }

    public function test_search_does_not_reveal_new_plays(): void
    {
        $existingUser = User::factory()->create(['surname' => 'Rossi', 'name' => 'Mario']);
        $newUser = User::factory()->create(['surname' => 'Rossi', 'name' => 'Luigi']);

        $existingPlay = Play::factory()->create(['user_id' => $existingUser->id]);

        $component = Livewire::test(ListPlays::class);

        $newPlay = Play::factory()->create(['user_id' => $newUser->id]);

        $component
            ->searchTable('Rossi')
            ->assertCanSeeTableRecords([$existingPlay])
            ->assertCanNotSeeTableRecords([$newPlay]);
    }

    public function test_snapshot_max_id_set_at_mount(): void
    {
        $play = Play::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(ListPlays::class)
            ->assertSet('listSnapshotMaxId', $play->id);
    }

    public function test_snapshot_max_id_is_zero_when_no_plays(): void
    {
        Livewire::test(ListPlays::class)
            ->assertSet('listSnapshotMaxId', 0);
    }

    public function test_subheading_reports_new_plays_count(): void
    {
        Play::factory()->create(['user_id' => $this->user->id]);

        $component = Livewire::test(ListPlays::class);

        Play::factory()->count(3)->create(['user_id' => $this->user->id]);

        $this->assertSame(
            '3 nuove giocate dal caricamento. Ricarica per visualizzarle.',
            $component->instance()->getSubheading(),
        );
    }

    public function test_subheading_singular_when_one_new_play(): void
    {
        Play::factory()->create(['user_id' => $this->user->id]);

        $component = Livewire::test(ListPlays::class);

        Play::factory()->create(['user_id' => $this->user->id]);

        $this->assertSame(
            '1 nuova giocata dal caricamento. Ricarica per visualizzarla.',
            $component->instance()->getSubheading(),
        );
    }

    public function test_subheading_is_null_when_no_new_plays(): void
    {
        Play::factory()->create(['user_id' => $this->user->id]);

        $component = Livewire::test(ListPlays::class);

        $this->assertNull($component->instance()->getSubheading());
    }

    public function test_reload_action_emits_reload_js(): void
    {
        $component = Livewire::test(ListPlays::class)->callAction('reload');

        $xjs = $component->effects['xjs'] ?? [];

        $this->assertTrue(
            collect($xjs)->contains(
                fn ($item): bool => is_array($item)
                    ? ($item['expression'] ?? null) === 'window.location.reload()'
                    : $item === 'window.location.reload()',
            ),
            'Expected reload action to emit window.location.reload() JS effect.',
        );
    }
}
