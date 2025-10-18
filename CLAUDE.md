# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 10 API for managing orders and payments with JWT authentication. The API is stateless and designed for RESTful interactions.

## Development Commands

### Setup
```bash
# Copy environment file
cp .env.example .env

# Install dependencies
composer install

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Run migrations
php artisan migrate

# Seed database (if seeders exist)
php artisan db:seed
```

### Testing
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run specific test file
php artisan test tests/Feature/OrderTest.php

# Run with coverage
php artisan test --coverage
```

### Development Server
```bash
# Start development server
php artisan serve

# Clear caches during development
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Code Quality
```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Fix specific files
./vendor/bin/pint app/Http/Controllers
```

### Database
```bash
# Create new migration
php artisan make:migration create_table_name

# Rollback last migration
php artisan migrate:rollback

# Refresh database (drop all tables and re-run migrations)
php artisan migrate:fresh

# Refresh with seeding
php artisan migrate:fresh --seed
```

## Architecture

### Authentication Flow
- Uses `php-open-source-saver/jwt-auth` package for JWT tokens
- Public routes: `/api/register`, `/api/login`
- Protected routes require `auth:api` middleware
- Auth guard configured to use JWT driver
- Token obtained via login and passed as `Authorization: Bearer <token>` header

### Data Model Relationships
```
User (id, name, email, password)
  └─> hasMany Order

Order (id, user_id, status, total, notes)
  ├─> belongsTo User
  ├─> hasMany OrderItem
  └─> hasMany Payment

OrderItem (id, order_id, product_name, quantity, unit_price, line_total)
  └─> belongsTo Order

Payment (id, payment_id, order_id, method, status, amount, meta)
  └─> belongsTo Order
```

### Key Domain Logic

**Order Total Calculation**
- Order totals are calculated via `Order::recalculateTotal()` method
- This method sums `quantity * unit_price` for all order items
- Called automatically after creating/updating order items

**Order Status Flow**
- Default status: `pending`
- Allowed statuses: `pending`, `confirmed`, `cancelled`
- Status can be updated via `PUT /api/orders/{id}` with `status` field

**Order Lifecycle**
- Orders cannot be deleted if they have associated payments (OrderController:88)
- When updating order items, the simple approach is used: delete all items and recreate (OrderController:67)
- Authorization checks are commented out but should be implemented via policies

### Request Validation

**StoreOrderRequest**
- `items` (required array with min 1 item)
  - `product_name` (required string, max 255)
  - `quantity` (required integer, min 1)
  - `price` (required numeric, min 0)
- `notes` (optional string, max 1000)

**UpdateOrderRequest**
- `items` (optional, same structure as StoreOrderRequest)
- `status` (optional, must be: pending, confirmed, or cancelled)

### API Response Format
All responses follow this pattern:
```json
{
  "success": true/false,
  "data": {...},
  "message": "..."
}
```

### Authorization Pattern
- Authorization logic exists but is commented out in several places:
  - `OrderController::show()` - line 47
  - `OrderController::update()` - line 55
  - `OrderController::destroy()` - line 87 (active)
- When implementing, create policies in `app/Policies` and uncomment `$this->authorize()` calls

### Incomplete Implementation
- `PaymentController` is stubbed but has no implementation
- Payment processing routes are defined in routes/api.php but need implementation:
  - `GET /api/payments` - list all payments
  - `GET /api/payments/{id}` - view specific payment
  - `POST /api/payments/process` - process payment

## Database Configuration
- Default connection: MySQL
- Connection details in `.env` file
- Migrations use foreign key constraints with cascade deletes
- Payment `meta` field uses JSON casting for flexible metadata storage
