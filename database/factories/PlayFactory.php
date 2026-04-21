<?php

namespace Database\Factories;

use App\Enums\PlayStatus;
use App\Models\Play;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Play>
 */
class PlayFactory extends Factory
{
    protected $model = Play::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'store_id' => null,
            'store_code' => strtoupper(fake()->bothify('STORE####')),
            'receipt_image' => 'receipts/'.fake()->uuid().'.jpg',
            'played_at' => now(),
            'is_winner' => false,
            'status' => PlayStatus::Pending,
        ];
    }

    public function forStore(Store $store): static
    {
        return $this->state(fn () => [
            'store_id' => $store->id,
            'store_code' => $store->code,
        ]);
    }
}
