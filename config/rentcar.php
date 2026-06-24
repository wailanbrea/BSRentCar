<?php

/*
|--------------------------------------------------------------------------
| Configuración de negocio RentCar (estándares RD)
| Ver docs/19_ENVIRONMENT_VARIABLES.md y docs/02_BUSINESS_RULES.md.
|--------------------------------------------------------------------------
*/

return [
    'currency' => env('DEFAULT_CURRENCY', 'DOP'),

    // ITBIS 18% (BR-P12).
    'tax_rate' => (float) env('TAX_RATE', 0.18),

    // Minutos que sostiene un hold de reserva pending_payment antes de expirar.
    'reservation_hold_minutes' => (int) env('RESERVATION_HOLD_MINUTES', 30),

    // Modo de depósito por defecto (BR-D00): authorized (hold) | charged.
    'deposit_mode' => env('DEPOSIT_MODE', 'authorized'),

    // Stripe (Fase 6). Ver docs/17_PAYMENT_PROVIDERS.md.
    'stripe' => [
        'secret_key'     => env('STRIPE_SECRET_KEY'),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
];
