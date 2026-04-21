<?php

namespace Tests\Unit;

use App\Models\Play;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_relation_uses_store_id(): void
    {
        $storeA = Store::factory()->create(['code' => 'SHARED']);
        $storeB = Store::factory()->create(['code' => 'SHARED']);

        $play = Play::factory()->create([
            'user_id' => User::factory(),
            'store_id' => $storeB->id,
            'store_code' => 'SHARED',
        ]);

        $this->assertSame($storeB->id, $play->store->id);
        $this->assertNotSame($storeA->id, $play->store->id);
    }
}
