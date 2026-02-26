<?php

namespace App\Jobs;

use App\Models\Prescription;
use App\Services\FeatureService;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyPatientOfValidatedResults implements ShouldQueue
{
    use Queueable;

    public $prescription;

    /**
     * Create a new job instance.
     */
    public function __construct(Prescription $prescription)
    {
        $this->prescription = $prescription;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        $this->prescription->loadMissing(['patient', 'secretaire']);
        $patient = $this->prescription->patient;
        $secretaire = $this->prescription->secretaire;

        $clientId = $secretaire?->client_id;

        // Check if the premium feature is enabled for the client
        if (! app(FeatureService::class)->isEnabled($clientId, 'notifications_sms_email_validated')) {
            Log::info('NotifyPatientOfValidatedResults: Premium feature "notifications_sms_email_validated" is disabled for this client.', [
                'prescription_id' => $this->prescription->id,
                'client_id' => $clientId,
            ]);

            return;
        }

        if (! $patient) {
            Log::warning('NotifyPatientOfValidatedResults: Prescription sans patient.', ['prescription_id' => $this->prescription->id]);

            return;
        }

        $message = "Bonjour {$patient->nom}, vos résultats d'analyses ({$this->prescription->reference}) sont disponibles au laboratoire. Merci de passer les récupérer. - Lareference";

        try {
            // Tenter SMS en premier si n° de téléphone existe
            if (! empty($patient->telephone)) {
                Log::info('Envoi SMS pour résultats validés', ['prescription_id' => $this->prescription->id, 'telephone' => $patient->telephone]);
                $notificationService->sendSms($this->prescription, $message);
            }
            // Sinon tenter Email si e-mail existe
            elseif (! empty($patient->email)) {
                Log::info('Envoi Email pour résultats validés', ['prescription_id' => $this->prescription->id, 'email' => $patient->email]);
                $pdfLink = route('laboratoire.prescription.pdf', $this->prescription->id);
                $notificationService->sendEmail($this->prescription, $message, $pdfLink);
            } else {
                Log::info('NotifyPatientOfValidatedResults: Patient sans contact (ni tel, ni email).', ['patient_id' => $patient->id]);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de la notification automatique de résultats', [
                'prescription_id' => $this->prescription->id,
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
