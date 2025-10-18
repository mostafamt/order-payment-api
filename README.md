# Order and Payment Management API

A Laravel-based RESTful API for managing orders and payments with extensible payment gateway support using the Strategy Pattern.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [API Documentation](#api-documentation)
- [Payment Gateway Extensibility](#payment-gateway-extensibility)
- [Testing](#testing)
- [Code Quality](#code-quality)
- [Architecture](#architecture)
- [Additional Notes](#additional-notes)

## Features

- ✅ **JWT Authentication** - Secure API with JSON Web Tokens
- ✅ **Order Management** - Create, Read, Update, Delete orders with items
- ✅ **Payment Processing** - Process payments through multiple gateways
- ✅ **Extensible Gateway System** - Easy to add new payment gateways
- ✅ **Business Rules** - Enforced payment and order constraints
- ✅ **Authorization Policies** - User-based resource access control
- ✅ **Comprehensive Testing** - Unit and Feature tests
- ✅ **PSR-12 Compliant** - Clean, standardized code
- ✅ **Pagination** - Efficient data retrieval for large datasets

## Requirements

- PHP >= 8.1
- Composer
- MySQL/PostgreSQL/SQLite
- Laravel 10.x

## Installation

### 1. Clone the repository

```bash
git clone <repository-url>
cd order-payment-api
```

### 2. Install dependencies

```bash
composer install
```

### 3. Environment setup

```bash
cp .env.example .env
```

Edit `.env` and configure your database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=order_payment_db
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Generate application key

```bash
php artisan key:generate
```

### 5. Generate JWT secret

```bash
php artisan jwt:secret
```

### 6. Run migrations

```bash
php artisan migrate
```

### 7. Start the development server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## Configuration

### Payment Gateway Configuration

Payment gateways are configured in `config/payment.php` and use environment variables for security.

Add these to your `.env` file:

```env
# Credit Card Gateway
CREDIT_CARD_API_KEY=your_credit_card_api_key
CREDIT_CARD_API_SECRET=your_credit_card_api_secret
CREDIT_CARD_ENABLED=true

# PayPal Gateway
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret
PAYPAL_MODE=sandbox  # or 'live' for production
PAYPAL_ENABLED=true

# Stripe Gateway
STRIPE_SECRET_KEY=sk_test_your_stripe_secret
STRIPE_PUBLISHABLE_KEY=pk_test_your_stripe_publishable
STRIPE_ENABLED=true
```

## API Documentation

### Authentication Endpoints

#### Register User
```http
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response (200):**
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

#### Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

#### Get Current User
```http
GET /api/me
Authorization: Bearer {token}
```

#### Logout
```http
POST /api/logout
Authorization: Bearer {token}
```

### Order Endpoints

All order endpoints require authentication via JWT token.

#### Create Order
```http
POST /api/orders
Authorization: Bearer {token}
Content-Type: application/json

{
  "items": [
    {
      "product_name": "Product 1",
      "quantity": 2,
      "price": 50.00
    },
    {
      "product_name": "Product 2",
      "quantity": 1,
      "price": 25.00
    }
  ],
  "notes": "Special delivery instructions",
  "shipping_name": "John Doe",
  "shipping_phone": "+1234567890",
  "shipping_address": "123 Main Street, Apt 4B",
  "shipping_city": "New York",
  "shipping_province": "NY",
  "shipping_postal_code": "10001",
  "shipping_country": "USA"
}
```

**Note:** All shipping fields are optional.

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "status": "pending",
    "total": 125.00,
    "notes": "Special delivery instructions",
    "created_at": "2025-10-18T10:00:00.000000Z",
    "updated_at": "2025-10-18T10:00:00.000000Z"
  }
}
```

#### Get All Orders
```http
GET /api/orders
Authorization: Bearer {token}

# Optional query parameters:
# ?status=pending|confirmed|cancelled
# ?page=1
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 1,
        "status": "pending",
        "total": 125.00,
        "notes": null,
        "items": [...]
      }
    ],
    "per_page": 15,
    "total": 1
  }
}
```

#### Get Single Order
```http
GET /api/orders/{id}
Authorization: Bearer {token}
```

#### Update Order
```http
PUT /api/orders/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "confirmed",
  "items": [
    {
      "product_name": "Updated Product",
      "quantity": 3,
      "price": 100.00
    }
  ],
  "shipping_name": "Jane Doe",
  "shipping_phone": "+1987654321",
  "shipping_address": "456 Oak Avenue",
  "shipping_city": "Los Angeles",
  "shipping_province": "CA",
  "shipping_postal_code": "90001",
  "shipping_country": "USA"
}
```

**Note:** All fields are optional in the update request. You can update status, items, shipping information, or any combination of these fields.

#### Delete Order
```http
DELETE /api/orders/{id}
Authorization: Bearer {token}
```

**Note:** Orders with payments cannot be deleted (400 error).

### Payment Endpoints

#### Process Payment
```http
POST /api/payments/process
Authorization: Bearer {token}
Content-Type: application/json

# Credit Card Payment
{
  "order_id": 1,
  "method": "credit_card",
  "payment_data": {
    "card_number": "4111111111111111",
    "cvv": "123",
    "expiry_date": "12/25"
  }
}

# PayPal Payment
{
  "order_id": 1,
  "method": "paypal",
  "payment_data": {
    "paypal_email": "user@example.com"
  }
}

# Stripe Payment
{
  "order_id": 1,
  "method": "stripe",
  "payment_data": {
    "stripe_token": "tok_visa"
  }
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Payment processed successfully via Credit Card",
  "data": {
    "id": 1,
    "payment_id": "CC-XYZABC123456",
    "order_id": 1,
    "method": "credit_card",
    "status": "successful",
    "amount": 125.00,
    "meta": {
      "message": "Payment processed successfully via Credit Card",
      "transaction_details": {
        "card_last_four": "1111",
        "gateway": "credit_card",
        "processed_at": "2025-10-18T10:05:00+00:00"
      }
    },
    "order": {...}
  }
}
```

**Business Rule:** Payments can only be processed for orders with `status = "confirmed"`.

#### Get All Payments
```http
GET /api/payments
Authorization: Bearer {token}

# Optional query parameters:
# ?status=pending|successful|failed
# ?method=credit_card|paypal|stripe
# ?order_id=1
# ?page=1
```

#### Get Single Payment
```http
GET /api/payments/{id}
Authorization: Bearer {token}
```

### Error Responses

#### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": [
      "Error message"
    ]
  }
}
```

#### Unauthorized (401)
```json
{
  "message": "Unauthenticated."
}
```

#### Forbidden (403)
```json
{
  "success": false,
  "message": "Unauthorized to perform this action"
}
```

## Payment Gateway Extensibility

The system uses the **Strategy Pattern** to make adding new payment gateways simple and maintainable.

### How to Add a New Payment Gateway

#### Step 1: Create Gateway Class

Create a new gateway class in `app/Services/PaymentGateways/`:

```php
<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use Illuminate\Support\Str;

class NewPaymentGateway implements PaymentGatewayInterface
{
    private string $apiKey;
    private string $apiSecret;

    public function __construct()
    {
        $this->apiKey = config('payment.gateways.new_gateway.api_key');
        $this->apiSecret = config('payment.gateways.new_gateway.api_secret');
    }

    public function processPayment(Order $order, array $paymentData): array
    {
        // Validate required payment data
        if (!isset($paymentData['required_field'])) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Missing required field',
                'payment_id' => null,
            ];
        }

        // Process payment with gateway API
        // ... your integration code here ...

        // Return success response
        return [
            'success' => true,
            'status' => 'successful',
            'message' => 'Payment processed successfully',
            'payment_id' => 'NEW-' . Str::random(16),
            'transaction_details' => [
                'gateway' => 'new_gateway',
                'processed_at' => now()->toIso8601String(),
            ],
        ];
    }

    public function getName(): string
    {
        return 'new_gateway';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->apiSecret);
    }
}
```

#### Step 2: Register Gateway

Add the gateway to `app/Services/PaymentService.php`:

```php
public function __construct()
{
    $this->registerGateway(new CreditCardGateway);
    $this->registerGateway(new PayPalGateway);
    $this->registerGateway(new StripeGateway);
    $this->registerGateway(new NewPaymentGateway); // Add this line
}
```

#### Step 3: Add Configuration

Update `config/payment.php`:

```php
'gateways' => [
    // ... existing gateways ...

    'new_gateway' => [
        'api_key' => env('NEW_GATEWAY_API_KEY'),
        'api_secret' => env('NEW_GATEWAY_API_SECRET'),
        'enabled' => env('NEW_GATEWAY_ENABLED', true),
    ],
],
```

#### Step 4: Add Environment Variables

Add to `.env`:

```env
NEW_GATEWAY_API_KEY=your_api_key
NEW_GATEWAY_API_SECRET=your_api_secret
NEW_GATEWAY_ENABLED=true
```

#### Step 5: Update Validation

Update `app/Http/Requests/ProcessPaymentRequest.php`:

```php
public function rules()
{
    return [
        'order_id' => 'required|exists:orders,id',
        'method' => 'required|in:credit_card,paypal,stripe,new_gateway', // Add here
        'payment_data' => 'required|array',
        // Add validation for new gateway specific fields
        'payment_data.required_field' => 'required_if:method,new_gateway|string',
    ];
}
```

That's it! Your new payment gateway is now integrated and ready to use.

### Gateway Interface Contract

All payment gateways must implement `PaymentGatewayInterface`:

- `processPayment(Order $order, array $paymentData): array` - Process the payment
- `getName(): string` - Return the gateway identifier
- `isConfigured(): bool` - Check if gateway credentials are set

## Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suites

```bash
# Run only unit tests
php artisan test --testsuite=Unit

