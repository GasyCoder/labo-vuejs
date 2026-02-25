<?php

namespace App\Services;

use App\Contracts\SmsDriverInterface;
use App\Exceptions\OrangeSmsException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrangeSmsService implements SmsDriverInterface
{
    protected string $clientId;

    protected string $clientSecret;

    protected string $senderName;

    protected string $senderNumber;

    protected string $authUrl;

    protected string $apiUrl;

    /**
     * @param  array<string, string>  $config
     */
    public function __construct(array $config = [])
    {
        $fallback = config('services.orange_sms', []);

        $this->clientId = $config['client_id'] ?? $fallback['client_id'] ?? '';
        $this->clientSecret = $config['client_secret'] ?? $fallback['client_secret'] ?? '';
        $this->senderName = $config['sender_name'] ?? $fallback['sender_name'] ?? 'Lareference';
        $this->senderNumber = $config['sender_number'] ?? $fallback['sender_number'] ?? 'tel:+261341234567';
        $this->authUrl = $config['auth_url'] ?? $fallback['auth_url'] ?? 'https://api.orange.com/oauth/v3/token';
        $this->apiUrl = $config['api_url'] ?? $fallback['api_url'] ?? 'https://api.orange.com/smsmessaging/v1/outbound';
    }

    /**
     * Formater un numero local en format international tel:+261...
     */
    public function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[\s\-\.\(\)]/', '', $phone);

        if (str_starts_with($phone, 'tel:')) {
            return $phone;
        }

        if (str_starts_with($phone, '+261')) {
            return 'tel:'.$phone;
        }

        if (str_starts_with($phone, '261')) {
            return 'tel:+'.$phone;
        }

        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        return 'tel:+261'.$phone;
    }

    /**
     * Obtenir un access token OAuth2 (avec cache)
     */
    public function getAccessToken(): string
    {
        $cacheKey = 'orange_sms_access_token';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $credentials = base64_encode($this->clientId.':'.$this->clientSecret);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic '.$credentials,
            ])->asForm()->post($this->authUrl, [
                'grant_type' => 'client_credentials',
            ]);
        } catch (\Exception $e) {
            Log::error('Orange SMS OAuth2 - Erreur connexion', ['error' => $e->getMessage()]);
            throw OrangeSmsException::connectionFailed($e);
        }

        if (! $response->successful()) {
            Log::error('Orange SMS OAuth2 - Echec authentification', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw OrangeSmsException::authenticationFailed($response->status(), $response->body());
        }

        $data = $response->json();
        $token = $data['access_token'];
        $expiresIn = $data['expires_in'] ?? 3600;

        Cache::put($cacheKey, $token, now()->addSeconds($expiresIn - 60));

        return $token;
    }

    /**
     * Verifier que le service SMS est accessible.
     */
    public function checkService(): array
    {
        return $this->checkContractStatus();
    }

    /**
     * Verifier le statut du contrat SMS
     */
    public function checkContractStatus(): array
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->get($this->apiUrl.'/'.urlencode($this->senderNumber).'/requests');

            if ($response->status() === 403) {
                $data = json_decode($response->body(), true);
                $policyMsg = $data['requestError']['policyException']['variables'][0] ?? '';

                if (str_contains($policyMsg, 'Expired contract')) {
                    return ['ok' => false, 'message' => 'Forfait SMS Orange expire. Veuillez renouveler votre bundle sur developer.orange.com'];
                }

                return ['ok' => false, 'message' => 'Acces refuse au service SMS Orange'];
            }

            return ['ok' => true, 'message' => 'Service SMS operationnel'];

        } catch (OrangeSmsException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Envoyer un SMS via l'API Orange
     */
    public function sendSms(string $phoneNumber, string $message): array
    {
        $recipientAddress = $this->formatPhoneNumber($phoneNumber);

        $token = $this->getAccessToken();

        $senderAddress = $this->senderNumber;
        $endpoint = $this->apiUrl.'/'.urlencode($senderAddress).'/requests';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->post($endpoint, [
                'outboundSMSMessageRequest' => [
                    'address' => $recipientAddress,
                    'senderAddress' => $senderAddress,
                    'senderName' => $this->senderName,
                    'outboundSMSTextMessage' => [
                        'message' => $message,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Orange SMS - Erreur connexion', ['error' => $e->getMessage()]);
            throw OrangeSmsException::connectionFailed($e);
        }

        if (! $response->successful()) {
            Log::error('Orange SMS - Echec envoi', [
                'phone' => $recipientAddress,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw OrangeSmsException::fromApiResponse($response->status(), $response->body());
        }

        Log::info('Orange SMS envoye', [
            'phone' => $recipientAddress,
            'status' => $response->status(),
        ]);

        return $response->json();
    }
}
