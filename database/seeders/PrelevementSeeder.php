<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrelevementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. S'assurer que les types de tubes existent d'abord
        $this->createTypeTubesIfNotExists();

        // 2. Récupérer les IDs des types de tubes
        $tubeRouge = DB::table('type_tubes')->where('code', 'SEC')->first()?->id;
        $tubeEcouvillon = DB::table('type_tubes')->where('code', 'ECOUVILLON')->first()?->id;

        $prelevements = [
            [
                'denomination' => 'Prélèvement avec écouvillon stérile',
                'prix' => 15.00,
                'prix_promotion' => 0,
                'quantite' => 1,
                'is_active' => true,
                'type_tube_id' => $tubeEcouvillon,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'denomination' => 'Prélèvement sanguin',
                'prix' => 25.00,
                'prix_promotion' => 0,
                'quantite' => 1,
                'is_active' => true,
                'type_tube_id' => $tubeRouge,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'denomination' => 'Prélèvement sanguin avec HGPO',
                'prix' => 35.00,
                'prix_promotion' => 0,
                'quantite' => 1,
                'is_active' => true,
                'type_tube_id' => $tubeRouge,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'denomination' => 'Prélèvement sanguin avec G50',
                'prix' => 30.00,
                'prix_promotion' => 0,
                'quantite' => 1,
                'is_active' => true,
                'type_tube_id' => $tubeRouge,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('prelevements')->insert($prelevements);

        $this->command->info('✅ Prélèvements créés avec types de tubes recommandés');
    }

    /**
     * Créer les types de tubes de base si ils n'existent pas
     */
    private function createTypeTubesIfNotExists()
    {
        $typeTubes = [
            ['code' => 'SEC', 'couleur' => 'Rouge'],
            ['code' => 'CITR', 'couleur' => 'Bleu'],
            ['code' => 'EDTA', 'couleur' => 'Violet'],
            ['code' => 'HEPA', 'couleur' => 'Vert'],
            ['code' => 'FLACON', 'couleur' => 'Transparent'],
            ['code' => 'ECOUVILLON', 'couleur' => 'Blanc'],
        ];

        foreach ($typeTubes as $type) {
            DB::table('type_tubes')->insertOrIgnore([
                'code' => $type['code'],
                'couleur' => $type['couleur'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
