<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MapiSmsService
{
    protected string $username;

    protected string $password;

    protected string $apiUrl;

    public function __construct()
    {
        $config = config('services.mapi_sms');

        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
        $this->apiUrl = $config['api_url'] ?? 'https://messaging.mapi.mg/api';
    }

    /**
     * Convertir tout format de numéro vers le format local 03xxxxxxxx
     */
    public function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-\.\(\)]/', '', $phone);

        // Retirer le préfixe tel:
        if (str_starts_with($phone, 'tel:')) {
            $phone = substr($phone, 4);
        }

        // +261327627443 → 0327627443
        if (str_starts_with($phone, '+261')) {
            $phone = '0'.substr($phone, 4);
        }

        // 261327627443 → 0327627443
        if (str_starts_with($phone, '261') && strlen($phone) === 12) {
            $phone = '0'.substr($phone, 3);
        }

        // S'assurer que ça commence par 0
        if (! str_starts_with($phone, '0')) {
            $phone = '0'.$phone;
        }

        return $phone;
    }

    /**
     * Logout de la session MAPI en cours
     */
    public function logout(string $token = ''): void
    {
        try {
            $headers = [];
            if ($token) {
                $headers['Authorization'] = $token;
            }
            Http::withHeaders($headers)->post($this->apiUrl.'/authentication/logout');
        } catch (\Exception $e) {
            // Ignorer les erreurs de logout
        }

        Cache::forget('mapi_sms_token');
    }

    /**
     * Obtenir un token MAPI (avec cache de 14 min)
     */
    public function getToken(bool $forceLogin = false): string
    {
        $cacheKey = 'mapi_sms_token';

        if (! $forceLogin && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        return $this->login();
    }

    /**
     * Login MAPI avec gestion de la session déjà active
     */
    protected function login(): string
    {
        $cacheKey = 'mapi_sms_token';

        try {
            $response = Http::asMultipart()->post($this->apiUrl.'/authentication/login', [
                ['name' => 'Username', 'contents' => $this->username],
                ['name' => 'Password', 'contents' => $this->password],
            ]);
        } catch (\Exception $e) {
            Log::error('MAPI SMS - Erreur connexion authentification', ['error' => $e->getMessage()]);
            throw new \Exception('Erreur de connexion au service SMS. Vérifiez votre connexion internet.');
        }

        $data = $response->json();

        // Session déjà active → logout puis réessayer
        if (
            isset($data['status']) && $data['status'] === false
            && isset($data['message']) && str_contains($data['message'], 'en cours de session')
        ) {
            Log::info('MAPI SMS - Session active détectée, logout puis reconnexion');
            $this->logout();

            try {
                $response = Http::asMultipart()->post($this->apiUrl.'/authentication/login', [
                    ['name' => 'Username', 'contents' => $this->username],
                    ['name' => 'Password', 'contents' => $this->password],
                ]);
            } catch (\Exception $e) {
                throw new \Exception('Erreur de connexion au service SMS.');
            }

            $data = $response->json();
        }

        if (! $response->successful() || empty($data['status']) || $data['status'] !== true) {
            $msg = $data['message'] ?? $response->body();
            Log::error('MAPI SMS - Échec authentification', ['body' => $response->body()]);
            throw new \Exception('Échec authentification SMS : '.$msg);
        }

        $token = $data['token'];
        Cache::put($cacheKey, $token, 840);

        return $token;
    }

    /**
     * Vérifier que le service MAPI est accessible (pré-vérification)
     */
    public function checkService(): array
    {
        try {
            $this->getToken();

            return ['ok' => true, 'message' => 'Service SMS opérationnel'];
        } catch (\Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Envoyer un SMS via l'API MAPI
     */
    public function sendSms(string $phoneNumber, string $message): array
    {
        $recipient = $this->formatPhone($phoneNumber);
        $token = $this->getToken();

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->asMultipart()->post($this->apiUrl.'/msg/send', [
                ['name' => 'Recipient', 'contents' => $recipient],
                ['name' => 'Message', 'contents' => $message],
                ['name' => 'Channel', 'contents' => 'sms'],
            ]);
        } catch (\Exception $e) {
            Log::error('MAPI SMS - Erreur connexion envoi', ['error' => $e->getMessage()]);
            throw new \Exception('Erreur de connexion au service SMS. Vérifiez votre connexion internet.');
        }

        $data = $response->json();

        if (! $response->successful() || empty($data['status']) || $data['status'] !== true) {
            $msg = $data['message'] ?? $response->body();
            Log::error('MAPI SMS - Échec envoi', [
                'phone' => $recipient,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // Token expiré → invalider le cache et réessayer une fois
            if ($response->status() === 401 || str_contains(strtolower($msg), 'unauthorized') || str_contains(strtolower($msg), 'token')) {
                Cache::forget('mapi_sms_token');
                throw new \Exception('Session SMS expirée. Réessayez l\'envoi.');
            }

            throw new \Exception('Erreur envoi SMS : '.$msg);
        }

        Log::info('MAPI SMS envoyé', [
            'phone' => $recipient,
            'response' => $data,
        ]);

        return $data;
    }
}
