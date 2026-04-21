<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\PlayResource\Pages\ListPlays;
use App\Models\Admin;
use App\Models\Play;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlayResourceAssignStoreTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::first();
        $this->actingAs($this->admin, 'admin');
    }

    public function test_admin_can_assign_store_id_from_matching_code(): void
    {
        $storeA = Store::factory()->create(['code' => 'DUP', 'is_active' => true]);
        $storeB = Store::factory()->create(['code' => 'DUP', 'is_active' => true]);

        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'store_code' => 'DUP',
            'store_id' => null,
        ]);

        Livewire::test(ListPlays::class)
            ->callTableAction('assign_store', $play, data: ['store_id' => $storeB->id])
            ->assertHasNoTableActionErrors();

        $this->assertSame($storeB->id, $play->fresh()->store_id);
        $this->assertSame('DUP', $play->fresh()->store_code);
    }

    public function test_assign_store_rejects_store_with_different_code(): void
    {
        Store::factory()->create(['code' => 'AAA']);
        $storeOther = Store::factory()->create(['code' => 'BBB']);

        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'store_code' => 'AAA',
            'store_id' => null,
        ]);

        Livewire::test(ListPlays::class)
            ->callTableAction('assign_store', $play, data: ['store_id' => $storeOther->id])
            ->assertHasTableActionErrors(['store_id']);

        $this->assertNull($play->fresh()->store_id);
    }

    public function test_assign_store_action_hidden_when_already_assigned(): void
    {
        $store = Store::factory()->create(['code' => 'SET']);
        $play = Play::factory()->forStore($store)->create([
            'user_id' => User::factory(),
        ]);

        Livewire::test(ListPlays::class)
            ->assertTableActionHidden('assign_store', $play);
    }

    public function test_filter_without_store_shows_only_null_plays(): void
    {
        $store = Store::factory()->create(['code' => 'HAS']);
        $withStore = Play::factory()->forStore($store)->create([
            'user_id' => User::factory(),
        ]);
        $withoutStore = Play::factory()->create([
            'user_id' => User::factory(),
            'store_code' => 'ORPHAN',
            'store_id' => null,
        ]);

        Livewire::test(ListPlays::class)
            ->filterTable('without_store')
            ->assertCanSeeTableRecords([$withoutStore])
            ->assertCanNotSeeTableRecords([$withStore]);
    }
}
