<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and get JWT token
        $this->user = User::factory()->create();
        /** @var \Tymon\JWTAuth\JWTGuard $auth */
        $auth = auth();
        $this->token = $auth->login($this->user);
    }

    public function test_user_can_create_order()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/orders', [
            'items' => [
                [
                    'product_name' => 'Product 1',
                    'quantity' => 2,
                    'price' => 50.00,
                ],
                [
                    'product_name' => 'Product 2',
                    'quantity' => 1,
                    'price' => 25.00,
                ],
            ],
            'notes' => 'Test order',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'user_id',
                    'status',
                    'total',
                    'notes',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'total' => 125.00,
        ]);
    }

    public function test_user_can_view_their_orders()
    {
        // Create some orders
        Order::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_user_can_filter_orders_by_status()
    {
        Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        Order::factory()->create(['user_id' => $this->user->id, 'status' => 'confirmed']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/orders?status=confirmed');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('confirmed', $data[0]['status']);
    }

    public function test_user_can_view_single_order()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $order->id,
                ],
            ]);
    }

    public function test_user_cannot_view_other_users_order()
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson("/api/orders/{$order->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_their_order()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->putJson("/api/orders/{$order->id}", [
            'status' => 'confirmed',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_user_cannot_update_other_users_order()
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->putJson("/api/orders/{$order->id}", [
            'status' => 'confirmed',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_order_without_payments()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_user_cannot_delete_order_with_payments()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        Payment::factory()->create(['order_id' => $order->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete order with payments',
            ]);
    }

    public function test_confirmed_order_with_payments_cannot_change_status()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
        ]);
        Payment::factory()->create(['order_id' => $order->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->putJson("/api/orders/{$order->id}", [
            'status' => 'cancelled',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot change status of confirmed order with payments to pending or cancelled',
            ]);

        // Verify status didn't change
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_order_creation_validates_items()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/orders', [
            'items' => [],
        ]);

        $response->assertStatus(422);
    }
}
