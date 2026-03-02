<?php

namespace App\Providers;

use App\Contracts\SmsDriverInterface;
use App\Services\Sms\SmsManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SmsManager::class);

        $this->app->bind(SmsDriverInterface::class, function ($app) {
            return $app->make(SmsManager::class)->driver();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // On surcharge le nom de l'application dynamiquement depuis la base de données
        try {
            $siteName = \App\Models\Setting::getSiteName();
            config(['app.name' => $siteName]);
        } catch (\Exception $e) {
            // Éviter les plantages si la table n'existe pas encore (ex: migrations)
        }
    }
}
