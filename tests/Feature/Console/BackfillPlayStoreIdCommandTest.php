<?php

namespace Tests\Feature\Console;

use App\Models\Play;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillPlayStoreIdCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_active_store_is_applied_directly(): void
    {
        $store = Store::factory()->create(['code' => 'PV001', 'is_active' => true]);
        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'store_code' => 'PV001',
            'store_id' => null,
        ]);

        $this->artisan('plays:backfill-store-id')->assertSuccessful();

        $this->assertSame($store->id, $play->fresh()->store_id);
    }

    public function test_single_inactive_store_is_applied_directly(): void
    {
        $store = Store::factory()->create(['code' => 'PV002', 'is_active' => false]);
        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'store_code' => 'PV002',
            'store_id' => null,
        ]);

        $this->artisan('plays:backfill-store-id')->assertSuccessful();

        $this->assertSame($store->id, $play->fresh()->store_id);
    }

    public function test_multiple_stores_with_active_picks_oldest_active(): void
    {
        $inactiveOlder = Store::factory()->create(['code' => 'PV003', 'is_active' => false]);
        $activeMiddle = Store::factory()->create(['code' => 'PV003', 'is_active' => true]);
        $activeNewer = Store::factory()->create(['code' => 'PV003', 'is_active' => true]);
        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'store_code' => 'PV003',
            'store_id' => null,
        ]);

        $this->artisan('plays:backfill-store-id')->assertSuccessful();

        $fresh = $play->fresh();
        $this->assertSame($activeMiddle->id, $fresh->store_id);
        $this->assertNotSame($inactiveOlder->id, $fresh->store_id);
        $this->assertNotSame($activeNewer->id, $fresh->store_id);
    }

    public function test_multiple_stores_without_active_leaves_null(): void
    {
        Store::factory()->create(['code' => 'PV004', 'is_active' => false]);
        Store::factory()->create(['code' => 'PV004', 'is_active' => false]);
        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'store_code' => 'PV004',
            'store_id' => null,
        ]);

        $this->artisan('plays:backfill-store-id')->assertSuccessful();

        $this->assertNull($play->fresh()->store_id);
    }

    public function test_no_store_for_code_leaves_null(): void
    {
        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'store_code' => 'MISSING',
            'store_id' => null,
        ]);

        $this->artisan('plays:backfill-store-id')->assertSuccessful();

        $this->assertNull($play->fresh()->store_id);
    }

    public function test_rerun_is_idempotent(): void
    {
        Store::factory()->create(['code' => 'PV005', 'is_active' => true]);
        $otherStore = Store::factory()->create(['code' => 'PV005', 'is_active' => true]);
        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'store_code' => 'PV005',
            'store_id' => $otherStore->id,
        ]);

        $this->artisan('plays:backfill-store-id')->assertSuccessful();

        $this->assertSame($otherStore->id, $play->fresh()->store_id);
    }
}
