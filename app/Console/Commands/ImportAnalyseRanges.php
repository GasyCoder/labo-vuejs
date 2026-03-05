<?php

namespace App\Console\Commands;

use App\Models\Analyse;
use App\Models\AnalyseRange;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportAnalyseRanges extends Command
{
    protected $signature = 'analyses:migrate-ranges';
    protected $description = 'Transfère les anciennes valeurs de référence vers la nouvelle table analyse_ranges';

    public function handle()
    {
        $analyses = Analyse::all();
        $count = 0;

        $mapping = [
            'valeur_ref_homme' => 'HOMME',
            'valeur_ref_femme' => 'FEMME',
            'valeur_ref_enfant_garcon' => 'ENFANT_GARCON',
            'valeur_ref_enfant_fille' => 'ENFANT_FILLE',
        ];

        $this->info("Début de la migration pour " . $analyses->count() . " analyses...");

        foreach ($analyses as $analyse) {
            foreach ($mapping as $column => $context) {
                $rawVal = $analyse->$column;

                if (empty($rawVal)) continue;

                $parsed = $this->parseRange($rawVal);

                if ($parsed) {
                    AnalyseRange::updateOrCreate(
                        ['analyse_id' => $analyse->id, 'context' => $context],
                        [
                            'normal_min' => $parsed['min'],
                            'normal_max' => $parsed['max'],
                        ]
                    );
                    $count++;
                }
            }
        }

        $this->info("Migration terminée ! $count intervalles de référence ont été créés.");
    }

    /**
     * Analyse une chaîne comme "130 - 180" ou "4.00-10" pour extraire min et max.
     */
    private function parseRange($str)
    {
        // On nettoie la chaîne (espaces, virgules en points)
        $str = str_replace([' ', ','], ['', '.'], $str);

        // On cherche un séparateur courant (tiret, tiret long, barre oblique)
        // Cas 1 : "130-180"
        if (preg_match('/^([\d\.]+)-([\d\.]+)$/', $str, $matches)) {
            return ['min' => (float)$matches[1], 'max' => (float)$matches[2]];
        }

        // Cas 2 : "< 50" (seulement max)
        if (preg_match('/^<([\d\.]+)$/', $str, $matches)) {
            return ['min' => null, 'max' => (float)$matches[1]];
        }

        // Cas 3 : "> 50" (seulement min)
        if (preg_match('/^>([\d\.]+)$/', $str, $matches)) {
            return ['min' => (float)$matches[1], 'max' => null];
        }

        // Cas 4 : Valeur unique "1.00" (on met en max par défaut ou les deux)
        if (is_numeric($str)) {
            return ['min' => (float)$str, 'max' => (float)$str];
        }

        return null;
    }
}
