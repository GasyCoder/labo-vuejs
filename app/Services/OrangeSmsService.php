<?php

namespace App\Services;

use App\Exceptions\OrangeSmsException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrangeSmsService
{
    protected string $clientId;

    protected string $clientSecret;

    protected string $senderName;

    protected string $senderNumber;

    protected string $authUrl;

    protected string $apiUrl;

    public function __construct()
    {
        $config = config('services.orange_sms');

        $this->clientId = $config['client_id'] ?? '';
        $this->clientSecret = $config['client_secret'] ?? '';
        $this->senderName = $config['sender_name'] ?? 'Lareference';
        $this->senderNumber = $config['sender_number'] ?? 'tel:+261341234567';
        $this->authUrl = $config['auth_url'] ?? 'https://api.orange.com/oauth/v3/token';
        $this->apiUrl = $config['api_url'] ?? 'https://api.orange.com/smsmessaging/v1/outbound';
    }

    /**
     * Formater un numéro local en format international tel:+261...
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
            Log::error('Orange SMS OAuth2 - Échec authentification', [
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
     * Vérifier le statut du contrat SMS en tentant d'obtenir les infos d'achat
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
                    return ['ok' => false, 'message' => 'Forfait SMS Orange expiré. Veuillez renouveler votre bundle sur developer.orange.com'];
                }

                return ['ok' => false, 'message' => 'Accès refusé au service SMS Orange'];
            }

            return ['ok' => true, 'message' => 'Service SMS opérationnel'];

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

        try {
            $token = $this->getAccessToken();
        } catch (OrangeSmsException $e) {
            throw $e;
        }

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
            Log::error('Orange SMS - Échec envoi', [
                'phone' => $recipientAddress,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw OrangeSmsException::fromApiResponse($response->status(), $response->body());
        }

        Log::info('Orange SMS envoyé', [
            'phone' => $recipientAddress,
            'status' => $response->status(),
        ]);

        return $response->json();
    }
}
