<?php

namespace Tests\Feature;

use App\Models\Prize;
use App\Models\WinningSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateWinningSlotsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

    }

    public function test_generates_exactly_104_slots(): void
    {
        $this->artisan('concorso:generate-slots')
            ->assertSuccessful();

        $this->assertDatabaseCount('winning_slots', 104);
    }

    public function test_correct_distribution_per_prize(): void
    {
        $this->artisan('concorso:generate-slots')
            ->assertSuccessful();

        $expected = ['A' => 28, 'B' => 28, 'C' => 20, 'D' => 16, 'E' => 12];

        foreach ($expected as $code => $count) {
            $prize = Prize::where('code', $code)->first();
            $actual = WinningSlot::where('prize_id', $prize->id)->count();
            $this->assertEquals($count, $actual, "Premio {$code}: attesi {$count}, trovati {$actual}");
        }
    }

    public function test_slots_times_within_range(): void
    {
        $this->artisan('concorso:generate-slots')
            ->assertSuccessful();

        $slots = WinningSlot::all();
        foreach ($slots as $slot) {
            $hour = (int) substr($slot->scheduled_time, 0, 2);
            $this->assertGreaterThanOrEqual(8, $hour, "Ora troppo presto: {$slot->scheduled_time}");
            $this->assertLessThanOrEqual(21, $hour, "Ora troppo tardi: {$slot->scheduled_time}");
        }
    }

    public function test_slots_cover_all_28_days(): void
    {
        $this->artisan('concorso:generate-slots')
            ->assertSuccessful();

        $uniqueDates = WinningSlot::distinct('scheduled_date')->count('scheduled_date');
        $this->assertEquals(28, $uniqueDates);
    }

    public function test_dry_run_does_not_insert(): void
    {
        $this->artisan('concorso:generate-slots', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('winning_slots', 0);
    }

    public function test_fails_if_slots_already_exist(): void
    {
        $this->artisan('concorso:generate-slots')->assertSuccessful();

        $this->artisan('concorso:generate-slots')
            ->assertFailed();
    }

    public function test_reset_regenerates_slots(): void
    {
        $this->artisan('concorso:generate-slots')->assertSuccessful();
        $this->assertDatabaseCount('winning_slots', 104);

        $this->artisan('concorso:generate-slots', ['--reset' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('winning_slots', 104);
    }

    public function test_prize_c_not_on_tuesday_thursday(): void
    {
        $this->artisan('concorso:generate-slots')->assertSuccessful();

        $prizeC = Prize::where('code', 'C')->first();
        $slotsCOnTuesday = WinningSlot::where('prize_id', $prizeC->id)
            ->get()
            ->filter(fn ($s) => $s->scheduled_date->isoFormat('E') == 2); // Martedì

        $this->assertCount(0, $slotsCOnTuesday, 'Premio C non deve avere slot di martedì');

        $slotsCOnThursday = WinningSlot::where('prize_id', $prizeC->id)
            ->get()
            ->filter(fn ($s) => $s->scheduled_date->isoFormat('E') == 4); // Giovedì

        $this->assertCount(0, $slotsCOnThursday, 'Premio C non deve avere slot di giovedì');
    }
}
