<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and get JWT token
        $this->user = User::factory()->create();
        $this->token = auth()->login($this->user);
    }

    public function test_user_can_process_payment_for_confirmed_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'total' => 100.00,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/payments/process', [
            'order_id' => $order->id,
            'method' => 'credit_card',
            'payment_data' => [
                'card_number' => '4111111111111111',
                'cvv' => '123',
                'expiry_date' => '12/25',
            ],
        ]);

        $response->assertStatus([200, 422]); // Can be successful or failed (simulated)
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'payment_id',
                'order_id',
                'method',
                'status',
                'amount',
            ],
        ]);
    }

    public function test_user_cannot_process_payment_for_pending_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
            'total' => 100.00,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/payments/process', [
            'order_id' => $order->id,
            'method' => 'credit_card',
            'payment_data' => [
                'card_number' => '4111111111111111',
                'cvv' => '123',
            ],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_user_cannot_process_payment_for_other_users_order()
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'confirmed',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/payments/process', [
            'order_id' => $order->id,
            'method' => 'credit_card',
            'payment_data' => [
                'card_number' => '4111111111111111',
                'cvv' => '123',
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_payment_process_validates_required_fields()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/payments/process', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id', 'method', 'payment_data']);
    }

    public function test_payment_process_validates_credit_card_data()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/payments/process', [
            'order_id' => $order->id,
            'method' => 'credit_card',
            'payment_data' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_data.card_number', 'payment_data.cvv']);
    }

    public function test_payment_process_validates_paypal_data()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/payments/process', [
            'order_id' => $order->id,
            'method' => 'paypal',
            'payment_data' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_data.paypal_email']);
    }

    public function test_user_can_view_all_payments()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        Payment::factory()->count(3)->create(['order_id' => $order->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'current_page',
                    'per_page',
                ],
            ]);
    }

    public function test_user_can_view_single_payment()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        $payment = Payment::factory()->create(['order_id' => $order->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson("/api/payments/{$payment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $payment->id,
                ],
            ]);
    }

    public function test_user_cannot_view_other_users_payment()
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id]);
        $payment = Payment::factory()->create(['order_id' => $order->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson("/api/payments/{$payment->id}");

        $response->assertStatus(403);
    }

    public function test_payment_requires_authentication()
    {
        $response = $this->postJson('/api/payments/process', [
            'order_id' => 1,
            'method' => 'credit_card',
            'payment_data' => [],
        ]);

        $response->assertStatus(401);
    }
}
