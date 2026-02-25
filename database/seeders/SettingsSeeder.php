<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::updateOrCreate(
            ['id' => 1], // Critère de recherche
            [
                'nom_entreprise' => 'LABO',
                'nif' => '1234567890',
                'statut' => 'SARL',
                'format_unite_argent' => 'MGA',
                'logo' => null, // Sera défini via l'interface admin
                'favicon' => null, // Sera défini via l'interface admin
                'remise_pourcentage' => 0,
                'activer_remise' => true,
                'commission_prescripteur' => true,
                'commission_prescripteur_pourcentage' => 10,
            ]
        );
    }
}
