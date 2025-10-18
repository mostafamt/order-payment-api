# Adding a New Payment Gateway

This guide explains how to add a new payment gateway to the Order and Payment Management API using the Strategy Pattern.

## Overview

The system uses the **Strategy Pattern** to make payment gateways interchangeable and extensible. Each payment gateway implements a common interface, allowing new gateways to be added with minimal changes to existing code.

## Step-by-Step Guide

### Step 1: Create Gateway Class

Create a new class in `app/Services/PaymentGateways/` that implements `PaymentGatewayInterface`.

**Example: Creating a new "Razorpay" gateway**

```php
<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use Illuminate\Support\Str;

class RazorpayGateway implements PaymentGatewayInterface
{
    private string $keyId;
    private string $keySecret;

    public function __construct()
    {
        $this->keyId = config('payment.gateways.razorpay.key_id');
        $this->keySecret = config('payment.gateways.razorpay.key_secret');
    }

    /**
     * Process payment through Razorpay
     */
    public function processPayment(Order $order, array $paymentData): array
    {
        // Step 1: Validate required data
        if (!isset($paymentData['razorpay_payment_id'])) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Missing Razorpay payment ID',
                'payment_id' => null,
            ];
        }

        // Step 2: Process payment with Razorpay API
        try {
            // In production, you would call Razorpay API here
            // Example:
            // $api = new Razorpay\Api\Api($this->keyId, $this->keySecret);
            // $payment = $api->payment->fetch($paymentData['razorpay_payment_id']);
            // $payment->capture(['amount' => $order->total * 100]);

            // For now, simulate success
            $success = rand(1, 10) > 1; // 90% success rate

            if ($success) {
                return [
                    'success' => true,
                    'status' => 'successful',
                    'message' => 'Payment processed successfully via Razorpay',
                    'payment_id' => 'pay_' . Str::random(14),
                    'transaction_details' => [
                        'razorpay_payment_id' => $paymentData['razorpay_payment_id'],
                        'gateway' => 'razorpay',
                        'processed_at' => now()->toIso8601String(),
                    ],
                ];
            }

            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Razorpay payment failed',
                'payment_id' => null,
                'error_code' => 'RAZORPAY_ERROR',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Payment processing error: ' . $e->getMessage(),
                'payment_id' => null,
            ];
        }
    }

    /**
     * Get the gateway name (used for method identification)
     */
    public function getName(): string
    {
        return 'razorpay';
    }

    /**
     * Check if gateway is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->keyId) && !empty($this->keySecret);
    }
}
```

### Step 2: Register the Gateway

Open `app/Services/PaymentService.php` and add your gateway to the constructor:

```php
public function __construct()
{
    // Register available payment gateways
    $this->registerGateway(new CreditCardGateway);
    $this->registerGateway(new PayPalGateway);
    $this->registerGateway(new StripeGateway);
    $this->registerGateway(new RazorpayGateway); // Add this line
}
```

### Step 3: Add Configuration

Add gateway configuration to `config/payment.php`:

```php
<?php

return [
    'gateways' => [
        // ... existing gateways ...

        'razorpay' => [
            'key_id' => env('RAZORPAY_KEY_ID'),
            'key_secret' => env('RAZORPAY_KEY_SECRET'),
            'enabled' => env('RAZORPAY_ENABLED', true),
        ],
    ],
];
```

### Step 4: Add Environment Variables

Add credentials to `.env`:

```env
RAZORPAY_KEY_ID=your_razorpay_key_id
RAZORPAY_KEY_SECRET=your_razorpay_key_secret
RAZORPAY_ENABLED=true
```

### Step 5: Update Request Validation

Update `app/Http/Requests/ProcessPaymentRequest.php` to include the new gateway:

```php
public function rules()
{
    return [
        'order_id' => 'required|exists:orders,id',
        'method' => 'required|in:credit_card,paypal,stripe,razorpay', // Add here
        'payment_data' => 'required|array',

        // Credit card validation
        'payment_data.card_number' => 'required_if:method,credit_card|string',
        'payment_data.cvv' => 'required_if:method,credit_card|string',
        'payment_data.expiry_date' => 'sometimes|string',

        // PayPal validation
        'payment_data.paypal_email' => 'required_if:method,paypal|email',

        // Stripe validation
        'payment_data.stripe_token' => 'required_if:method,stripe|string',

        // Razorpay validation (Add this)
        'payment_data.razorpay_payment_id' => 'required_if:method,razorpay|string',
    ];
}

public function messages()
{
    return [
        'order_id.required' => 'Order ID is required',
        'order_id.exists' => 'Order does not exist',
        'method.required' => 'Payment method is required',
        'method.in' => 'Invalid payment method. Accepted methods: credit_card, paypal, stripe, razorpay',
        'payment_data.required' => 'Payment data is required',

        // ... existing messages ...

        // Razorpay messages (Add this)
        'payment_data.razorpay_payment_id.required_if' => 'Razorpay payment ID is required for Razorpay payments',
    ];
}
```

### Step 6: Write Tests

Create tests for your new gateway in `tests/Unit/PaymentGatewayTest.php`:

```php
public function test_razorpay_gateway_name()
{
    $gateway = new RazorpayGateway;
    $this->assertEquals('razorpay', $gateway->getName());
}

public function test_razorpay_gateway_is_configured()
{
    $gateway = new RazorpayGateway;
    $this->assertTrue($gateway->isConfigured());
}

public function test_razorpay_gateway_requires_payment_id()
{
    $gateway = new RazorpayGateway;
    $result = $gateway->processPayment($this->order, []);

    $this->assertFalse($result['success']);
    $this->assertEquals('failed', $result['status']);
    $this->assertStringContainsString('Missing Razorpay payment ID', $result['message']);
}

public function test_razorpay_gateway_processes_payment()
{
    $gateway = new RazorpayGateway;
    $result = $gateway->processPayment($this->order, [
        'razorpay_payment_id' => 'pay_test123456',
    ]);

    $this->assertArrayHasKey('success', $result);
    $this->assertArrayHasKey('status', $result);
    $this->assertArrayHasKey('payment_id', $result);
    $this->assertArrayHasKey('message', $result);
}
```

