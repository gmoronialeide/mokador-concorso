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

    public function test_eligible_plays_excludes_banned_users(): void
    {
        $this->createUserWithPlays(5);
        $this->createUserWithPlays(3, banned: true);

        $eligible = $this->service->getEligiblePlays();

        $this->assertCount(5, $eligible);
        $this->assertEquals(1, $eligible->unique('user_id')->count());
    }

    public function test_eligible_plays_excludes_banned_plays(): void
    {
        $user = User::factory()->create();

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

        $eligible = $this->service->getEligiblePlays();

        $this->assertCount(5, $eligible);
    }

    public function test_eligible_plays_excludes_users_with_only_banned_plays(): void
    {
        $user = User::factory()->create();

        Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/test.jpg',
            'played_at' => now(),
            'is_winner' => false,
            'status' => PlayStatus::Banned,
        ]);

        $eligible = $this->service->getEligiblePlays();

        $this->assertCount(0, $eligible);
    }

    public function test_eligible_plays_includes_instant_win_winners(): void
    {
        $user = User::factory()->create();

        Play::create([
            'user_id' => $user->id,
            'store_code' => 'STORE01',
            'receipt_image' => 'receipts/test.jpg',
            'played_at' => now(),
            'is_winner' => true,
            'status' => PlayStatus::Validated,
        ]);

        $eligible = $this->service->getEligiblePlays();

        $this->assertCount(1, $eligible);
        $this->assertEquals($user->id, $eligible->first()->user_id);
    }

    public function test_eligible_plays_excludes_already_drawn_users(): void
    {
        $admin = $this->createAdmin();

        for ($i = 0; $i < 13; $i++) {
            $this->createUserWithPlays(2);
        }

        $this->service->drawWinners($admin);

        $eligible = $this->service->getEligiblePlays();

        // 13 users * 2 plays = 26 plays total, 3 winners removed = 10 users * 2 plays = 20
        $this->assertCount(20, $eligible);
        $this->assertEquals(10, $eligible->unique('user_id')->count());
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

    public function test_draw_result_has_play_id(): void
    {
        $admin = $this->createAdmin();

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(3);
        }

        $this->service->drawWinners($admin);

        foreach (FinalDrawResult::all() as $result) {
            $this->assertNotNull($result->play_id);
            $this->assertTrue(Play::where('id', $result->play_id)->exists());
        }
    }

    public function test_drawn_play_belongs_to_correct_user(): void
    {
        $admin = $this->createAdmin();

        for ($i = 0; $i < 15; $i++) {
            $this->createUserWithPlays(3);
        }

        $this->service->drawWinners($admin);
        $this->service->drawSubstitutes($admin);

        foreach (FinalDrawResult::with('play')->get() as $result) {
            $this->assertEquals($result->user_id, $result->play->user_id);
        }
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

    public function test_each_play_is_equiprobable_ticket(): void
    {
        $admin = $this->createAdmin();

        // User A with 10 plays, User B with 1 play
        // + 13 other users with 1 play each (need 12 min for draw)
        $userA = $this->createUserWithPlays(10);
        $userB = $this->createUserWithPlays(1);

        for ($i = 0; $i < 13; $i++) {
            $this->createUserWithPlays(1);
        }

        // Run 100 draws, count how often User A wins
        $userAWins = 0;
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            FinalDrawResult::query()->delete();
            FinalPrize::query()->update(['drawn_at' => null, 'drawn_by' => null]);

            $results = $this->service->drawWinners($admin);
            $winnerIds = array_map(fn ($r) => $r->user_id, $results);

            if (in_array($userA->id, $winnerIds)) {
                $userAWins++;
            }
        }

        // Total plays = 10 + 1 + 13 = 24 tickets
        // User A has 10/24 ≈ 42% chance per slot, ~83% to appear in 3 draws
        // Should win significantly more than User B who has 1/24 ≈ 4%
        $this->assertGreaterThan(40, $userAWins, "User with 10 plays should win often. Won {$userAWins}/{$iterations} times.");
    }
}
