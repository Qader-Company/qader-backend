<?php

use App\Modules\V1\Payments\Infrastructure\Gateways\FakePaymentGateway;

return [
    'default' => env('PAYMENT_GATEWAY'),

    'gateways' => [
        'fake' => [
            'driver' => FakePaymentGateway::class,
            'webhook_secret' => env('PAYMENT_FAKE_WEBHOOK_SECRET', 'local-payment-webhook-secret'),
        ],
    ],

    'webhook' => [
        'max_attempts_per_minute' => (int) env('PAYMENT_WEBHOOK_RATE_LIMIT', 120),
    ],

    'currency_exponents' => [
        'EGP' => 2,
        'SAR' => 2,
        'USD' => 2,
        'AED' => 2,
        'KWD' => 3,
        'JPY' => 0,
    ],
];
