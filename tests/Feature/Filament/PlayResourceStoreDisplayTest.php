<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\PlayResource\Pages\ViewPlay;
use App\Models\Admin;
use App\Models\Play;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlayResourceStoreDisplayTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::first();
        $this->actingAs($this->admin, 'admin');
    }

    public function test_view_page_shows_full_store_data(): void
    {
        $store = Store::factory()->create([
            'code' => 'STOREX',
            'name' => 'Bar Central',
            'sign_name' => 'Central',
            'address' => 'Via Liberta 1',
            'cap' => '90100',
            'city' => 'Palermo',
            'province' => 'PA',
        ]);

        $user = User::factory()->create();
        $play = Play::create([
            'user_id' => $user->id,
            'store_code' => $store->code,
            'receipt_image' => 'receipts/x.jpg',
            'played_at' => now(),
        ]);

        Livewire::test(ViewPlay::class, ['record' => $play->id])
            ->assertSee('STOREX')
            ->assertSee('Central')
            ->assertSee('Via Liberta 1')
            ->assertSee('90100')
            ->assertSee('Palermo')
            ->assertSee('PA');
    }

    public function test_view_page_handles_orphan_store_code(): void
    {
        $user = User::factory()->create();
        $play = Play::create([
            'user_id' => $user->id,
            'store_code' => 'ORPHAN999',
            'receipt_image' => 'receipts/o.jpg',
            'played_at' => now(),
        ]);

        Livewire::test(ViewPlay::class, ['record' => $play->id])
            ->assertSuccessful()
            ->assertSee('ORPHAN999');
    }
}
