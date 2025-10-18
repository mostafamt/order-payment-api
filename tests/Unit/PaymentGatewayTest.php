<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use App\Services\PaymentGateways\CreditCardGateway;
use App\Services\PaymentGateways\PayPalGateway;
use App\Services\PaymentGateways\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user and order
        $user = User::factory()->create();
        $this->order = Order::create([
            'user_id' => $user->id,
            'status' => 'confirmed',
            'total' => 100.00,
        ]);
    }

    public function test_credit_card_gateway_name()
    {
        $gateway = new CreditCardGateway;
        $this->assertEquals('credit_card', $gateway->getName());
    }

    public function test_paypal_gateway_name()
    {
        $gateway = new PayPalGateway;
        $this->assertEquals('paypal', $gateway->getName());
    }

    public function test_stripe_gateway_name()
    {
        $gateway = new StripeGateway;
        $this->assertEquals('stripe', $gateway->getName());
    }

    public function test_credit_card_gateway_is_configured()
    {
        $gateway = new CreditCardGateway;
        $this->assertTrue($gateway->isConfigured());
    }

    public function test_paypal_gateway_is_configured()
    {
        $gateway = new PayPalGateway;
        $this->assertTrue($gateway->isConfigured());
    }

    public function test_stripe_gateway_is_configured()
    {
        $gateway = new StripeGateway;
        $this->assertTrue($gateway->isConfigured());
    }

    public function test_credit_card_gateway_requires_card_number()
    {
        $gateway = new CreditCardGateway;
        $result = $gateway->processPayment($this->order, []);

        $this->assertFalse($result['success']);
        $this->assertEquals('failed', $result['status']);
        $this->assertStringContainsString('Missing required credit card information', $result['message']);
    }

    public function test_paypal_gateway_requires_email()
    {
        $gateway = new PayPalGateway;
        $result = $gateway->processPayment($this->order, []);

        $this->assertFalse($result['success']);
        $this->assertEquals('failed', $result['status']);
        $this->assertStringContainsString('Missing PayPal email', $result['message']);
    }

    public function test_stripe_gateway_requires_token()
    {
        $gateway = new StripeGateway;
        $result = $gateway->processPayment($this->order, []);

        $this->assertFalse($result['success']);
        $this->assertEquals('failed', $result['status']);
        $this->assertStringContainsString('Missing Stripe payment token', $result['message']);
    }

    public function test_credit_card_gateway_processes_payment()
    {
        $gateway = new CreditCardGateway;
        $result = $gateway->processPayment($this->order, [
            'card_number' => '4111111111111111',
            'cvv' => '123',
            'expiry_date' => '12/25',
        ]);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('payment_id', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_paypal_gateway_processes_payment()
    {
        $gateway = new PayPalGateway;
        $result = $gateway->processPayment($this->order, [
            'paypal_email' => 'test@example.com',
        ]);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('payment_id', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_stripe_gateway_processes_payment()
    {
        $gateway = new StripeGateway;
        $result = $gateway->processPayment($this->order, [
            'stripe_token' => 'tok_visa',
        ]);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('payment_id', $result);
        $this->assertArrayHasKey('message', $result);
    }
}
