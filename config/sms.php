<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default SMS Driver
    |--------------------------------------------------------------------------
    |
    | Le driver SMS actif. Peut etre change dynamiquement via les parametres
    | de l'application. Drivers disponibles: "mapi", "orange".
    |
    */

    'default' => env('SMS_DRIVER', 'mapi'),

    /*
    |--------------------------------------------------------------------------
    | SMS Drivers
    |--------------------------------------------------------------------------
    |
    | Configuration de chaque driver SMS. Les cles correspondent aux noms
    | de drivers utilises dans le selecteur.
    |
    */

    'drivers' => [

        'mapi' => [
            'label' => 'MAPI SMS (Madagascar)',
            'class' => App\Services\MapiSmsService::class,
            'fields' => [
                'api_url' => [
                    'label' => "URL de l'API",
                    'type' => 'url',
                    'env' => 'MAPI_SMS_URL',
                    'default' => 'https://messaging.mapi.mg/api',
                    'required' => true,
                    'full_width' => true,
                ],
                'username' => [
                    'label' => 'Utilisateur',
                    'type' => 'text',
                    'env' => 'MAPI_SMS_USERNAME',
                    'default' => '',
                    'required' => true,
                ],
                'password' => [
                    'label' => 'Mot de passe',
                    'type' => 'password',
                    'env' => 'MAPI_SMS_PASSWORD',
                    'default' => '',
                    'required' => true,
                ],
            ],
        ],

        'orange' => [
            'label' => 'Orange SMS (Madagascar)',
            'class' => App\Services\OrangeSmsService::class,
            'fields' => [
                'client_id' => [
                    'label' => 'Client ID',
                    'type' => 'password',
                    'env' => 'ORANGE_SMS_CLIENT_ID',
                    'default' => '',
                    'required' => true,
                    'full_width' => true,
                ],
                'client_secret' => [
                    'label' => 'Client Secret',
                    'type' => 'text',
                    'env' => 'ORANGE_SMS_CLIENT_SECRET',
                    'default' => '',
                    'required' => true,
                ],
                'sender_name' => [
                    'label' => "Nom d'expediteur",
                    'type' => 'text',
                    'env' => 'ORANGE_SMS_SENDER_NAME',
                    'default' => 'LA REFERENCE',
                    'required' => false,
                    'max' => 11,
                    'hint' => 'Maximum 11 caracteres',
                ],
            ],
        ],

        'custom' => [
            'label' => 'API Personnalisée (Custom HTTP)',
            'class' => App\Services\Sms\CustomSmsService::class,
            'fields' => [
                'api_url' => [
                    'label' => 'URL de l\'API',
                    'type' => 'url',
                    'env' => '',
                    'default' => 'https://api.monsms.com/send',
                    'required' => true,
                    'full_width' => true,
                ],
                'http_method' => [
                    'label' => 'Méthode HTTP (GET/POST)',
                    'type' => 'text',
                    'env' => '',
                    'default' => 'POST',
                    'required' => true,
                    'hint' => 'Saisir GET ou POST',
                ],
                'phone_param_name' => [
                    'label' => 'Nom du paramètre (Numéro)',
                    'type' => 'text',
                    'env' => '',
                    'default' => 'to',
                    'required' => true,
                    'hint' => 'Clé contenant le numéro (ex: to, dest, msisdn)',
                ],
                'message_param_name' => [
                    'label' => 'Nom du paramètre (Message)',
                    'type' => 'text',
                    'env' => '',
                    'default' => 'text',
                    'required' => true,
                    'hint' => 'Clé contenant le texte (ex: text, message, msg)',
                ],
                'authorization_header' => [
                    'label' => 'En-tête Authorization',
                    'type' => 'text',
                    'env' => '',
                    'default' => '',
                    'required' => false,
                    'hint' => 'Ex: Bearer VOTRE_TOKEN',
                    'full_width' => true,
                ],
                'extra_payload_json' => [
                    'label' => 'Paramètres supplémentaires (JSON)',
                    'type' => 'text',
                    'env' => '',
                    'default' => '{}',
                    'required' => false,
                    'hint' => 'Ex: {"sender": "MaBoite", "type": "sms"}',
                    'full_width' => true,
                ],
            ],
        ],

    ],

];
