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
}
