<?php

namespace Tests\Feature;

use App\Models\Play;
use App\Models\Prize;
use App\Models\Store;
use App\Models\User;
use App\Models\WinningSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GameControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();

        // La migrazione seed_winning_slots pre-genera slot; li eliminiamo per controllare lo scenario
        WinningSlot::query()->delete();
    }

    private function createVerifiedUser(array $overrides = []): User
    {
        return User::factory()->create($overrides);
    }

    private function createActiveStore(string $code = 'STORE01'): Store
    {
        return Store::create([
            'code' => $code,
            'name' => 'Test Store',
            'sign_name' => 'Test Store',
            'vat_number' => '12345678901',
            'address' => 'Via Test 1',
            'city' => 'Bologna',
            'province' => 'BO',
            'cap' => '40100',
            'is_active' => true,
        ]);
    }

    private function validPlayData(?int $storeId = null): array
    {
        return [
            'store_id' => $storeId ?? Store::where('code', 'STORE01')->value('id'),
            'receipt' => UploadedFile::fake()->image('receipt.jpg', 800, 600)->size(2048),
        ];
    }

    // --- Play page access ---

    public function test_play_page_shows_for_authenticated_verified_user(): void
    {
        Carbon::setTestNow('2026-04-25 10:00:00');
        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)->get(route('game.show'));

        $response->assertStatus(200);
    }

    // --- Valid play ---

    public function test_play_with_valid_jpg(): void
    {
        Carbon::setTestNow('2026-04-25 10:00:00');
        $user = $this->createVerifiedUser();
        $this->createActiveStore();

        $response = $this->actingAs($user)->post(route('game.play'), $this->validPlayData());

        $response->assertRedirect();
        $this->assertDatabaseHas('plays', [
            'user_id' => $user->id,
            'store_code' => 'STORE01',
        ]);
    }

    public function test_play_persists_store_id_and_store_code(): void
    {
        Carbon::setTestNow('2026-04-25 10:00:00');
        $user = $this->createVerifiedUser();
        $store = $this->createActiveStore();

        $this->actingAs($user)->post(route('game.play'), $this->validPlayData());

        $play = Play::where('user_id', $user->id)->first();
        $this->assertNotNull($play);
        $this->assertSame($store->id, $play->store_id);
        $this->assertSame($store->code, $play->store_code);
    }

    public function test_play_with_valid_png(): void
    {
        Carbon::setTestNow('2026-04-25 10:00:00');
        $user = $this->createVerifiedUser();
        $this->createActiveStore();

        $data = $this->validPlayData();
        $data['receipt'] = UploadedFile::fake()->image('receipt.png', 800, 600)->size(2048);

        $response = $this->actingAs($user)->post(route('game.play'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('plays', ['user_id' => $user->id]);
    }

    // --- Validation errors ---

    public function test_play_file_too_large(): void
    {
        Carbon::setTestNow('2026-04-25 10:00:00');
        $user = $this->createVerifiedUser();
        $this->createActiveStore();

        $data = $this->validPlayData();
        $data['receipt'] = UploadedFile::fake()->image('big.jpg')->size(7000); // > 6MB

        $response = $this->actingAs($user)->post(route('game.play'), $data);

        $response->assertSessionHasErrors('receipt');
    }

    public function test_play_non_image_file(): void
    {
        Carbon::setTestNow('2026-04-25 10:00:00');
        $user = $this->createVerifiedUser();
        $this->createActiveStore();

        $data = $this->validPlayData();
        $data['receipt'] = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($user)->post(route('game.play'), $data);

        $response->assertSessionHasErrors('receipt');
    }

    public function test_play_nonexistent_store(): void
    {
        Carbon::setTestNow('2026-04-25 10:00:00');
        $user = $this->createVerifiedUser();
        // No store created

        $response = $this->actingAs($user)->post(route('game.play'), [
            'store_id' => 99999,
            'receipt' => UploadedFile::fake()->image('receipt.jpg', 800, 600)->size(2048),
        ]);

        $response->assertSessionHasErrors('store_id');
    }

    public function test_play_inactive_store(): void
    {
        Carbon::setTestNow('2026-04-25 10:00:00');
        $user = $this->createVerifiedUser();
        $inactiveStore = Store::create([
            'code' => 'CLOSED01',
            'name' => 'Closed Store',
            'sign_name' => 'Closed Store',
            'vat_number' => '12345678902',
            'address' => 'Via Test 1',
            'city' => 'Bologna',
            'province' => 'BO',
            'cap' => '40100',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->post(route('game.play'), [
            'store_id' => $inactiveStore->id,
            'receipt' => UploadedFile::fake()->image('receipt.jpg', 800, 600)->size(2048),
        ]);

        $response->assertSessionHasErrors('store_id');
    }

    public function test_play_twice_same_day(): void
    {
        Carbon::setTestNow('2026-04-25 10:00:00');
        $user = $this->createVerifiedUser();
        $this->createActiveStore();

        // First play
        $this->actingAs($user)->post(route('game.play'), $this->validPlayData());

        // Second play same day
        $response = $this->actingAs($user)->post(route('game.play'), $this->validPlayData());

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(1, Play::where('user_id', $user->id)->count());
    }

    public function test_play_outside_contest_period(): void
    {
        Carbon::setTestNow('2026-03-01 10:00:00'); // Before contest start
        $user = $this->createVerifiedUser();
        $this->createActiveStore();

        $response = $this->actingAs($user)->post(route('game.play'), $this->validPlayData());

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('plays', ['user_id' => $user->id]);
    }

    public function test_play_after_contest_end(): void
    {
        Carbon::setTestNow('2026-06-01 10:00:00'); // After contest end
        $user = $this->createVerifiedUser();
        $this->createActiveStore();

        $response = $this->actingAs($user)->post(route('game.play'), $this->validPlayData());

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // --- Win/Lose flow ---

    public function test_winning_play_redirects_to_loading_with_won(): void
    {
        Carbon::setTestNow('2026-04-25 15:00:00');
        $user = $this->createVerifiedUser();
        $this->createActiveStore();

        $prize = Prize::where('code', 'A')->first();
        WinningSlot::create([
            'prize_id' => $prize->id,
            'scheduled_date' => '2026-04-25',
            'scheduled_time' => '14:00:00',
        ]);

        $response = $this->actingAs($user)->post(route('game.play'), $this->validPlayData());

        $response->assertRedirect(route('game.loading', ['result' => 'won']));
    }

    public function test_losing_play_redirects_to_loading_with_lost(): void
    {
        Carbon::setTestNow('2026-04-25 15:00:00');
        $user = $this->createVerifiedUser();
        $this->createActiveStore();
        // No winning slots

        $response = $this->actingAs($user)->post(route('game.play'), $this->validPlayData());

        $response->assertRedirect(route('game.loading', ['result' => 'lost']));
    }

    public function test_loading_page_renders(): void
    {
        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)->get(route('game.loading', ['result' => 'lost']));

        $response->assertStatus(200);
    }

    public function test_lost_page_renders(): void
    {
        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)->get(route('game.lost'));

        $response->assertStatus(200);
    }

    public function test_won_page_without_prize_shows_lost(): void
    {
        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)->get(route('game.won'));

        $response->assertStatus(200);
        $response->assertViewIs('game.lost');
    }
}
