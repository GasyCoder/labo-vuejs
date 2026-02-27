<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeTubesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $typeTubes = [
            ['code' => 'SEC',       'couleur' => 'Rouge',       'created_at' => $now, 'updated_at' => $now],
            ['code' => 'CITR',      'couleur' => 'Bleu',        'created_at' => $now, 'updated_at' => $now],
            ['code' => 'EDTA',      'couleur' => 'Violet',      'created_at' => $now, 'updated_at' => $now],
            ['code' => 'HEPA',      'couleur' => 'Vert',        'created_at' => $now, 'updated_at' => $now],
            ['code' => 'FLACON',    'couleur' => 'Transparent', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'ECOUVILLON','couleur' => 'Blanc',       'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('type_tubes')->upsert(
            $typeTubes,
            ['code'],        // clé unique
            ['couleur', 'updated_at'] // colonnes à mettre à jour si existe
        );
    }
}