# Run only feature tests
php artisan test --testsuite=Feature
```

### Run Specific Test File

```bash
php artisan test tests/Feature/PaymentTest.php
```

### Run with Coverage

```bash
php artisan test --coverage
```

### Test Coverage

The project includes comprehensive tests for:
- ✅ Payment Gateway logic (Unit tests)
- ✅ Order CRUD operations (Feature tests)
- ✅ Payment processing (Feature tests)
- ✅ Authentication and Authorization (Feature tests)
- ✅ Validation rules (Feature tests)
- ✅ Business rules enforcement (Feature tests)

## Code Quality

### Laravel Pint (PSR-12)

Format code to PSR-12 standards:

```bash
./vendor/bin/pint
```

Check without fixing:

```bash
./vendor/bin/pint --test
```

## Architecture

### Design Patterns

#### Strategy Pattern (Payment Gateways)
- **Interface:** `PaymentGatewayInterface`
- **Context:** `PaymentService`
- **Strategies:** `CreditCardGateway`, `PayPalGateway`, `StripeGateway`

Benefits:
- Easy to add new gateways
- Each gateway is independently testable
- Follows Open/Closed Principle

#### Repository Pattern (Eloquent Models)
- Models act as data repositories
- Clean separation of concerns

### Key Business Rules

1. **Payment Processing**
   - Payments can only be processed for orders with `status = "confirmed"`
   - Payment records are created as `pending` first, then updated based on gateway response

2. **Order Status Management**
   - Confirmed orders with associated payments cannot be changed to `pending` or `cancelled`
   - This prevents status manipulation after payment has been processed
   - Returns 400 error if attempted

3. **Order Deletion**
   - Orders cannot be deleted if they have associated payments
   - Enforced at the controller level

4. **Authorization**
   - Users can only view, update, or delete their own orders
   - Users can only view payments for their own orders
   - Enforced via Laravel Policies

5. **Order Total Calculation**
   - Automatically calculated from order items
   - Updated via `Order::recalculateTotal()` method

### Database Schema

```
users
├── id
├── name
├── email
└── password

