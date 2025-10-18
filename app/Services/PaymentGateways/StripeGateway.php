<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use Illuminate\Support\Str;

class StripeGateway implements PaymentGatewayInterface
{
    private string $secretKey;

    private string $publishableKey;

    public function __construct()
    {
        $this->secretKey = config('payment.gateways.stripe.secret_key');
        $this->publishableKey = config('payment.gateways.stripe.publishable_key');
    }

    public function processPayment(Order $order, array $paymentData): array
    {
        // Simulate Stripe payment processing
        // In production, this would call the Stripe API

        // Validate Stripe specific data
        if (! isset($paymentData['stripe_token'])) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Missing Stripe payment token',
                'payment_id' => null,
            ];
        }

        // Simulate random success/failure for demonstration
        $success = rand(1, 10) > 1; // 90% success rate

        if ($success) {
            return [
                'success' => true,
                'status' => 'successful',
                'message' => 'Payment processed successfully via Stripe',
                'payment_id' => 'pi_'.Str::random(24),
                'transaction_details' => [
                    'charge_id' => 'ch_'.Str::random(24),
                    'gateway' => 'stripe',
                    'processed_at' => now()->toIso8601String(),
                ],
            ];
        }

        return [
            'success' => false,
            'status' => 'failed',
            'message' => 'Stripe payment failed',
            'payment_id' => null,
            'error_code' => 'STRIPE_ERROR',
        ];
    }

    public function getName(): string
    {
        return 'stripe';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->secretKey) && ! empty($this->publishableKey);
    }
}
