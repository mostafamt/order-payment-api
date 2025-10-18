<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'status' => fake()->randomElement(['pending', 'confirmed', 'cancelled']),
            'total' => fake()->randomFloat(2, 10, 1000),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
