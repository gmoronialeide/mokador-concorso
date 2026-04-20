<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('STORE####')),
            'name' => fake()->company(),
            'sign_name' => fake()->companySuffix(),
            'vat_number' => fake()->numerify('###########'),
            'agent' => fake()->name(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => strtoupper(fake()->lexify('??')),
            'cap' => fake()->numerify('#####'),
            'is_active' => true,
        ];
    }
}
