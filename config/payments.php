<?php

use App\Services\Payments\Gateways\ManualGateway;
use App\Services\Payments\Gateways\PayUGateway;
use App\Services\Payments\Gateways\RazorpayGateway;
use App\Services\Payments\Gateways\StripeGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Gateway
    |--------------------------------------------------------------------------
    |
    | PayU is the primary gateway for this platform. The default may still be
    | overridden at runtime by enabled gateways / admin settings.
    |
    */
    'default' => env('DEFAULT_PAYMENT_GATEWAY', 'payu'),

    'currency' => env('APP_CURRENCY', 'INR'),

    /*
    |--------------------------------------------------------------------------
    | Gateways
    |--------------------------------------------------------------------------
    |
    | Each gateway is modular and individually toggleable. Only enable the
    | gateways approved for the platform's lawful content category. All
    | webhooks must verify signatures and enforce idempotency.
    |
    */
    'gateways' => [

        'payu' => [
            'enabled' => env('PAYU_ENABLED', true),
            'driver' => PayUGateway::class,
            'label' => 'PayU',
            'mode' => env('PAYU_MODE', 'production'), // test | production
            'merchant_key' => env('PAYU_MERCHANT_KEY'),
            'merchant_salt' => env('PAYU_MERCHANT_SALT'),
            'auth_header' => env('PAYU_AUTH_HEADER'),
            'endpoints' => [
                'test' => 'https://test.payu.in/_payment',
                'production' => 'https://secure.payu.in/_payment',
            ],
        ],

        'razorpay' => [
            'enabled' => env('RAZORPAY_ENABLED', false),
            'driver' => RazorpayGateway::class,
            'label' => 'Razorpay',
            'key_id' => env('RAZORPAY_KEY_ID'),
            'key_secret' => env('RAZORPAY_KEY_SECRET'),
            'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        ],

        'stripe' => [
            'enabled' => env('STRIPE_ENABLED', false),
            'driver' => StripeGateway::class,
            'label' => 'Stripe',
            'key' => env('STRIPE_KEY'),
            'secret' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],

        'manual' => [
            'enabled' => env('MANUAL_PAYMENT_ENABLED', true),
            'driver' => ManualGateway::class,
            'label' => 'Manual / Bank Transfer',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook hardening
    |--------------------------------------------------------------------------
    */
    'webhooks' => [
        // Reject webhook events older than this many seconds (replay protection)
        'tolerance_seconds' => 300,
    ],
];
