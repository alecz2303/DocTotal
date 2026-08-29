<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Plans
    |--------------------------------------------------------------------------
    |
    | Los importes se almacenan en centavos.
    |
    | Mensual:
    | $600 MXN
    |
    | Anual:
    | $6,000 MXN
    | Equivale a $500/mes.
    | Ahorro frente a 12 mensualidades: $1,200 MXN.
    |
    */

    'plans' => [
        'monthly' => [
            'name' =>
            'DocTotal Mensual',

            'amount' =>
            60000,

            'currency' =>
            'MXN',
        ],

        'yearly' => [
            'name' =>
            'DocTotal Anual',

            'amount' =>
            600000,

            'currency' =>
            'MXN',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic charging
    |--------------------------------------------------------------------------
    */

    'automatic_charging_enabled' => env(
        'BILLING_AUTOMATIC_CHARGING_ENABLED',
        false
    ),

    /*
    |--------------------------------------------------------------------------
    | Manual checkout expiration
    |--------------------------------------------------------------------------
    |
    | Los checkouts manuales iniciales que permanezcan pendientes durante
    | este tiempo podrán considerarse abandonados y cancelarse de forma
    | automática.
    |
    | El valor se expresa en horas.
    |
    */

    'manual_checkout_expiration_hours' => env(
        'BILLING_MANUAL_CHECKOUT_EXPIRATION_HOURS',
        24
    ),

    /*
    |--------------------------------------------------------------------------
    | Payment recovery
    |--------------------------------------------------------------------------
    */

    'grace_period_days' => 7,

    'retry_schedule_hours' => [
        24,
        72,
        144,
    ],

    'payment_gateway' => env(
        'BILLING_PAYMENT_GATEWAY',
        'fake'
    ),
];
