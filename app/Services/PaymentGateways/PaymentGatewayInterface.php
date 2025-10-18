<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;

interface PaymentGatewayInterface
{
    /**
     * Process a payment for the given order
     *
     * @return array Returns payment result with status and details
     */
    public function processPayment(Order $order, array $paymentData): array;

    /**
     * Get the gateway name
     */
    public function getName(): string;

    /**
     * Validate gateway configuration
     */
    public function isConfigured(): bool;
}
