<?php

namespace App\Mail;

use App\Models\PdfJob;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PdfReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly PdfJob $pdfJob)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your PDF is ready to download',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pdf-ready',
            with: [
                'pdfJob' => $this->pdfJob,
            ],
        );
    }
}
