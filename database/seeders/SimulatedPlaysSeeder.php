<?php

namespace Database\Seeders;

use App\Models\Play;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

class SimulatedPlaysSeeder extends Seeder
{
    /**
     * Seed 2000 random valid plays from 800-900 random users
     * across the entire contest period, to simulate contest end.
     */
    public function run(): void
    {
        $startDate = Carbon::parse(config('app.concorso_start_date'));
        $endDate = Carbon::parse(config('app.concorso_end_date'));

        $storeCodes = Store::query()->pluck('code')->toArray();

        if (empty($storeCodes)) {
            $this->command->error('No stores found. Run store seeder first.');

            return;
        }

        $userCount = rand(800, 900);
        $this->command->info("Creating {$userCount} users...");

        $users = User::factory()->count($userCount)->create();

        $totalPlays = 2000;
        $this->command->info("Creating {$totalPlays} plays...");

        $contestDays = CarbonPeriod::create($startDate, $endDate)->toArray();
        $plays = [];
        $userDayTracker = [];

        for ($i = 0; $i < $totalPlays; $i++) {
            $attempts = 0;

            do {
                $user = $users->random();
                $day = $contestDays[array_rand($contestDays)];
                $dayKey = $user->id.'-'.$day->format('Y-m-d');
                $attempts++;

                if ($attempts > 100) {
                    break;
                }
            } while (isset($userDayTracker[$dayKey]));

            if ($attempts > 100) {
                continue;
            }

            $userDayTracker[$dayKey] = true;

            $playedAt = $day->copy()
                ->setHour(rand(8, 20))
                ->setMinute(rand(0, 59))
                ->setSecond(rand(0, 59));

            $plays[] = [
                'user_id' => $user->id,
                'store_code' => $storeCodes[array_rand($storeCodes)],
                'receipt_image' => 'receipts/simulated_'.uniqid('', true).'.jpg',
                'played_at' => $playedAt,
                'is_winner' => false,
                'prize_id' => null,
                'winning_slot_id' => null,
                'created_at' => $playedAt,
                'updated_at' => $playedAt,
            ];

            if (count($plays) >= 500) {
                Play::query()->insert($plays);
                $plays = [];
            }
        }

        if (! empty($plays)) {
            Play::query()->insert($plays);
        }

        $actualPlays = Play::query()->count();
        $this->command->info("Done! Created {$userCount} users and {$actualPlays} total plays.");
    }
}
