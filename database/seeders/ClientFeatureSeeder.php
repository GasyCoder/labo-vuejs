<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\ClientFeature;
use App\Models\User;

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

        // 3. Define the default features settings for this client (currently turning them ON by default for smooth transition)
        // If we want features strictly OFF, change is_enabled to false.
        $features = [
            'prescriptions_tracking' => true,
            'notifications_sms_email_validated' => true,
            'journal_decaissement' => true,
            'patient_invoice_email' => true,
        ];

        foreach ($features as $key => $enabled) {
            ClientFeature::updateOrCreate(
                ['client_id' => $client->id, 'feature_key' => $key],
                ['is_enabled' => $enabled]
            );
        }
    }
}