orders
├── id
├── user_id (FK → users)
├── status (pending|confirmed|cancelled)
├── total
├── notes
├── shipping_name
├── shipping_phone
├── shipping_address
├── shipping_city
├── shipping_province
├── shipping_postal_code
├── shipping_country
└── timestamps

order_items
├── id
├── order_id (FK → orders, cascade delete)
├── product_name
├── quantity
├── unit_price
├── line_total
└── timestamps

payments
├── id
├── payment_id (unique)
├── order_id (FK → orders, cascade delete)
├── method (credit_card|paypal|stripe)
├── status (pending|successful|failed)
├── amount
├── meta (JSON)
└── timestamps
```

## Additional Notes

### Assumptions

1. **Payment Simulation**: The current implementation simulates payment processing with randomized success/failure rates for demonstration purposes. In production, these would be replaced with actual API calls to payment providers.

2. **Order Confirmation**: Orders must be manually updated to `confirmed` status before payments can be processed. This could be automated based on business logic.

3. **Payment Idempotency**: The system doesn't currently prevent duplicate payment processing for the same order. Consider implementing idempotency keys in production.

### Security Considerations

- All API endpoints (except registration and login) require JWT authentication
- Payment gateway credentials are stored in environment variables, never in code
- Passwords are hashed using Laravel's bcrypt
- Authorization policies prevent unauthorized access to resources
- Input validation on all endpoints

### Future Enhancements

- Add webhook handlers for asynchronous payment confirmations
- Implement payment refund functionality
- Add order status transition workflow
- Implement rate limiting for API endpoints
- Add database seeders for demo data
- Implement soft deletes for orders and payments
- Add email notifications for order and payment events
- Implement API versioning

## License

This project is licensed under the MIT License.

## Support

For issues, questions, or contributions, please open an issue in the repository.
