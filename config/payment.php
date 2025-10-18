<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your payment gateways here. Each gateway requires specific
    | credentials that should be set in your .env file for security.
    |
    */

    'gateways' => [
        'credit_card' => [
            'api_key' => env('CREDIT_CARD_API_KEY', 'test_cc_key'),
            'api_secret' => env('CREDIT_CARD_API_SECRET', 'test_cc_secret'),
            'enabled' => env('CREDIT_CARD_ENABLED', true),
        ],

        'paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID', 'test_paypal_client_id'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET', 'test_paypal_secret'),
            'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
            'enabled' => env('PAYPAL_ENABLED', true),
        ],

        'stripe' => [
            'secret_key' => env('STRIPE_SECRET_KEY', 'sk_test_'),
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', 'pk_test_'),
            'enabled' => env('STRIPE_ENABLED', true),
        ],
    ],
];
