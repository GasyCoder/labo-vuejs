<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientFeature;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientFeatureSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create a default client for existing data
        $client = Client::firstOrCreate(
            ['name' => 'Laboratoire Principal'],
            ['is_active' => true]
        );

        // 2. Attach existing users to this main client
        User::whereNull('client_id')->update(['client_id' => $client->id]);

        // 3. Define the default features settings for this client (OFF by default)
        $features = [
            'prescriptions_tracking' => false,
            'notifications_sms_email_validated' => false,
            'journal_decaissement' => false,
            'patient_invoice_email' => false,
        ];

        foreach ($features as $key => $enabled) {
            ClientFeature::updateOrCreate(
                ['client_id' => $client->id, 'feature_key' => $key],
                ['is_enabled' => $enabled]
            );
        }
    }
}
