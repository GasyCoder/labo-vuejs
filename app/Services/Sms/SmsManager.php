<?php

namespace App\Services\Sms;

use App\Contracts\SmsDriverInterface;
use App\Models\Setting;
use InvalidArgumentException;

class SmsManager
{
    /**
     * Obtenir le driver SMS actif.
     */
    public function driver(?string $name = null): SmsDriverInterface
    {
        $provider = \App\Models\SmsProvider::getActive();

        $name = $name ?? $provider?->driver ?? $this->getDefaultDriver();

        $drivers = config('sms.drivers');

        if (! isset($drivers[$name])) {
            throw new InvalidArgumentException("Le driver SMS [{$name}] n'est pas configure.");
        }

        $class = $drivers[$name]['class'];

        if ($provider && $provider->driver === $name) {
            return new $class($provider->credentials ?? []);
        }

        return app($class);
    }

    /**
     * Obtenir le nom du driver par defaut depuis les settings ou config.
     */
    public function getDefaultDriver(): string
    {
        $setting = Setting::first();

        return $setting?->sms_driver ?? config('sms.default', 'mapi');
    }

    /**
     * Obtenir la liste des drivers disponibles avec leurs labels.
     *
     * @return array<string, string>
     */
    public function getAvailableDrivers(): array
    {
        $drivers = [];

        foreach (config('sms.drivers', []) as $key => $driverConfig) {
            $drivers[$key] = $driverConfig['label'];
        }

        return $drivers;
    }

    /**
     * Obtenir la definition des champs de configuration pour un driver.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getDriverFields(string $driverName): array
    {
        return config("sms.drivers.{$driverName}.fields", []);
    }

    /**
     * Obtenir les valeurs actuelles des champs d'un driver depuis l'env.
     *
     * @return array<string, string>
     */
    public function getDriverValues(string $driverName): array
    {
        $fields = $this->getDriverFields($driverName);
        $values = [];

        foreach ($fields as $fieldKey => $fieldConfig) {
            $envKey = $fieldConfig['env'] ?? '';
            $defaultValue = $fieldConfig['default'] ?? '';

            if (! empty($envKey)) {
                $values[$fieldKey] = env($envKey, $defaultValue);
            } else {
                $values[$fieldKey] = $defaultValue;
            }
        }

        return $values;
    }

    /**
     * Obtenir toutes les configs SMS pour le frontend (tous les drivers).
     *
     * @return array<string, mixed>
     */
    public function getAllDriverConfigs(): array
    {
        $configs = [];

        foreach (config('sms.drivers', []) as $key => $driverConfig) {
            $configs[$key] = [
                'label' => $driverConfig['label'],
                'fields' => $driverConfig['fields'],
                'values' => $this->getDriverValues($key),
            ];
        }

        return $configs;
    }
}
