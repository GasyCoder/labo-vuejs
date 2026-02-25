<?php

namespace App\Contracts;

interface SmsDriverInterface
{
    /**
     * Envoyer un SMS.
     *
     * @return array<string, mixed>
     */
    public function sendSms(string $phoneNumber, string $message): array;

    /**
     * Verifier que le service SMS est accessible.
     *
     * @return array{ok: bool, message: string}
     */
    public function checkService(): array;
}
