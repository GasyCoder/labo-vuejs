<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

class SendTestEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * Le nombre de fois que la tâche peut être tentée.
     */
    public $tries = 10;

    /**
     * Le nombre de secondes à attendre avant de retenter la tâche (Backoff exponentiel).
     */
    public $backoff = [1, 2, 4, 8, 16, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $email,
        public string $messageContent
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Throttling : Respecter la limite de Resend (2 requêtes / sec)
        $executed = RateLimiter::attempt(
            'resend:send',
            2,
            function () {
                try {
                    Mail::mailer('resend')->raw($this->messageContent, function ($message) {
                        $message->to($this->email)
                            ->subject('Test Resend (APP) - '.config('app.name'));
                    });
                } catch (\Exception $e) {
                    // Si c'est une erreur de type "Too Many Requests" (429)
                    if (str_contains(strtolower($e->getMessage()), 'too many requests') || $e->getCode() === 429) {
                        Log::warning("Resend Rate Limit atteint pour {$this->email}. Libération du job pour une nouvelle tentative.");
                        $this->release(2); // Relâcher avec un délai de 2 secondes
                        return;
                    }

                    // Relancer l'exception pour les autres erreurs (visibles dans failed_jobs)
                    throw $e;
                }
            },
            1 // Fenêtre de 1 seconde
        );

        if (! $executed) {
            // Le RateLimiter refuse l'exécution immédiate
            $this->release(1);
        }
    }
}
