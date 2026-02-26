<?php

namespace App\Services;

use App\Models\ClientFeature;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class FeatureService
{
    /**
     * Cache key prefix for client features.
     */
    private const CACHE_PREFIX = 'client_features_';

    /**
     * Cache TTL in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Check if a specific feature is enabled for a given client ID.
     */
    public function isEnabled(?int $clientId, string $featureKey): bool
    {
        // If there's no client ID (e.g. system command or superadmin without client),
        // we might deny by default, but typically users belong to a client now.
        if (! $clientId) {
            return false;
        }

        $features = $this->getAllForClient($clientId);

        return $features[$featureKey] ?? false;
    }

    /**
     * Get all feature toggles for a client as an associative array [feature_key => boolean].
     */
    public function getAllForClient(int $clientId): array
    {
        $cacheKey = self::CACHE_PREFIX.$clientId;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($clientId) {
            $toggles = ClientFeature::where('client_id', $clientId)->pluck('is_enabled', 'feature_key')->toArray();

            $registeredFeatures = config('features.list', []);
            $result = [];

            foreach ($registeredFeatures as $key => $config) {
                // If the feature is present in DB, use it. Otherwise, default to false.
                $result[$key] = $toggles[$key] ?? false;
            }

            return $result;
        });
    }

    /**
     * Enable or disable a feature for a client.
     */
    public function setEnabled(int $clientId, string $featureKey, bool $enabled): void
    {
        ClientFeature::updateOrCreate(
            ['client_id' => $clientId, 'feature_key' => $featureKey],
            ['is_enabled' => $enabled]
        );

        $this->clearCache($clientId);
    }

    /**
     * Fast check helper using the current authenticated user's client.
     */
    public function isEnabledForCurrentUser(string $featureKey): bool
    {
        $user = auth()->user();
        if (! $user || ! $user->client_id) {
            return false; // Or true if Superadmin dictates. We keep it strictly restricted.
        }

        return $this->isEnabled($user->client_id, $featureKey);
    }

    /**
     * Get all features for the current user.
     */
    public function getAllForCurrentUser(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        // Superadmins see everything as enabled
        if ($user->type === 'superadmin') {
            $registeredFeatures = config('features.list', []);
            $result = [];
            foreach ($registeredFeatures as $key => $config) {
                $result[$key] = true;
            }

            return $result;
        }

        if (! $user->client_id) {
            return [];
        }

        return $this->getAllForClient($user->client_id);
    }

    /**
     * Invalidate cache for a specific client.
     */
    public function clearCache(int $clientId): void
    {
        Cache::forget(self::CACHE_PREFIX.$clientId);
    }
}
