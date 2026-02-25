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
    public function boot(): void {}
}
