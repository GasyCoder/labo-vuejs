<?php

namespace App\Jobs;

use App\Mail\ResultatDisponible;
use App\Models\NotificationLog;
use App\Models\Prescription;
use App\Services\MapiSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public int $prescriptionId,
        public string $type,
        public string $message,
        public string $lienPdf,
        public ?int $sentBy = null,
    ) {}

    public function handle(MapiSmsService $mapiSms): void
    {
        $prescription = Prescription::with('patient')->findOrFail($this->prescriptionId);
        $patient = $prescription->patient;

        try {
            if ($this->type === 'sms') {
                $phone = $patient->telephone;
                if (! $phone) {
                    throw new \Exception('Aucun numéro de téléphone pour le patient.');
                }

                $mapiSms->sendSms($phone, $this->message);

                $this->logNotification($prescription, 'sms', $phone, 'envoye');
                Log::info("SMS MAPI envoyé à {$phone} pour prescription {$prescription->reference}");

            } elseif ($this->type === 'email') {
                $email = $patient->email;
                if (! $email) {
                    throw new \Exception('Aucune adresse email pour le patient.');
                }

                Mail::to($email)->send(new ResultatDisponible($prescription, $this->message, $this->lienPdf));

                $this->logNotification($prescription, 'email', $email, 'envoye');
                Log::info("Email envoyé à {$email} pour prescription {$prescription->reference}");
            }

            $prescription->update(['notified_at' => now()]);

        } catch (\Exception $e) {
            Log::error("Erreur envoi notification ({$this->type}): ".$e->getMessage());

            $destinataire = $this->type === 'sms' ? ($patient->telephone ?? '') : ($patient->email ?? '');
            $this->logNotification($prescription, $this->type, $destinataire, 'echec', $e->getMessage());

            throw $e;
        }
    }

    protected function logNotification(
        Prescription $prescription,
        string $type,
        string $destinataire,
        string $statut,
        ?string $errorMessage = null,
    ): void {
        NotificationLog::create([
            'prescription_id' => $prescription->id,
            'type' => $type,
            'destinataire' => $destinataire,
            'message' => $this->message,
            'statut' => $statut,
            'error_message' => $errorMessage,
            'sent_by' => $this->sentBy,
        ]);
    }
}
