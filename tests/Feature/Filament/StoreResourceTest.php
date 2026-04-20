<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\StoreResource;
use App\Filament\Resources\StoreResource\Pages\CreateStore;
use App\Filament\Resources\StoreResource\Pages\EditStore;
use App\Models\Admin;
use App\Models\Play;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StoreResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Admin::first(), 'admin');
    }

    public function test_can_deactivate_store_with_duplicate_code(): void
    {
        $code = 'DUP001';
        $target = Store::factory()->create(['code' => $code, 'is_active' => true]);
        Store::factory()->create(['code' => $code, 'is_active' => true]);

        Livewire::test(EditStore::class, ['record' => $target->getRouteKey()])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($target->fresh()->is_active);
    }

    public function test_can_save_store_with_duplicate_code_on_create(): void
    {
        Store::factory()->create(['code' => 'DUP002']);

        Livewire::test(CreateStore::class)
            ->fillForm([
                'code' => 'DUP002',
                'name' => 'Secondo punto vendita',
                'sign_name' => 'Insegna 2',
                'vat_number' => '12345678901',
                'address' => 'Via Roma 1',
                'city' => 'Milano',
                'province' => 'MI',
                'cap' => '20100',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(2, Store::where('code', 'DUP002')->count());
    }

    public function test_cannot_delete_store_with_linked_plays(): void
    {
        $store = Store::factory()->create(['code' => 'LINKED01']);
        $user = User::factory()->create();
        Play::create([
            'user_id' => $user->id,
            'store_code' => $store->code,
            'receipt_image' => 'receipts/x.jpg',
            'played_at' => now(),
        ]);

        $this->assertFalse(StoreResource::canDelete($store));
    }

    public function test_can_delete_store_without_plays(): void
    {
        $store = Store::factory()->create(['code' => 'FREE01']);

        $this->assertTrue(StoreResource::canDelete($store));
    }

    public function test_code_still_required(): void
    {
        Livewire::test(CreateStore::class)
            ->fillForm([
                'code' => '',
                'name' => 'X',
                'sign_name' => 'X',
                'vat_number' => '123',
                'address' => 'X',
                'city' => 'X',
                'province' => 'MI',
                'cap' => '20100',
            ])
            ->call('create')
            ->assertHasFormErrors(['code' => 'required']);
    }
}
