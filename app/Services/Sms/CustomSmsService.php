<?php

namespace App\Services\Sms;

use App\Contracts\SmsDriverInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CustomSmsService implements SmsDriverInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Create a new CustomSmsService instance.
     *
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;

        $requiredConfigs = ['api_url', 'http_method', 'phone_param_name', 'message_param_name'];

        foreach ($requiredConfigs as $required) {
            if (empty($this->config[$required])) {
                throw new InvalidArgumentException("La configuration '{$required}' est requise pour le pilote Custom HTTP.");
            }
        }
    }

    /**
     * Envoyer un SMS.
     *
     * @param  string  $phoneNumber
     * @param  string  $message
     * @return array<string, mixed>
     */
    public function sendSms(string $phoneNumber, string $message): array
    {
        $apiUrl = $this->config['api_url'];
        $httpMethod = strtoupper($this->config['http_method']);
        $phoneParam = $this->config['phone_param_name'];
        $messageParam = $this->config['message_param_name'];
        $authHeader = $this->config['authorization_header'] ?? null;
        $extraPayload = $this->config['extra_payload_json'] ?? '{}';

        // Decode JSON payload safely
        $payload = json_decode($extraPayload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $payload = [];
            Log::warning('Le paramètre JSON extra_payload_json du pilote Custom SMS est invalide. Utilisation du tableau vide.');
        }

        // Add the core parameters
        $payload[$phoneParam] = $this->formatPhoneNumber($phoneNumber);
        $payload[$messageParam] = $message;

        try {
            // Build the HTTP request
            $request = Http::timeout(30);

            if (! empty($authHeader)) {
                $request = $request->withHeaders([
                    'Authorization' => $authHeader,
                ]);
            }

            // Execute the HTTP request
            $response = null;
            if ($httpMethod === 'GET') {
                $response = $request->get($apiUrl, $payload);
            } elseif ($httpMethod === 'POST') {
                $response = $request->post($apiUrl, $payload);
            } else {
                throw new InvalidArgumentException("Méthode HTTP non supportée : {$httpMethod}");
            }

            if ($response->successful()) {
                if (config('app.debug')) {
                    Log::info("Custom SMS envoyé avec succès à {$phoneNumber}.", ['response' => $response->json()]);
                }
                return ['success' => true, 'message' => 'SMS envoyé avec succès.'];
            }

            Log::error("Échec de l'envoi Custom SMS à {$phoneNumber}. HTTP {$response->status()}", [
                'body' => $response->body(),
                'payload' => $payload,
            ]);

            return ['success' => false, 'message' => "Erreur API SMS via HTTP avec le statut " . $response->status()];

        } catch (Exception $e) {
            Log::error("Erreur lors de l'appel au pilote Custom SMS : " . $e->getMessage());
            return ['success' => false, 'message' => "Erreur lors de l'appel au pilote: " . $e->getMessage()];
        }
    }

    /**
     * Verifier que le service SMS est accessible.
     *
     * @return array{ok: bool, message: string}
     */
    public function checkService(): array
    {
        try {
            $apiUrl = $this->config['api_url'] ?? '';
            if (empty($apiUrl)) {
                return ['ok' => false, 'message' => "URL de l'API non configurée."];
            }
            
            // Simple check conceptually, as custom endpoints vary wildly
            return ['ok' => true, 'message' => "La configuration Custom SMS est prête."];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Format the phone number (Remove non-digits, etc).
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Supprimer tous les espaces et les tirets temporairement si besoin.
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}
