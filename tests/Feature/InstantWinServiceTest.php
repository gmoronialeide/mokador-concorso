<?php

namespace Tests\Feature;

use App\Models\Play;
use App\Models\Prize;
use App\Models\User;
use App\Models\WinningSlot;
use App\Services\InstantWinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InstantWinServiceTest extends TestCase
{
    use RefreshDatabase;

    private InstantWinService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new InstantWinService();
    }

    private function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Mario',
            'surname' => 'Rossi',
            'birth_date' => '1990-01-01',
            'email' => 'mario' . uniqid() . '@test.it',
            'phone' => '3331234567',
            'address' => 'Via Roma 1',
            'city' => 'Bologna',
            'province' => 'BO',
            'cap' => '40100',
            'password' => 'password',
            'privacy_consent' => true,
        ], $overrides));
    }

    private function createPlay(User $user, string $storeCode = 'STORE01', ?Carbon $playedAt = null): Play
    {
        return Play::create([
            'user_id' => $user->id,
            'store_code' => $storeCode,
            'receipt_image' => 'receipts/test.jpg',
            'played_at' => $playedAt ?? Carbon::now(),
        ]);
    }

    private function createSlot(string $prizeCode, string $date, string $time): WinningSlot
    {
        $prize = Prize::where('code', $prizeCode)->first();

        return WinningSlot::create([
            'prize_id' => $prize->id,
            'scheduled_date' => $date,
            'scheduled_time' => $time,
        ]);
    }

    // --- Test vincita base ---

    public function test_wins_when_slot_available_and_time_passed(): void
    {
        Carbon::setTestNow('2026-04-20 15:00:00');

        $this->createSlot('A', '2026-04-20', '14:00:00');
        $user = $this->createUser();
        $play = $this->createPlay($user);

        $prize = $this->service->attempt($play);

        $this->assertNotNull($prize);
        $this->assertEquals('A', $prize->code);

        $play->refresh();
        $this->assertTrue($play->is_winner);
        $this->assertNotNull($play->prize_id);
        $this->assertNotNull($play->winning_slot_id);
    }

    public function test_no_win_when_no_slot_available(): void
    {
        Carbon::setTestNow('2026-04-20 10:00:00');

        $user = $this->createUser();
        $play = $this->createPlay($user);

        $prize = $this->service->attempt($play);

        $this->assertNull($prize);

        $play->refresh();
        $this->assertFalse($play->is_winner);
    }

    public function test_no_win_when_slot_time_not_yet_reached(): void
    {
        Carbon::setTestNow('2026-04-20 10:00:00');

        $this->createSlot('A', '2026-04-20', '14:00:00');
        $user = $this->createUser();
        $play = $this->createPlay($user);

        $prize = $this->service->attempt($play);

        $this->assertNull($prize);
    }

    // --- Test regola ore 12 ---

    public function test_rule_12_assigns_unassigned_slot_after_noon(): void
    {
        Carbon::setTestNow('2026-04-20 13:00:00');

        // Slot schedulato per le 18:00, normalmente non assegnabile alle 13
        $this->createSlot('B', '2026-04-20', '18:00:00');
        $user = $this->createUser();
        $play = $this->createPlay($user);

        $prize = $this->service->attempt($play);

        $this->assertNotNull($prize, 'Dopo le 12, la regola ore 12 deve assegnare lo slot');
        $this->assertEquals('B', $prize->code);
    }

    public function test_rule_12_does_not_apply_before_noon(): void
    {
        Carbon::setTestNow('2026-04-20 11:00:00');

        $this->createSlot('B', '2026-04-20', '18:00:00');
        $user = $this->createUser();
        $play = $this->createPlay($user);

        $prize = $this->service->attempt($play);

        $this->assertNull($prize, 'Prima delle 12, slot con ora futura non deve essere assegnato');
    }

    // --- Test vincolo punto vendita ---

    public function test_store_cannot_win_twice_same_week(): void
    {
        Carbon::setTestNow('2026-04-20 15:00:00');

        $this->createSlot('A', '2026-04-20', '14:00:00');
        $this->createSlot('B', '2026-04-20', '14:30:00');

        // Primo utente dallo stesso PV vince
        $user1 = $this->createUser();
        $play1 = $this->createPlay($user1, 'STORE01');
        $prize1 = $this->service->attempt($play1);
        $this->assertNotNull($prize1);

        // Secondo utente dallo stesso PV non vince
        $user2 = $this->createUser();
        $play2 = $this->createPlay($user2, 'STORE01');
        $prize2 = $this->service->attempt($play2);
        $this->assertNull($prize2, 'Stesso PV non deve vincere due volte nella stessa settimana');
    }

    public function test_store_constraint_slot_remains_available_for_others(): void
    {
        Carbon::setTestNow('2026-04-20 15:00:00');

        $this->createSlot('A', '2026-04-20', '14:00:00');
        $this->createSlot('B', '2026-04-20', '14:30:00');

        // PV STORE01 vince il primo slot
        $user1 = $this->createUser();
        $play1 = $this->createPlay($user1, 'STORE01');
        $this->service->attempt($play1);

        // PV STORE01 non vince (vincolo PV)
        $user2 = $this->createUser();
        $play2 = $this->createPlay($user2, 'STORE01');
        $this->service->attempt($play2);

        // PV STORE02 vince lo slot rimasto
        $user3 = $this->createUser();
        $play3 = $this->createPlay($user3, 'STORE02');
        $prize3 = $this->service->attempt($play3);

        $this->assertNotNull($prize3, 'Slot deve restare disponibile per altri PV');
        $this->assertEquals('B', $prize3->code);
    }

    public function test_different_stores_can_win_same_week(): void
    {
        Carbon::setTestNow('2026-04-20 15:00:00');

        $this->createSlot('A', '2026-04-20', '14:00:00');
        $this->createSlot('B', '2026-04-20', '14:30:00');

        $user1 = $this->createUser();
        $play1 = $this->createPlay($user1, 'STORE01');
        $prize1 = $this->service->attempt($play1);
        $this->assertNotNull($prize1);

        $user2 = $this->createUser();
        $play2 = $this->createPlay($user2, 'STORE02');
        $prize2 = $this->service->attempt($play2);
        $this->assertNotNull($prize2, 'PV diversi possono vincere nella stessa settimana');
    }

    public function test_store_can_win_in_different_weeks(): void
    {
        // Settimana 1: STORE01 vince
        Carbon::setTestNow('2026-04-20 15:00:00');
        $this->createSlot('A', '2026-04-20', '14:00:00');

        $user1 = $this->createUser();
        $play1 = $this->createPlay($user1, 'STORE01');
        $prize1 = $this->service->attempt($play1);
        $this->assertNotNull($prize1);

        // Settimana 2: STORE01 può vincere di nuovo
        Carbon::setTestNow('2026-04-27 15:00:00');
        $this->createSlot('B', '2026-04-27', '14:00:00');

        $user2 = $this->createUser();
        $play2 = $this->createPlay($user2, 'STORE01', Carbon::parse('2026-04-27 15:00:00'));
        $prize2 = $this->service->attempt($play2);
        $this->assertNotNull($prize2, 'Stesso PV deve poter vincere in settimane diverse');
    }

    // --- Test regola ore 12 + vincolo PV ---

    public function test_rule_12_respects_store_constraint(): void
    {
        Carbon::setTestNow('2026-04-20 13:00:00');

        $this->createSlot('A', '2026-04-20', '18:00:00');

        // STORE01 vince prima (slot precedente)
        $slotEarly = $this->createSlot('B', '2026-04-20', '10:00:00');
        $user1 = $this->createUser();
        $play1 = $this->createPlay($user1, 'STORE01');

        // Assegna manualmente la vincita del primo slot
        $slotEarly->update(['is_assigned' => true, 'play_id' => $play1->id, 'assigned_at' => now()]);
        $play1->update(['is_winner' => true, 'prize_id' => $slotEarly->prize_id, 'winning_slot_id' => $slotEarly->id]);

        // STORE01 tenta ancora dopo le 12 — deve fallire per vincolo PV
        $user2 = $this->createUser();
        $play2 = $this->createPlay($user2, 'STORE01');
        $prize2 = $this->service->attempt($play2);

        $this->assertNull($prize2, 'Regola ore 12 deve rispettare vincolo PV');

        // Lo slot ore 18 deve essere ancora disponibile
        $remainingSlot = WinningSlot::where('scheduled_time', '18:00:00')->first();
        $this->assertFalse($remainingSlot->is_assigned);
    }

    // --- Test concorrenza (lock) ---

    public function test_same_slot_not_assigned_twice(): void
    {
        Carbon::setTestNow('2026-04-20 15:00:00');

        $this->createSlot('A', '2026-04-20', '14:00:00');

        $user1 = $this->createUser();
        $play1 = $this->createPlay($user1, 'STORE01');
        $prize1 = $this->service->attempt($play1);
        $this->assertNotNull($prize1);

        // Secondo tentativo: nessuno slot disponibile
        $user2 = $this->createUser();
        $play2 = $this->createPlay($user2, 'STORE02');
        $prize2 = $this->service->attempt($play2);
        $this->assertNull($prize2, 'Slot già assegnato non deve essere dato due volte');
    }

    // --- Test scenario molti giocatori ---

    public function test_no_more_prizes_than_slots(): void
    {
        Carbon::setTestNow('2026-04-20 15:00:00');

        // 2 slot disponibili
        $this->createSlot('A', '2026-04-20', '10:00:00');
        $this->createSlot('B', '2026-04-20', '11:00:00');

        $winners = 0;
        for ($i = 0; $i < 10; $i++) {
            $user = $this->createUser();
            $play = $this->createPlay($user, 'STORE' . str_pad($i, 2, '0', STR_PAD_LEFT));
            if ($this->service->attempt($play)) {
                $winners++;
            }
        }

        $this->assertEquals(2, $winners, 'Non devono essere assegnati più premi degli slot');
    }

    // --- Test getWeekBounds ---

    public function test_week_bounds_monday(): void
    {
        [$monday, $sunday] = $this->service->getWeekBounds('2026-04-22'); // Mercoledì

        $this->assertEquals('2026-04-20', $monday->format('Y-m-d'));
        $this->assertEquals('2026-04-26', $sunday->format('Y-m-d'));
    }

    public function test_week_bounds_sunday(): void
    {
        [$monday, $sunday] = $this->service->getWeekBounds('2026-04-26'); // Domenica

        $this->assertEquals('2026-04-20', $monday->format('Y-m-d'));
        $this->assertEquals('2026-04-26', $sunday->format('Y-m-d'));
    }
}
