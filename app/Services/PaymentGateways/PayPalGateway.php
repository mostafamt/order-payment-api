<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use Illuminate\Support\Str;

class PayPalGateway implements PaymentGatewayInterface
{
    private string $clientId;

    private string $clientSecret;

    private string $mode;

    public function __construct()
    {
        $this->clientId = config('payment.gateways.paypal.client_id');
        $this->clientSecret = config('payment.gateways.paypal.client_secret');
        $this->mode = config('payment.gateways.paypal.mode', 'sandbox');
    }

    public function processPayment(Order $order, array $paymentData): array
    {
        // Simulate PayPal payment processing
        // In production, this would call the PayPal REST API

        // Validate PayPal specific data
        if (! isset($paymentData['paypal_email'])) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Missing PayPal email address',
                'payment_id' => null,
            ];
        }

        // Simulate random success/failure for demonstration
        $success = rand(1, 10) > 1; // 90% success rate

        if ($success) {
            return [
                'success' => true,
                'status' => 'successful',
                'message' => 'Payment processed successfully via PayPal',
                'payment_id' => 'PAYPAL-'.Str::upper(Str::random(16)),
                'transaction_details' => [
                    'payer_email' => $paymentData['paypal_email'],
                    'gateway' => 'paypal',
                    'mode' => $this->mode,
                    'processed_at' => now()->toIso8601String(),
                ],
            ];
        }

        return [
            'success' => false,
            'status' => 'failed',
            'message' => 'PayPal payment failed',
            'payment_id' => null,
            'error_code' => 'PAYPAL_ERROR',
        ];
    }

    public function getName(): string
    {
        return 'paypal';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->clientId) && ! empty($this->clientSecret);
    }
}
