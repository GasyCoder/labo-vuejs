<?php

namespace App\Mail;

use App\Models\Prescription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResultatDisponible extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Prescription $prescription,
        public string $customMessage,
        public string $lienPdf = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vos résultats d\'analyses sont disponibles - Laboratoire Lareference',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.resultat-disponible',
        );
    }
}
