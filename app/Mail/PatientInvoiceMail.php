<?php

namespace App\Mail;

use App\Models\Prescription;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PatientInvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Prescription $prescription
    ) {}

    public function envelope(): Envelope
    {
        $nomEntreprise = Setting::getNomEntreprise();

        return new Envelope(
            subject: "Facture pour votre prescription {$this->prescription->reference} - {$nomEntreprise}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.patient-invoice',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => \Barryvdh\DomPDF\Facade\Pdf::loadView('factures.pdf-template', ['prescription' => $this->prescription])
                    ->setPaper('a4', 'portrait')
                    ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])
                    ->output(),
                "facture-{$this->prescription->reference}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
