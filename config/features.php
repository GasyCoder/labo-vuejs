<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Available Premium Features
    |--------------------------------------------------------------------------
    |
    | This file contains the registry of all available features that can
    | be toggled on or off for specific clients/tenants.
    |
    */

    'list' => [
        'prescriptions_tracking' => [
            'name' => 'Suivi Opérationnel SaaS',
            'description' => 'Vue tableau de bord détaillée pour le suivi des prescriptions.',
        ],
        'notifications_sms_email_validated' => [
            'name' => 'Notifications SMS & Email (Résultats)',
            'description' => 'Envoi automatique de SMS ou Email aux patients lorsque l\'analyse est validée.',
        ],
        'journal_decaissement' => [
            'name' => 'Journal de Décaissement',
            'description' => 'Accès et gestion du journal de décaissement pour les secrétaires.',
        ],
        'patient_invoice_email' => [
            'name' => 'Envoi de facture par Email',
            'description' => 'Bouton d\'envoi de la facture du patient au format PDF par email depuis la page patient.',
        ],
    ],

];
