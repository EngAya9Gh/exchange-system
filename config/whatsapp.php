<?php

return [
    'default' => env('WHATSAPP_PROVIDER', 'mock'),

    'providers' => [
        'mock' => [],
        
        'ultramsg' => [
            'instance_id' => env('WHATSAPP_ULTRAMSG_INSTANCE_ID'),
            'token' => env('WHATSAPP_ULTRAMSG_TOKEN'),
        ],

        'whapi' => [
            'api_url' => env('WHATSAPP_WHAPI_API_URL', 'https://gate.whapi.cloud'),
            'token' => env('WHATSAPP_WHAPI_TOKEN'),
        ],

        'baileys' => [
            'api_url' => env('WHATSAPP_BAILEYS_API_URL'),
            'api_key' => env('WHATSAPP_BAILEYS_API_KEY'),
        ],
    ],
];
