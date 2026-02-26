<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Configuration du Scheduler pour hébergement mutualisé.
 * Centralise le traitement de la file d'attente sans nécessiter de Daemon permanent.
 */

// Lance le worker de file d'attente toutes les minutes.
// --stop-when-empty : Le processus s'arrête proprement dès que la file est vide (économise les ressources CPU).
// withoutOverlapping : Utilise le cache (database) pour empêcher le lancement de plusieurs workers simultanés.
Schedule::command('queue:work --stop-when-empty --tries=3 --timeout=60')
    ->everyMinute()
    ->withoutOverlapping();

// Nettoyage quotidien des jobs échoués datant de plus de 48h.
Schedule::command('queue:prune-failed --hours=48')->daily();

// Nettoyage des jetons d'authentification expirés (si applicable).
Schedule::command('auth:clear-resets')->everyFifteenMinutes();
