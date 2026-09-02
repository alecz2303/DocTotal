<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Communication transports
    |--------------------------------------------------------------------------
    |
    | Cada canal puede tener una implementación concreta de
    | CommunicationTransport.
    |
    | Un valor null significa que el canal todavía no tiene un proveedor
    | configurado y, por lo tanto, no debe intentar enviar comunicaciones.
    |
    */

    'transports' => [

        'email' => null,

        'whatsapp' => null,

        'sms' => null,

    ],

];
