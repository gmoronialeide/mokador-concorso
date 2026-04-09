<?php

namespace Tests\Feature;

use App\Enums\PlayStatus;
use App\Models\Admin;
use App\Models\FinalDrawResult;
use App\Models\FinalPrize;
use App\Models\Play;
use App\Models\User;
use App\Services\FinalDrawService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalDrawServiceTest extends TestCase
{
    use RefreshDatabase;

    private FinalDrawService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FinalDrawService;
    }

    private function createAdmin(): Admin
    {
        return Admin::first();
    }

    private function createUserWithPlays(int $playsCount, bool $banned = false): User
    {
        $user = $banned
            ? User::factory()->banned()->create()
            : User::factory()->create();

        for ($i = 0; $i < $playsCount; $i++) {
            Play::create([
                'user_id' => $user->id,
                'store_code' => 'STORE01',
                'receipt_image' => 'receipts/test.jpg',
                'played_at' => now()->subDays($i),
                'is_winner' => false,
                'status' => PlayStatus::Validated,
            ]);
        }

        return $user;
    }

    public function test_eligible_users_excludes_banned_users(): void
    {
        $this->createUserWithPlays(5);
        $this->createUserWithPlays(3, banned: true);

        $eligible = $this->service->getEligibleUsers();

        $this->assertCount(1, $eligible);
    }

    public function test_eligible_users_excludes_users_with_only_banned_plays(): void
    {
        $user = User::factory()->create([
            'surname' => fake()->lastName(),
            'birth_date' => fake()->date('Y-m-d', '-18 years'),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'province' => 'RN',
            'cap' => '47900',
            'privacy_consent' => true,
            'is_banned' => false,
        ]);

        Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/test.jpg',
            'played_at' => now(),
            'is_winner' => false,
            'status' => PlayStatus::Banned,
        ]);

        $eligible = $this->service->getEligibleUsers();

        $this->assertCount(0, $eligible);
    }

    public function test_eligible_users_includes_instant_win_winners(): void
    {
        $user = User::factory()->create([
            'surname' => fake()->lastName(),
            'birth_date' => fake()->date('Y-m-d', '-18 years'),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'province' => 'RN',
            'cap' => '47900',
            'privacy_consent' => true,
            'is_banned' => false,
        ]);

        Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/test.jpg',
            'played_at' => now(),
            'is_winner' => true,
            'status' => PlayStatus::Validated,
        ]);

        $eligible = $this->service->getEligibleUsers();

        $this->assertCount(1, $eligible);
        $this->assertEquals($user->id, $eligible->first()->user_id);
    }

    public function test_eligible_users_excludes_already_drawn(): void
    {
        $admin = $this->createAdmin();

        // Create 13 users (need 12 min for full draw + 1 to test exclusion)
        for ($i = 0; $i < 13; $i++) {
            $this->createUserWithPlays(2);
        }

        $this->service->drawWinners($admin);

        $eligible = $this->service->getEligibleUsers();

        $this->assertCount(10, $eligible);
    }

    public function test_eligible_plays_count_is_correct(): void
    {
        $this->createUserWithPlays(7);

        $eligible = $this->service->getEligibleUsers();

        $this->assertEquals(7, $eligible->first()->eligible_plays_count);
    }

    public function test_eligible_plays_count_excludes_banned_plays(): void
    {
        $user = User::factory()->create([
            'surname' => fake()->lastName(),
            'birth_date' => fake()->date('Y-m-d', '-18 years'),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'province' => 'RN',
            'cap' => '47900',
            'privacy_consent' => true,
            'is_banned' => false,
        ]);

        for ($i = 0; $i < 5; $i++) {
            Play::create([
                'user_id' => $user->id,
                'store_code' => 'STORE01',
                'receipt_image' => 'receipts/test.jpg',
                'played_at' => now()->subDays($i),
                'is_winner' => false,
                'status' => PlayStatus::Validated,
            ]);
        }

        Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/test.jpg',
            'played_at' => now()->subDays(6),
            'is_winner' => false,
            'status' => PlayStatus::Banned,
        ]);

        $eligible = $this->service->getEligibleUsers();

        $this->assertEquals(5, $eligible->first()->eligible_plays_count);
    }

    public function test_draw_winners_creates_exactly_3_winners(): void
    {
        $admin = $this->createAdmin();

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(3);
        }

        $results = $this->service->drawWinners($admin);

        $this->assertCount(3, $results);

        foreach ($results as $result) {
            $this->assertEquals('winner', $result->role);
            $this->assertNull($result->substitute_position);
        }
    }

    public function test_draw_winners_assigns_to_correct_prizes(): void
    {
        $admin = $this->createAdmin();

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(2);
        }

        $results = $this->service->drawWinners($admin);
        $prizes = FinalPrize::orderBy('position')->get();

        $this->assertEquals($prizes[0]->id, $results[0]->final_prize_id);
        $this->assertEquals($prizes[1]->id, $results[1]->final_prize_id);
        $this->assertEquals($prizes[2]->id, $results[2]->final_prize_id);
    }

    public function test_draw_winners_updates_prize_drawn_at(): void
    {
        $admin = $this->createAdmin();

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(2);
        }

        $this->service->drawWinners($admin);

        $prizes = FinalPrize::all();
        foreach ($prizes as $prize) {
            $this->assertNotNull($prize->drawn_at);
            $this->assertEquals($admin->id, $prize->drawn_by);
        }
    }

    public function test_draw_winners_no_duplicate_users(): void
    {
        $admin = $this->createAdmin();

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(2);
        }

        $results = $this->service->drawWinners($admin);
        $userIds = array_map(fn ($r) => $r->user_id, $results);

        $this->assertCount(3, array_unique($userIds));
    }

    public function test_draw_winners_fails_if_already_drawn(): void
    {
        $admin = $this->createAdmin();

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(2);
        }

        $this->service->drawWinners($admin);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('già stati estratti');

        $this->service->drawWinners($admin);
    }

    public function test_draw_winners_fails_with_insufficient_users(): void
    {
        $admin = $this->createAdmin();

        for ($i = 0; $i < 5; $i++) {
            $this->createUserWithPlays(2);
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Servono almeno');

        $this->service->drawWinners($admin);
    }

    public function test_draw_substitutes_fails_without_winners(): void
    {
        $admin = $this->createAdmin();

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(2);
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Bisogna prima estrarre tutti i vincitori');

        $this->service->drawSubstitutes($admin);
    }

    public function test_draw_substitutes_creates_9_results(): void
    {
        $admin = $this->createAdmin();

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(3);
        }

        $this->service->drawWinners($admin);
        $results = $this->service->drawSubstitutes($admin);

        $this->assertCount(9, $results);

        foreach ($results as $result) {
            $this->assertEquals('substitute', $result->role);
            $this->assertNotNull($result->substitute_position);
            $this->assertContains($result->substitute_position, [1, 2, 3]);
        }
    }

    public function test_draw_substitutes_no_user_overlap_with_winners(): void
    {
        $admin = $this->createAdmin();

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(3);
        }

        $winnerResults = $this->service->drawWinners($admin);
        $substituteResults = $this->service->drawSubstitutes($admin);

        $winnerUserIds = array_map(fn ($r) => $r->user_id, $winnerResults);
        $substituteUserIds = array_map(fn ($r) => $r->user_id, $substituteResults);

        $this->assertEmpty(array_intersect($winnerUserIds, $substituteUserIds));
    }

    public function test_draw_substitutes_no_duplicate_users(): void
    {
        $admin = $this->createAdmin();

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(3);
        }

        $this->service->drawWinners($admin);
        $results = $this->service->drawSubstitutes($admin);

        $userIds = array_map(fn ($r) => $r->user_id, $results);

        $this->assertCount(9, array_unique($userIds));
    }

    public function test_draw_substitutes_fails_if_already_drawn(): void
    {
        $admin = $this->createAdmin();

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(3);
        }

        $this->service->drawWinners($admin);
        $this->service->drawSubstitutes($admin);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('già stati estratti');

        $this->service->drawSubstitutes($admin);
    }

    public function test_reset_substitutes_removes_only_substitutes(): void
    {
        $admin = $this->createAdmin();

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(3);
        }

        $this->service->drawWinners($admin);
        $this->service->drawSubstitutes($admin);

        $this->service->resetSubstitutes();

        $this->assertEquals(3, FinalDrawResult::where('role', 'winner')->count());
        $this->assertEquals(0, FinalDrawResult::where('role', 'substitute')->count());
    }

    public function test_reset_all_clears_everything(): void
    {
        $admin = $this->createAdmin();

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(3);
        }

        $this->service->drawWinners($admin);
        $this->service->drawSubstitutes($admin);

        $this->service->resetAll();

        $this->assertEquals(0, FinalDrawResult::count());

        foreach (FinalPrize::all() as $prize) {
            $this->assertNull($prize->drawn_at);
            $this->assertNull($prize->drawn_by);
        }
    }

    public function test_weighted_random_favors_users_with_more_plays(): void
    {
        $admin = $this->createAdmin();

        // User with 100 plays vs 14 users with 1 play each (need 15 total: 3 winners + 9 subs + margin)
        $heavyUser = $this->createUserWithPlays(100);

        for ($i = 0; $i < 14; $i++) {
            $this->createUserWithPlays(1);
        }

        // Run 50 draws and check if heavy user appears among winners
        $heavyUserWins = 0;
        $iterations = 50;

        for ($i = 0; $i < $iterations; $i++) {
            FinalDrawResult::query()->delete();
            FinalPrize::query()->update(['drawn_at' => null, 'drawn_by' => null]);

            $results = $this->service->drawWinners($admin);
            $winnerIds = array_map(fn ($r) => $r->user_id, $results);

            if (in_array($heavyUser->id, $winnerIds)) {
                $heavyUserWins++;
            }
        }

        // With 100/114 weight per slot ≈ 88%, ~99.8% chance to appear in 3 draws
        // With 50 iterations, expect nearly all. Use 30 as safe lower bound.
        $this->assertGreaterThan(30, $heavyUserWins, "Heavy user should win significantly more often. Won {$heavyUserWins}/{$iterations} times.");
    }

    public function test_total_plays_recorded_for_audit(): void
    {
        $admin = $this->createAdmin();

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(rand(1, 10));
        }

        $this->service->drawWinners($admin);

        foreach (FinalDrawResult::all() as $result) {
            $expectedCount = Play::where('user_id', $result->user_id)
                ->whereNot('status', PlayStatus::Banned)
                ->count();
            $this->assertEquals($expectedCount, $result->total_plays);
        }
    }
}
