<?php

return [
    'premium' => [
        'name' => 'Pack Premium',
        'price' => 250000,
        'currency' => 'Ar',
        'interval' => 'mois',
        'sms_quota' => 500,
        'email_quota' => 5000,
        'features' => [
            'prescriptions_tracking',
            'notifications_sms_email_validated',
            'journal_decaissement',
            'patient_invoice_email',
        ],
    ],
];
