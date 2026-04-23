<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\PlayResource\Pages\ListPlays;
use App\Models\Admin;
use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
        Carbon::setTestNow('2026-04-23 10:00:00');

        $existingPlay = Play::factory()->create([
            'user_id' => $this->user->id,
            'played_at' => now(),
        ]);

        Carbon::setTestNow('2026-04-23 10:05:00');

        Livewire::test(ListPlays::class)
            ->assertCanSeeTableRecords([$existingPlay]);
    }

    public function test_plays_created_after_mount_are_hidden(): void
    {
        Carbon::setTestNow('2026-04-23 10:00:00');

        $existingPlay = Play::factory()->create([
            'user_id' => $this->user->id,
            'played_at' => now(),
        ]);

        Carbon::setTestNow('2026-04-23 10:05:00');

        $component = Livewire::test(ListPlays::class);

        Carbon::setTestNow('2026-04-23 10:10:00');

        $newPlay = Play::factory()->create([
            'user_id' => $this->user->id,
            'played_at' => now(),
        ]);

        $component
            ->call('$refresh')
            ->assertCanSeeTableRecords([$existingPlay])
            ->assertCanNotSeeTableRecords([$newPlay]);
    }

    public function test_filtering_does_not_reveal_new_plays(): void
    {
        Carbon::setTestNow('2026-04-23 10:00:00');

        $existingPlay = Play::factory()->create([
            'user_id' => $this->user->id,
            'played_at' => now(),
            'is_winner' => true,
        ]);

        Carbon::setTestNow('2026-04-23 10:05:00');

        $component = Livewire::test(ListPlays::class);

        Carbon::setTestNow('2026-04-23 10:10:00');

        $newWinningPlay = Play::factory()->create([
            'user_id' => $this->user->id,
            'played_at' => now(),
            'is_winner' => true,
        ]);

        $component
            ->filterTable('is_winner', true)
            ->assertCanSeeTableRecords([$existingPlay])
            ->assertCanNotSeeTableRecords([$newWinningPlay]);
    }

    public function test_subheading_reports_new_plays_count(): void
    {
        Carbon::setTestNow('2026-04-23 10:00:00');

        Livewire::test(ListPlays::class)
            ->assertSet('listSnapshotAt', '2026-04-23 10:00:00');

        Carbon::setTestNow('2026-04-23 10:10:00');

        Play::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'played_at' => now(),
        ]);

        $component = Livewire::test(ListPlays::class);
        $component->set('listSnapshotAt', '2026-04-23 10:00:00');

        $this->assertSame(
            '3 nuove giocate dal caricamento. Ricarica per visualizzarle.',
            $component->instance()->getSubheading(),
        );
    }

    public function test_subheading_is_null_when_no_new_plays(): void
    {
        Carbon::setTestNow('2026-04-23 10:00:00');

        Play::factory()->create([
            'user_id' => $this->user->id,
            'played_at' => now(),
        ]);

        $component = Livewire::test(ListPlays::class);

        $this->assertNull($component->instance()->getSubheading());
    }

    public function test_reload_action_emits_reload_js(): void
    {
        Carbon::setTestNow('2026-04-23 10:00:00');

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
