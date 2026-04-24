<?php

namespace Tests\Feature;

use App\Filament\Widgets\TopStoresChartWidget;
use App\Models\Admin;
use App\Models\Play;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TopStoresChartWidgetTest extends TestCase
{
    use RefreshDatabase;

    private function createStore(string $code, string $name, ?string $signName = null): Store
    {
        return Store::create([
            'code' => $code,
            'name' => $name,
            'sign_name' => $signName ?? $name,
            'vat_number' => 'IT'.str_pad($code, 9, '0'),
            'address' => 'Via Test 1',
            'city' => 'Bologna',
            'province' => 'BO',
            'cap' => '40100',
            'is_active' => true,
        ]);
    }

    private function createPlays(User $user, Store $store, int $count): void
    {
        Play::factory()
            ->count($count)
            ->forStore($store)
            ->create([
                'user_id' => $user->id,
                'played_at' => now(),
            ]);
    }

    public function test_renders_top_5_stores_widget(): void
    {
        $user = User::factory()->create();
        $admin = Admin::first() ?? Admin::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        $s01 = $this->createStore('S01', 'Bar Centrale');
        $s02 = $this->createStore('S02', 'Caffè Roma');
        $s03 = $this->createStore('S03', 'Bar Sport');
        $s04 = $this->createStore('S04', 'Mokador Point');
        $s05 = $this->createStore('S05', 'Bar Stazione');
        $s06 = $this->createStore('S06', 'Bar Piazza');

        $this->createPlays($user, $s01, 10);
        $this->createPlays($user, $s02, 8);
        $this->createPlays($user, $s03, 6);
        $this->createPlays($user, $s04, 4);
        $this->createPlays($user, $s05, 2);
        $this->createPlays($user, $s06, 1);

        Livewire::actingAs($admin, 'admin')
            ->test(TopStoresChartWidget::class)
            ->assertCanSeeTableRecords(
                Store::whereIn('code', ['S01', 'S02', 'S03', 'S04', 'S05'])->get()
            )
            ->assertCanNotSeeTableRecords(
                Store::where('code', 'S06')->get()
            );
    }

    public function test_shows_empty_when_no_plays(): void
    {
        $admin = Admin::first() ?? Admin::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        Livewire::actingAs($admin, 'admin')
            ->test(TopStoresChartWidget::class)
            ->assertSuccessful();
    }

    public function test_store_has_plays_relationship(): void
    {
        $user = User::factory()->create();
        $store = $this->createStore('S01', 'Bar Centrale');
        $this->createPlays($user, $store, 3);

        $this->assertEquals(3, $store->plays()->count());
    }

    public function test_plays_without_store_id_are_not_counted(): void
    {
        $user = User::factory()->create();
        $admin = Admin::first() ?? Admin::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        $store = $this->createStore('S01', 'Bar Centrale');

        Play::factory()->count(3)->create([
            'user_id' => $user->id,
            'store_id' => null,
            'store_code' => 'S01',
            'played_at' => now(),
        ]);

        Livewire::actingAs($admin, 'admin')
            ->test(TopStoresChartWidget::class)
            ->assertCanNotSeeTableRecords(Store::where('code', 'S01')->get());
    }
}
