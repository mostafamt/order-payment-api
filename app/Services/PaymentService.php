<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentGateways\CreditCardGateway;
use App\Services\PaymentGateways\PaymentGatewayInterface;
use App\Services\PaymentGateways\PayPalGateway;
use App\Services\PaymentGateways\StripeGateway;
use Exception;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    private array $gateways = [];

    public function __construct()
    {
        // Register available payment gateways
        $this->registerGateway(new CreditCardGateway);
        $this->registerGateway(new PayPalGateway);
        $this->registerGateway(new StripeGateway);
    }

    /**
     * Register a payment gateway
     */
    public function registerGateway(PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$gateway->getName()] = $gateway;
    }

    /**
     * Get a payment gateway by name
     *
     * @throws Exception
     */
    public function getGateway(string $gatewayName): PaymentGatewayInterface
    {
        if (! isset($this->gateways[$gatewayName])) {
            throw new Exception("Payment gateway '{$gatewayName}' is not registered");
        }

        $gateway = $this->gateways[$gatewayName];

        if (! $gateway->isConfigured()) {
            throw new Exception("Payment gateway '{$gatewayName}' is not properly configured");
        }

        return $gateway;
    }

    /**
     * Get all registered gateway names
     */
    public function getAvailableGateways(): array
    {
        return array_keys($this->gateways);
    }

    /**
     * Process a payment for an order
     *
     * @throws Exception
     */
    public function processPayment(Order $order, string $method, array $paymentData): Payment
    {
        // Validate order status
        if ($order->status !== 'confirmed') {
            throw new Exception('Payments can only be processed for orders with confirmed status');
        }

        // Get the appropriate gateway
        $gateway = $this->getGateway($method);

        // Create pending payment record
        $payment = Payment::create([
            'payment_id' => 'PENDING-'.time(),
            'order_id' => $order->id,
            'method' => $method,
            'status' => 'pending',
            'amount' => $order->total,
            'meta' => ['initiated_at' => now()->toIso8601String()],
        ]);

        try {
            // Process payment through gateway
            DB::beginTransaction();

            $result = $gateway->processPayment($order, $paymentData);

            // Update payment record based on result
            $payment->update([
                'payment_id' => $result['payment_id'] ?? $payment->payment_id,
                'status' => $result['status'],
                'meta' => array_merge(
                    $payment->meta ?? [],
                    [
                        'message' => $result['message'],
                        'transaction_details' => $result['transaction_details'] ?? null,
                        'error_code' => $result['error_code'] ?? null,
                        'processed_at' => now()->toIso8601String(),
                    ]
                ),
            ]);

            DB::commit();

            return $payment;
        } catch (Exception $e) {
            DB::rollBack();

            // Update payment as failed
            $payment->update([
                'status' => 'failed',
                'meta' => array_merge(
                    $payment->meta ?? [],
                    [
                        'error' => $e->getMessage(),
                        'failed_at' => now()->toIso8601String(),
                    ]
                ),
            ]);

            throw $e;
        }
    }
}
