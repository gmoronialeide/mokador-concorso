<?php

namespace Tests\Feature\Filament;

use App\Enums\PlayStatus;
use App\Filament\Resources\PlayResource;
use App\Filament\Resources\PlayResource\Pages\ListPlays;
use App\Models\Admin;
use App\Models\Play;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlayResourceReceiptActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Admin::first(), 'admin');
    }

    public function test_get_filtered_ordered_ids_returns_all_plays_ordered_by_played_at_desc(): void
    {
        $play1 = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
            'played_at' => now()->subMinutes(5),
        ]);
        $play2 = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
            'played_at' => now()->subMinutes(2),
        ]);
        $play3 = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
            'played_at' => now()->subMinutes(1),
        ]);

        $component = Livewire::test(ListPlays::class);

        $ids = PlayResource::getFilteredOrderedIds($component->instance());

        $this->assertSame([$play3->id, $play2->id, $play1->id], $ids);
    }

    public function test_get_filtered_ordered_ids_returns_provided_ids_sanitized(): void
    {
        $component = Livewire::test(ListPlays::class);

        $ids = PlayResource::getFilteredOrderedIds(
            $component->instance(),
            ['7', 12, 'abc', 99, null, '0'],
        );

        $this->assertSame([7, 12, 99, 0], $ids);
    }

    public function test_receipt_action_uses_provided_ids_argument_instead_of_recomputing(): void
    {
        $play1 = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
            'played_at' => now()->subMinutes(5),
        ]);
        $play2 = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
            'played_at' => now()->subMinutes(2),
        ]);

        $component = Livewire::test(ListPlays::class)
            ->mountAction(
                TestAction::make('receipt')
                    ->table($play1->getKey())
                    ->arguments(['ids' => [9999, $play1->id, 7777]])
            );

        $mountedAction = $component->instance()->getMountedAction();

        $this->assertSame([9999, $play1->id, 7777], $mountedAction->getArguments()['ids'] ?? null);

        $content = $mountedAction->getModalContent();
        $html = $content->render();

        $this->assertStringContainsString('Scontrino 2 / 3', $html);
    }

    public function test_receipt_action_modal_renders_with_ids_and_counter(): void
    {
        $play1 = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
            'played_at' => now()->subMinutes(5),
        ]);
        $play2 = Play::factory()->create([
            'user_id' => User::factory(),
            'status' => PlayStatus::Pending,
            'played_at' => now()->subMinutes(2),
        ]);

        $component = Livewire::test(ListPlays::class)
            ->mountTableAction('receipt', $play1->getKey());

        $mountedAction = $component->instance()->getMountedAction();
        $this->assertNotNull($mountedAction);

        $content = $mountedAction->getModalContent();
        $html = is_object($content) && method_exists($content, 'render')
            ? $content->render()
            : (string) $content;

        $this->assertStringContainsString('Scontrino 2 / 2', $html);
        $this->assertStringContainsString((string) $play1->id, $html);
    }
}
