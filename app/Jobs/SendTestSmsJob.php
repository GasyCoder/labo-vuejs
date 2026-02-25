<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTestSmsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $phone,
        public string $message
    ) {}

    /**
     * Execute the job.
     */
    public function handle(\App\Services\Sms\SmsManager $smsManager): void
    {
        $smsManager->driver()->sendSms($this->phone, $this->message);
    }
}
