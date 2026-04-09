<?php

namespace Database\Seeders;

use App\Models\Play;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SimulatedPlaysSeeder extends Seeder
{
    private static ?string $password = null;

    /**
     * Seed 2000 random valid plays from 800-900 random users
     * across the entire contest period, to simulate contest end.
     * No Faker dependency — safe for production.
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

        static::$password ??= Hash::make('password');

        $userCount = rand(800, 900);
        $this->command->info("Creating {$userCount} users...");

        $names = ['Marco', 'Luca', 'Andrea', 'Matteo', 'Alessandro', 'Lorenzo', 'Simone', 'Davide', 'Federico', 'Riccardo',
            'Giulia', 'Francesca', 'Sara', 'Valentina', 'Chiara', 'Elena', 'Alessia', 'Martina', 'Anna', 'Laura'];
        $surnames = ['Rossi', 'Bianchi', 'Russo', 'Ferrari', 'Esposito', 'Romano', 'Colombo', 'Ricci', 'Marino', 'Greco',
            'Bruno', 'Gallo', 'Conti', 'Costa', 'Giordano', 'Mancini', 'Rizzo', 'Lombardi', 'Moretti', 'Barbieri'];
        $cities = ['Bologna', 'Milano', 'Roma', 'Napoli', 'Torino', 'Firenze', 'Venezia', 'Genova', 'Palermo', 'Bari'];
        $provinces = ['BO', 'MI', 'RM', 'NA', 'TO', 'FI', 'VE', 'GE', 'PA', 'BA'];

        $userRows = [];
        $now = now();
        for ($i = 0; $i < $userCount; $i++) {
            $cityIdx = array_rand($cities);
            $userRows[] = [
                'name' => $names[array_rand($names)],
                'surname' => $surnames[array_rand($surnames)],
                'birth_date' => Carbon::now()->subYears(rand(19, 60))->subDays(rand(0, 364))->format('Y-m-d'),
                'email' => 'user'.($i + 1).'_'.uniqid().'@test.it',
                'phone' => '3'.str_pad((string) rand(0, 999999999), 9, '0', STR_PAD_LEFT),
                'address' => 'Via Test '.rand(1, 200),
                'city' => $cities[$cityIdx],
                'province' => $provinces[$cityIdx],
                'cap' => str_pad((string) rand(10000, 99999), 5, '0', STR_PAD_LEFT),
                'password' => static::$password,
                'privacy_consent' => true,
                'marketing_consent' => false,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($userRows) >= 500) {
                User::query()->insert($userRows);
                $userRows = [];
            }
        }

        if (! empty($userRows)) {
            User::query()->insert($userRows);
        }

        $users = User::query()->latest('id')->take($userCount)->pluck('id')->toArray();

        $totalPlays = 2000;
        $this->command->info("Creating {$totalPlays} plays...");

        $contestDays = CarbonPeriod::create($startDate, $endDate)->toArray();
        $plays = [];
        $userDayTracker = [];

        for ($i = 0; $i < $totalPlays; $i++) {
            $attempts = 0;

            do {
                $userId = $users[array_rand($users)];
                $day = $contestDays[array_rand($contestDays)];
                $dayKey = $userId.'-'.$day->format('Y-m-d');
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
                'user_id' => $userId,
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
