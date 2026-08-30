<?php

return [

    'documents' => [

        /*
        |--------------------------------------------------------------------------
        | Clinical documents disk
        |--------------------------------------------------------------------------
        |
        | Defines the default filesystem disk used for newly uploaded
        | clinical documents.
        |
        | Existing documents keep their own disk value in the database,
        | allowing gradual migration between storage providers.
        |
        */

        'disk' => env(
            'CLINICAL_DOCUMENTS_DISK',
            'local'
        ),

    ],

];
