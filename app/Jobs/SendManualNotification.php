<?php

namespace App\Jobs;

use App\Models\Prescription;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendManualNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Prescription $prescription,
        public string $channel,
        public string $message,
        public ?string $pdfLink = null
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        try {
            if ($this->channel === 'sms') {
                $notificationService->sendSms($this->prescription, $this->message);
            } elseif ($this->channel === 'email') {
                $notificationService->sendEmail($this->prescription, $this->message, $this->pdfLink ?? '');
            } else {
                Log::warning("SendManualNotification: Canal inconnu '{$this->channel}' pour la prescription {$this->prescription->id}");
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de la notification manuelle', [
                'prescription_id' => $this->prescription->id,
                'channel' => $this->channel,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
