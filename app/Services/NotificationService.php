<?php

namespace App\Services;

use App\Mail\ResultatDisponible;
use App\Models\NotificationLog;
use App\Models\Prescription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    protected OrangeSmsService $orangeSms;

    public function __construct(OrangeSmsService $orangeSms)
    {
        $this->orangeSms = $orangeSms;
    }

    /**
     * Envoyer une notification (E-mail ou SMS)
     */
    public function send(Prescription $prescription, string $type, string $message, string $lienPdf = ''): bool
    {
        if ($type === 'email') {
            return $this->sendEmail($prescription, $message, $lienPdf);
        }

        if ($type === 'sms') {
            return $this->sendSms($prescription, $message);
        }

        return false;
    }

    /**
     * Envoi de SMS via Orange API
     */
    public function sendSms(Prescription $prescription, string $message): bool
    {
        $phone = $prescription->patient->telephone;

        if (! $phone) {
            Log::warning("Échec envoi SMS : Aucun numéro pour la prescription {$prescription->reference}");

            return false;
        }

        try {
            $this->orangeSms->sendSms($phone, $message);

            $this->logNotification($prescription, 'sms', $phone, $message, 'envoye');
            $prescription->update(['notified_at' => now()]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur envoi SMS Orange : '.$e->getMessage());
            $this->logNotification($prescription, 'sms', $phone, $message, 'echec', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envoi d'E-mail via Laravel Mail
     */
    public function sendEmail(Prescription $prescription, string $message, string $lienPdf = ''): bool
    {
        $email = $prescription->patient->email;

        if (! $email) {
            Log::warning("Échec envoi Email : Aucune adresse pour la prescription {$prescription->reference}");

            return false;
        }

        try {
            Mail::to($email)->send(new ResultatDisponible($prescription, $message, $lienPdf));

            Log::info("Email envoyé à {$email} pour la prescription {$prescription->reference}");
            $this->logNotification($prescription, 'email', $email, $message, 'envoye');
            $prescription->update(['notified_at' => now()]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur envoi Email : '.$e->getMessage());
            $this->logNotification($prescription, 'email', $email, $message, 'echec', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Enregistrer un log de notification
     */
    protected function logNotification(
        Prescription $prescription,
        string $type,
        string $destinataire,
        string $message,
        string $statut,
        ?string $errorMessage = null,
    ): void {
        NotificationLog::create([
            'prescription_id' => $prescription->id,
            'type' => $type,
            'destinataire' => $destinataire,
            'message' => $message,
            'statut' => $statut,
            'error_message' => $errorMessage,
            'sent_by' => Auth::id(),
        ]);
    }
}
