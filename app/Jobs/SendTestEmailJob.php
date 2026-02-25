<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTestEmailJob implements ShouldQueue
{
    use Queueable;

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
        \Illuminate\Support\Facades\Mail::raw($this->messageContent, function ($message) {
            $message->to($this->email)
                ->subject('Test de configuration E-mail - '.config('app.name'));
        });
    }
}
