<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => 'PAY-'.strtoupper(fake()->bothify('??########')),
            'order_id' => \App\Models\Order::factory(),
            'method' => fake()->randomElement(['credit_card', 'paypal', 'stripe']),
            'status' => fake()->randomElement(['pending', 'successful', 'failed']),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'meta' => [
                'processed_at' => now()->toIso8601String(),
                'message' => 'Payment processed',
            ],
        ];
    }
}
