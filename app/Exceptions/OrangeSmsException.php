<?php

namespace App\Exceptions;

use Exception;

class OrangeSmsException extends Exception
{
    public static function fromApiResponse(int $status, string $body): self
    {
        $data = json_decode($body, true);

        $policyMessage = $data['requestError']['policyException']['variables'][0] ?? '';
        $serviceMessage = $data['requestError']['serviceException']['variables'][0] ?? '';
        $errorText = $data['requestError']['policyException']['text'] ?? '';
        $messageId = $data['requestError']['policyException']['messageId']
            ?? $data['requestError']['serviceException']['messageId']
            ?? '';

        // Contrat expiré
        if (str_contains($policyMessage, 'Expired contract') || $messageId === 'POL0001') {
            return new self('Forfait SMS Orange expiré. Veuillez renouveler votre bundle sur developer.orange.com');
        }

        // Numéro invalide
        if ($messageId === 'SVC0004' || str_contains($serviceMessage, 'address')) {
            return new self('Numéro de téléphone invalide');
        }

        // Quota dépassé
        if ($messageId === 'POL0003' || str_contains($policyMessage, 'throttl')) {
            return new self('Quota SMS dépassé. Réessayez plus tard');
        }

        // Authentification échouée
        if ($status === 401) {
            return new self('Identifiants SMS Orange invalides. Vérifiez la configuration');
        }

        // Erreur serveur Orange
        if ($status >= 500) {
            return new self('Service SMS Orange temporairement indisponible. Réessayez plus tard');
        }

        return new self('Erreur lors de l\'envoi du SMS : '.($policyMessage ?: $serviceMessage ?: $body));
    }

    public static function authenticationFailed(int $status, string $body): self
    {
        if ($status === 401) {
            return new self('Identifiants SMS Orange invalides. Vérifiez CLIENT_ID et CLIENT_SECRET');
        }

        if ($status === 404) {
            return new self('URL d\'authentification SMS Orange introuvable. Vérifiez ORANGE_SMS_AUTH_URL');
        }

        return new self('Erreur de connexion au service SMS Orange');
    }

    public static function connectionFailed(\Exception $e): self
    {
        return new self('Erreur de connexion au service SMS. Vérifiez votre connexion internet');
    }
}
