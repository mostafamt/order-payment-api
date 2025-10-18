<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use Illuminate\Support\Str;

class CreditCardGateway implements PaymentGatewayInterface
{
    private string $apiKey;

    private string $apiSecret;

    public function __construct()
    {
        $this->apiKey = config('payment.gateways.credit_card.api_key');
        $this->apiSecret = config('payment.gateways.credit_card.api_secret');
    }

    public function processPayment(Order $order, array $paymentData): array
    {
        // Simulate credit card payment processing
        // In production, this would call the actual payment gateway API

        // Validate credit card specific data
        if (! isset($paymentData['card_number']) || ! isset($paymentData['cvv'])) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Missing required credit card information',
                'payment_id' => null,
            ];
        }

        // Simulate random success/failure for demonstration
        $success = rand(1, 10) > 2; // 80% success rate

        if ($success) {
            return [
                'success' => true,
                'status' => 'successful',
                'message' => 'Payment processed successfully via Credit Card',
                'payment_id' => 'CC-'.Str::upper(Str::random(12)),
                'transaction_details' => [
                    'card_last_four' => substr($paymentData['card_number'], -4),
                    'gateway' => 'credit_card',
                    'processed_at' => now()->toIso8601String(),
                ],
            ];
        }

        return [
            'success' => false,
            'status' => 'failed',
            'message' => 'Credit card payment declined',
            'payment_id' => null,
            'error_code' => 'CARD_DECLINED',
        ];
    }

    public function getName(): string
    {
        return 'credit_card';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey) && ! empty($this->apiSecret);
    }
}
