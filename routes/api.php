<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Here is where all API routes are defined.
| These routes are automatically prefixed with /api/
| Example: /api/register, /api/orders
|--------------------------------------------------------------------------
*/

// Public routes (no authentication required)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require JWT token)
Route::middleware('auth:api')->group(function () {

    // --- Authentication ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // --- Order Management ---
    Route::get('/orders', [OrderController::class, 'index']);            // list or filter by status
    Route::post('/orders', [OrderController::class, 'store']);           // create new order
    Route::get('/orders/{order}', [OrderController::class, 'show']);        // view single order
    Route::put('/orders/{order}', [OrderController::class, 'update']);      // update existing order
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);  // delete order (if no payments)

    // --- Payment Management ---
    Route::get('/payments', [PaymentController::class, 'index']);         // list all payments
    Route::get('/payments/{id}', [PaymentController::class, 'show']);     // view specific payment
    Route::post('/payments/process', [PaymentController::class, 'process']); // simulate/process payment
});
