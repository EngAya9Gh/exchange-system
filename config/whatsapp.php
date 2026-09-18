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

        'wakeel' => [
            'api_key' => env('WAKEEL_WHATSAPP_API_KEY'),
            'group_id' => env('WAKEEL_WHATSAPP_GROUP_ID'),
        ],
    ],
];
