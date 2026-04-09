<?php

namespace Tests\Feature;

use App\Enums\PlayStatus;
use App\Filament\Pages\FinalDraw;
use App\Filament\Pages\PrizeSummary;
use App\Filament\Resources\PlayResource\Pages\ListPlays;
use App\Filament\Resources\PlayResource\Pages\ViewPlay;
use App\Filament\Resources\StoreResource\Pages\CreateStore;
use App\Filament\Resources\StoreResource\Pages\EditStore;
use App\Filament\Resources\StoreResource\Pages\ListStores;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Filament\Resources\WinningSlotResource\Pages\ListWinningSlots;
use App\Models\Admin;
use App\Models\Play;
use App\Models\Store;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotaioRoleTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Admin $notaio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::first();

        $this->notaio = Admin::create([
            'name' => 'Notaio Test',
            'email' => 'notaio@test.com',
            'password' => 'password',
            'role' => 'notaio',
        ]);
    }

    // -------------------------------------------------------
    // Notaio CANNOT access WinningSlotResource
    // -------------------------------------------------------

    public function test_notaio_cannot_access_winning_slots(): void
    {
        $this->actingAs($this->notaio, 'admin');

        Livewire::test(ListWinningSlots::class)
            ->assertForbidden();
    }

    // -------------------------------------------------------
    // Notaio CANNOT ban a user
    // -------------------------------------------------------

    public function test_notaio_cannot_ban_user(): void
    {
        $this->actingAs($this->notaio, 'admin');

        $user = User::factory()->create();

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('ban', $user);
    }

    // -------------------------------------------------------
    // Notaio CANNOT unban a user
    // -------------------------------------------------------

    public function test_notaio_cannot_unban_user(): void
    {
        $this->actingAs($this->notaio, 'admin');

        $user = User::factory()->banned()->create();

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('unban', $user);
    }

    // -------------------------------------------------------
    // Notaio CANNOT bulk ban users
    // -------------------------------------------------------

    public function test_notaio_cannot_bulk_ban_users(): void
    {
        $this->actingAs($this->notaio, 'admin');

        Livewire::test(ListUsers::class)
            ->assertTableBulkActionHidden('ban_selected');
    }

    // -------------------------------------------------------
    // Notaio CANNOT ban a play
    // -------------------------------------------------------

    public function test_notaio_cannot_ban_play(): void
    {
        $this->actingAs($this->notaio, 'admin');

        $user = User::factory()->create();
        $play = Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/test.jpg',
            'played_at' => now(),
        ]);

        Livewire::test(ListPlays::class)
            ->assertTableActionHidden('ban', $play);
    }

    // -------------------------------------------------------
    // Notaio CANNOT unban a play
    // -------------------------------------------------------

    public function test_notaio_cannot_unban_play(): void
    {
        $this->actingAs($this->notaio, 'admin');

        $user = User::factory()->create();
        $play = Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/test.jpg',
            'played_at' => now(),
            'status' => PlayStatus::Banned,
            'ban_reason' => 'Test ban',
            'banned_at' => now(),
        ]);

        Livewire::test(ListPlays::class)
            ->assertTableActionHidden('unban', $play);
    }

    // -------------------------------------------------------
    // Notaio CANNOT edit notes on a play
    // -------------------------------------------------------

    public function test_notaio_cannot_edit_play_notes(): void
    {
        $this->actingAs($this->notaio, 'admin');

        $user = User::factory()->create();
        $play = Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/test.jpg',
            'played_at' => now(),
        ]);

        Livewire::test(ListPlays::class)
            ->assertTableActionHidden('notes', $play);
    }

    // -------------------------------------------------------
    // Notaio CANNOT validate a play
    // -------------------------------------------------------

    public function test_notaio_cannot_validate_play(): void
    {
        $this->actingAs($this->notaio, 'admin');

        $user = User::factory()->create();
        $play = Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/test.jpg',
            'played_at' => now(),
            'status' => PlayStatus::Pending,
        ]);

        Livewire::test(ListPlays::class)
            ->assertTableActionHidden('validate', $play);
    }

    // -------------------------------------------------------
    // Notaio CANNOT create a store
    // -------------------------------------------------------

    public function test_notaio_cannot_create_store(): void
    {
        $this->actingAs($this->notaio, 'admin');

        Livewire::test(CreateStore::class)
            ->assertForbidden();
    }

    // -------------------------------------------------------
    // Notaio CANNOT edit a store
    // -------------------------------------------------------

    public function test_notaio_cannot_edit_store(): void
    {
        $this->actingAs($this->notaio, 'admin');

        $store = Store::create([
            'code' => 'EDIT01',
            'name' => 'Test Store',
            'sign_name' => 'Test Sign',
            'vat_number' => '12345678901',
            'address' => 'Via Test',
            'city' => 'Bologna',
            'province' => 'BO',
            'cap' => '40100',
        ]);

        Livewire::test(EditStore::class, ['record' => $store->id])
            ->assertForbidden();
    }

    // -------------------------------------------------------
    // Notaio CAN access the admin panel
    // -------------------------------------------------------

    public function test_notaio_can_access_admin_panel(): void
    {
        $response = $this->actingAs($this->notaio, 'admin')
            ->get('/admin');

        $response->assertStatus(200);
    }

    // -------------------------------------------------------
    // Notaio CAN view the list of plays
    // -------------------------------------------------------

    public function test_notaio_can_view_play_list(): void
    {
        $this->actingAs($this->notaio, 'admin');

        Livewire::test(ListPlays::class)
            ->assertSuccessful();
    }

    // -------------------------------------------------------
    // Notaio CAN view the list of users
    // -------------------------------------------------------

    public function test_notaio_can_view_user_list(): void
    {
        $this->actingAs($this->notaio, 'admin');

        Livewire::test(ListUsers::class)
            ->assertSuccessful();
    }

    // -------------------------------------------------------
    // Notaio CAN view user detail page
    // -------------------------------------------------------

    public function test_notaio_can_view_user_detail(): void
    {
        $this->actingAs($this->notaio, 'admin');

        $user = User::factory()->create();

        Livewire::test(ViewUser::class, ['record' => $user->id])
            ->assertSuccessful();
    }

    // -------------------------------------------------------
    // Notaio CAN view play detail page
    // -------------------------------------------------------

    public function test_notaio_can_view_play_detail(): void
    {
        $this->actingAs($this->notaio, 'admin');

        $user = User::factory()->create();
        $play = Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/test.jpg',
            'played_at' => now(),
        ]);

        Livewire::test(ViewPlay::class, ['record' => $play->id])
            ->assertSuccessful();
    }

    // -------------------------------------------------------
    // Notaio CAN view the list of stores
    // -------------------------------------------------------

    public function test_notaio_can_view_store_list(): void
    {
        $this->actingAs($this->notaio, 'admin');

        Livewire::test(ListStores::class)
            ->assertSuccessful();
    }

    // -------------------------------------------------------
    // Notaio CAN view the FinalDraw page
    // -------------------------------------------------------

    public function test_notaio_can_view_final_draw_page(): void
    {
        $this->actingAs($this->notaio, 'admin');

        Livewire::test(FinalDraw::class)
            ->assertSuccessful();
    }

    // -------------------------------------------------------
    // Notaio CAN view the PrizeSummary page
    // -------------------------------------------------------

    public function test_notaio_can_view_prize_summary_page(): void
    {
        $this->actingAs($this->notaio, 'admin');

        Livewire::test(PrizeSummary::class)
            ->assertSuccessful();
    }

    // -------------------------------------------------------
    // Admin regression: can still ban a user
    // -------------------------------------------------------

    public function test_admin_can_still_ban_user(): void
    {
        $this->actingAs($this->admin, 'admin');

        $user = User::factory()->create();

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('ban')->table($user), [
                'ban_reason' => 'Comportamento scorretto',
            ])
            ->assertSuccessful();

        $user->refresh();
        $this->assertTrue($user->is_banned);
    }

    // -------------------------------------------------------
    // Admin regression: can still access WinningSlots
    // -------------------------------------------------------

    public function test_admin_can_still_access_winning_slots(): void
    {
        $this->actingAs($this->admin, 'admin');

        Livewire::test(ListWinningSlots::class)
            ->assertSuccessful();
    }
}
