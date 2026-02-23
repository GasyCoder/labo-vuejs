<?php

namespace Database\Seeders;

use App\Models\Prescripteur;
use Illuminate\Database\Seeder;

class PrescripteurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prescripteurs = [
            [
                'nom' => 'RAKOTO',
                'prenom' => 'Jean',
                'grade' => 'Dr',
                'status' => 'Medecin',
                'telephone' => '+261 32 12 345 67',
                'is_active' => true,
                'notes' => 'Prescripteur régulier, spécialisé en médecine générale',
            ],
            [
                'nom' => 'RANDRIAMAHEFA',
                'prenom' => 'Marie',
                'grade' => 'Dr',
                'status' => 'Medecin',
                'telephone' => '+261 33 45 678 90',
                'is_active' => true,
                'notes' => 'Cardiologue expérimentée',
            ],
            [
                'nom' => 'ANDRIANAIVORAVELONA',
                'prenom' => 'Paul',
                'grade' => 'Dr',
                'status' => 'Medecin',
                'telephone' => '+261 34 56 789 01',
                'is_active' => true,
                'notes' => 'Pédiatre réputé, travaille principalement avec les enfants',
            ],
            [
                'nom' => 'RASOANAIVO',
                'prenom' => 'Hanta',
                'grade' => 'Dr',
                'status' => 'Professeur',
                'telephone' => '+261 32 67 890 12',
                'is_active' => true,
                'notes' => 'Gynécologue-obstétricienne',
            ],
        ];

        foreach ($prescripteurs as $prescripteur) {
            Prescripteur::create($prescripteur);
        }

        $this->command->info(count($prescripteurs).' prescripteurs créés avec succès !');
    }
}