Add feature tests in `tests/Feature/PaymentTest.php`:

```php
public function test_payment_process_validates_razorpay_data()
{
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'confirmed',
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson('/api/payments/process', [
        'order_id' => $order->id,
        'method' => 'razorpay',
        'payment_data' => [],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['payment_data.razorpay_payment_id']);
}
```

### Step 7: Update Documentation

Update the Postman collection to include the new gateway:

```json
{
  "name": "Process Razorpay Payment",
  "request": {
    "method": "POST",
    "header": [
      {
        "key": "Content-Type",
        "value": "application/json"
      },
      {
        "key": "Authorization",
        "value": "Bearer {{jwt_token}}"
      }
    ],
    "body": {
      "mode": "raw",
      "raw": "{\n    \"order_id\": {{order_id}},\n    \"method\": \"razorpay\",\n    \"payment_data\": {\n        \"razorpay_payment_id\": \"pay_test123456\"\n    }\n}"
    },
    "url": {
      "raw": "{{base_url}}/payments/process",
      "host": ["{{base_url}}"],
      "path": ["payments", "process"]
    },
    "description": "Process a Razorpay payment"
  }
}
```

### Step 8: Run Tests

```bash
# Run all tests
php artisan test

# Run only payment gateway tests
php artisan test --filter=PaymentGateway

# Run only payment feature tests
php artisan test tests/Feature/PaymentTest.php
```

### Step 9: Format Code

Apply PSR-12 standards:

```bash
./vendor/bin/pint
```

## Interface Contract

All payment gateways MUST implement `PaymentGatewayInterface`:

```php
interface PaymentGatewayInterface
{
    /**
     * Process a payment for the given order
     *
     * @param Order $order The order to process payment for
     * @param array $paymentData Gateway-specific payment data
     * @return array Payment result with keys:
     *               - success (bool)
     *               - status (string: 'pending', 'successful', 'failed')
     *               - message (string)
     *               - payment_id (string|null)
     *               - transaction_details (array, optional)
     *               - error_code (string, optional)
     */
    public function processPayment(Order $order, array $paymentData): array;

    /**
     * Get the gateway name
     *
     * @return string Unique identifier for this gateway (e.g., 'razorpay')
     */
    public function getName(): string;

    /**
     * Validate gateway configuration
     *
     * @return bool True if gateway is properly configured
     */
    public function isConfigured(): bool;
}
```

## Best Practices

### 1. Error Handling

Always handle exceptions and return consistent error responses:

```php
try {
    // API call
} catch (ApiException $e) {
    return [
        'success' => false,
        'status' => 'failed',
        'message' => 'API error: ' . $e->getMessage(),
        'payment_id' => null,
        'error_code' => $e->getCode(),
    ];
}
```

### 2. Validation

Validate all required fields before processing:

```php
$requiredFields = ['field1', 'field2'];
foreach ($requiredFields as $field) {
    if (!isset($paymentData[$field])) {
        return [
            'success' => false,
            'status' => 'failed',
            'message' => "Missing required field: {$field}",
            'payment_id' => null,
        ];
    }
}
```

### 3. Logging

Add logging for debugging and monitoring:

```php
use Illuminate\Support\Facades\Log;

Log::info('Processing payment', [
    'gateway' => $this->getName(),
    'order_id' => $order->id,
    'amount' => $order->total,
]);
```

### 4. Configuration Validation

Check configuration in `isConfigured()`:

```php
public function isConfigured(): bool
{
    return !empty($this->apiKey)
        && !empty($this->apiSecret)
        && !empty($this->merchantId);
}
```

### 5. Response Format

Always return the same structure:

```php
return [
    'success' => true|false,
    'status' => 'pending'|'successful'|'failed',
    'message' => 'Descriptive message',
    'payment_id' => 'gateway_payment_id' or null,
    'transaction_details' => [...], // Optional
    'error_code' => 'ERROR_CODE', // Optional, for failures
];
```

## Example API Call

Once implemented, clients can use your new gateway:

```bash
curl -X POST http://localhost:8000/api/payments/process \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 1,
    "method": "razorpay",
    "payment_data": {
      "razorpay_payment_id": "pay_test123456"
    }
  }'
```

## Checklist

Before deploying your new gateway, ensure:

- [ ] Gateway class implements `PaymentGatewayInterface`
- [ ] Gateway is registered in `PaymentService`
- [ ] Configuration added to `config/payment.php`
- [ ] Environment variables documented in `.env.example`
- [ ] Validation rules added to `ProcessPaymentRequest`
- [ ] Unit tests written and passing
- [ ] Feature tests written and passing
- [ ] Code formatted with Laravel Pint (PSR-12)
- [ ] Postman collection updated
- [ ] README updated with new gateway info

## Need Help?

If you encounter issues:

1. Check that `isConfigured()` returns `true`
2. Review Laravel logs in `storage/logs/laravel.log`
3. Test the gateway in isolation with unit tests
4. Verify environment variables are loaded correctly

## Summary

Adding a new payment gateway involves:

1. **Create** gateway class implementing the interface
2. **Register** gateway in PaymentService
3. **Configure** in config/payment.php and .env
4. **Validate** in ProcessPaymentRequest
5. **Test** with unit and feature tests
6. **Document** in Postman and README

The Strategy Pattern ensures your new gateway integrates seamlessly without modifying existing code!
