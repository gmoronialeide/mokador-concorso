<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'surname' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-19 years')->format('Y-m-d'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '3' . fake()->numerify('#########'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->randomElement(['BO', 'MI', 'RM', 'NA', 'TO', 'FI', 'VE', 'GE', 'PA', 'BA']),
            'cap' => fake()->numerify('#####'),
            'password' => static::$password ??= Hash::make('password'),
            'privacy_consent' => true,
            'marketing_consent' => false,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function banned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_banned' => true,
            'ban_reason' => 'Banned for testing',
        ]);
    }

    public function minor(): static
    {
        return $this->state(fn (array $attributes) => [
            'birth_date' => now()->subYears(16)->format('Y-m-d'),
        ]);
    }
}
