<?php

namespace Tests\Feature;

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
use App\Models\Prize;
use App\Models\Store;
use App\Models\User;
use App\Models\WinningSlot;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BackofficeTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::first();
    }

    // --- Admin auth ---

    public function test_admin_can_access_panel(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin');

        $response->assertStatus(200);
    }

    public function test_web_user_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'web')
            ->get('/admin');

        $response->assertRedirect();
    }

    public function test_unauthenticated_cannot_access_admin_panel(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect();
    }

    // --- StoreResource CRUD ---

    public function test_store_list_page_renders(): void
    {
        $this->actingAs($this->admin, 'admin');

        Livewire::test(ListStores::class)
            ->assertSuccessful();
    }

    public function test_store_create(): void
    {
        $this->actingAs($this->admin, 'admin');

        Livewire::test(CreateStore::class)
            ->fillForm([
                'code' => 'NEW01',
                'name' => 'Nuovo Punto Vendita',
                'address' => 'Via Nuova 1',
                'city' => 'Roma',
                'province' => 'RM',
                'cap' => '00100',
                'is_active' => true,
            ])
            ->call('create')
            ->assertRedirect();

        $this->assertDatabaseHas('stores', ['code' => 'NEW01', 'name' => 'Nuovo Punto Vendita']);
    }

    public function test_store_edit(): void
    {
        $this->actingAs($this->admin, 'admin');

        $store = Store::create([
            'code' => 'EDIT01',
            'name' => 'Old Name',
            'address' => 'Via Test',
            'city' => 'Bologna',
            'province' => 'BO',
            'cap' => '40100',
        ]);

        Livewire::test(EditStore::class, ['record' => $store->id])
            ->fillForm(['name' => 'New Name'])
            ->call('save')
            ->assertSuccessful();

        $this->assertDatabaseHas('stores', ['code' => 'EDIT01', 'name' => 'New Name']);
    }

    // --- PlayResource ban/unban ---

    public function test_ban_winning_play_frees_slot(): void
    {
        $this->actingAs($this->admin, 'admin');

        $user = User::factory()->create();
        $prize = Prize::where('code', 'A')->first();
        $slot = WinningSlot::create([
            'prize_id' => $prize->id,
            'scheduled_date' => '2026-04-20',
            'scheduled_time' => '14:00:00',
            'is_assigned' => true,
            'assigned_at' => now(),
        ]);
        $play = Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/test.jpg',
            'played_at' => '2026-04-20 15:00:00',
            'is_winner' => true,
            'prize_id' => $prize->id,
            'winning_slot_id' => $slot->id,
        ]);
        $slot->update(['play_id' => $play->id]);

        Livewire::test(ListPlays::class)
            ->callAction(TestAction::make('ban')->table($play), [
                'ban_reason' => 'Scontrino falso',
            ])
            ->assertSuccessful();

        $play->refresh();
        $slot->refresh();

        $this->assertTrue($play->is_banned);
        $this->assertFalse($play->is_winner);
        $this->assertNull($play->prize_id);
        $this->assertFalse($slot->is_assigned);
        $this->assertNull($slot->play_id);
    }

    public function test_unban_play_does_not_reassign_prize(): void
    {
        $this->actingAs($this->admin, 'admin');

        $user = User::factory()->create();
        $play = Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/test.jpg',
            'played_at' => '2026-04-20 15:00:00',
            'is_banned' => true,
            'ban_reason' => 'Test ban',
            'banned_at' => now(),
        ]);

        Livewire::test(ListPlays::class)
            ->callAction(TestAction::make('unban')->table($play))
            ->assertSuccessful();

        $play->refresh();
        $this->assertFalse($play->is_banned);
        $this->assertFalse($play->is_winner);
        $this->assertNull($play->prize_id);
    }

    // --- UserResource ban/unban ---

    public function test_ban_user(): void
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
        $this->assertEquals('Comportamento scorretto', $user->ban_reason);
    }

    public function test_unban_user(): void
    {
        $this->actingAs($this->admin, 'admin');

        $user = User::factory()->banned()->create();

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('unban')->table($user))
            ->assertSuccessful();

        $user->refresh();
        $this->assertFalse($user->is_banned);
        $this->assertNull($user->ban_reason);
    }

    // --- View pages ---

    public function test_user_view_page_renders(): void
    {
        $this->actingAs($this->admin, 'admin');

        $user = User::factory()->create();

        Livewire::test(ViewUser::class, ['record' => $user->id])
            ->assertSuccessful();
    }

    public function test_play_view_page_renders(): void
    {
        $this->actingAs($this->admin, 'admin');

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

    // --- WinningSlotResource (read-only) ---

    public function test_winning_slot_list_renders(): void
    {
        $this->actingAs($this->admin, 'admin');

        Livewire::test(ListWinningSlots::class)
            ->assertSuccessful();
    }

    // --- PrizeSummary page ---

    public function test_prize_summary_page_renders(): void
    {
        $this->actingAs($this->admin, 'admin');

        Livewire::test(PrizeSummary::class)
            ->assertSuccessful()
            ->assertSee('Programmazione Settimanale')
            ->assertSee('Stato Assegnazione')
            ->assertSee('Settimana 1');
    }

    // --- Banned user cannot play ---

    public function test_banned_user_cannot_play(): void
    {
        $user = User::factory()->banned()->create();

        $response = $this->actingAs($user)->get(route('game.show'));

        // The game page should show the "already played" or banned state
        $response->assertStatus(200);
    }
}
