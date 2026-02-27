<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Scheduler: on garde uniquement les tâches planifiées,
 * le worker queue tourne via CRON cPanel séparé.
 */

// Nettoyage quotidien des jobs échoués datant de plus de 48h.
Schedule::command('queue:prune-failed --hours=48')->daily();

// Nettoyage des jetons d'authentification expirés
Schedule::command('auth:clear-resets')->everyFifteenMinutes();