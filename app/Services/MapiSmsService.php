<?php

namespace App\Services;

use App\Contracts\SmsDriverInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MapiSmsService implements SmsDriverInterface
{
    protected string $username;

    protected string $password;

    protected string $apiUrl;

    /**
     * @param  array<string, string>  $config
     */
    public function __construct(array $config = [])
    {
        $this->username = $config['username'] ?? config('services.mapi_sms.username', '');
        $this->password = $config['password'] ?? config('services.mapi_sms.password', '');
        $this->apiUrl = $config['api_url'] ?? config('services.mapi_sms.api_url', 'https://messaging.mapi.mg/api');
    }

    /**
     * Convertir tout format de numero vers le format local 03xxxxxxxx
     */
    public function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-\.\(\)]/', '', $phone);

        if (str_starts_with($phone, 'tel:')) {
            $phone = substr($phone, 4);
        }

        if (str_starts_with($phone, '+261')) {
            $phone = '0'.substr($phone, 4);
        }

        if (str_starts_with($phone, '261') && strlen($phone) === 12) {
            $phone = '0'.substr($phone, 3);
        }

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
            if ($token) {
                Http::withHeaders(['Authorization' => $token])
                    ->post($this->apiUrl.'/authentication/logout');
            } else {
                Http::asMultipart()->post($this->apiUrl.'/authentication/logout', [
                    ['name' => 'Username', 'contents' => $this->username],
                    ['name' => 'Password', 'contents' => $this->password],
                ]);
            }
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
     * Login MAPI avec gestion de la session deja active
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

        if (
            isset($data['status']) && $data['status'] === false
            && isset($data['message']) && str_contains($data['message'], 'en cours de session')
        ) {
            Log::info('MAPI SMS - Session active détectée mais impossible à clore sans le jeton actuel. Veuillez patienter la fin de session (environ 1 heure) ou contacter le support MAPI.');
            throw new \Exception('Session SMS MAPI verrouillée par une autre connexion. Veuillez réessayer plus tard.');
        }

        if (! $response->successful() || empty($data['status']) || $data['status'] !== true) {
            $msg = $data['message'] ?? $response->body();
            Log::error('MAPI SMS - Echec authentification', ['body' => $response->body()]);
            throw new \Exception('Echec authentification SMS : '.$msg);
        }

        $token = $data['token'];
        // Cache le token pendant 24 heures. Laissons le service MAPI décider quand la session expire vraiment.
        // Si elle expire, le serveur renverra 401 et notre bloc sendSms réessaiera automatiquement.
        Cache::put($cacheKey, $token, 86400);

        return $token;
    }

    /**
     * Verifier que le service MAPI est accessible
     */
    public function checkService(): array
    {
        try {
            $this->getToken();

            return ['ok' => true, 'message' => 'Service SMS operationnel'];
        } catch (\Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Envoyer un SMS via l'API MAPI
     */
    public function sendSms(string $phoneNumber, string $message, bool $isRetry = false): array
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
            Log::error('MAPI SMS - Echec envoi', [
                'phone' => $recipient,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->status() === 401 || str_contains(strtolower($msg), 'unauthorized') || str_contains(strtolower($msg), 'token')) {
                Cache::forget('mapi_sms_token');

                if (! $isRetry) {
                    Log::info('MAPI SMS - Session expiree, tentative de reconnexion automatique...');

                    return $this->sendSms($phoneNumber, $message, true);
                }

                throw new \Exception('Session SMS expiree et impossible de se reconnecter. Attendez quelques minutes que MAPI ferme la session.');
            }

            throw new \Exception('Erreur envoi SMS : '.$msg);
        }

        Log::info('MAPI SMS envoye', [
            'phone' => $recipient,
            'response' => $data,
        ]);

        return $data;
    }
}
