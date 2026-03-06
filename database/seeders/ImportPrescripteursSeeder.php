<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportPrescripteursSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('clean_prescripteurs.sql');

        if (!File::exists($path)) {
            $this->command->error("Le fichier SQL n'existe pas : {$path}");
            return;
        }

        $sql = File::get($path);

        // Supprimer les commentaires simples (--) pour éviter les erreurs de parsing si on splitte
        $sql = preg_replace('/--.*$/m', '', $sql);
        
        // Séparer les requêtes par point-virgule pour exécution individuelle si nécessaire
        // Mais comme il y a des transactions et tables temporaires, l'exécution brute est préférable
        try {
            DB::unprepared($sql);
            $this->command->info('Importation et nettoyage des prescripteurs terminés avec succès.');
        } catch (\Exception $e) {
            $this->command->error('Erreur lors de l\'importation : ' . $e->getMessage());
        }
    }
}
