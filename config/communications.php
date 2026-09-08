<?php

use App\Services\Communications\Transports\LaravelMailCommunicationTransport;

return [

    /*
    |--------------------------------------------------------------------------
    | Communication transports
    |--------------------------------------------------------------------------
    |
    | Cada canal puede tener una implementación concreta de
    | CommunicationTransport. Los canales externos permanecen deshabilitados
    | hasta que su configuración productiva sea explícita.
    |
    */

    'transports' => [

        'email' => env('DOCTOTAL_EMAIL_COMMUNICATIONS_ENABLED', false)
            ? LaravelMailCommunicationTransport::class
            : null,

        'whatsapp' => null,

        'sms' => null,

    ],

    'email' => [
        'runbook' => env(
            'DOCTOTAL_EMAIL_RUNBOOK',
            'docs/OPERATIONS_EMAIL_DELIVERY.md'
        ),
    ],

];
