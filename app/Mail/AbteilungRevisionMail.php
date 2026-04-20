<?php

namespace App\Mail;

use App\Models\Abteilung;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbteilungRevisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $revisionUrl;

    public function __construct(
        public Abteilung $abteilung,
        public string $empfaengerName = '',
    ) {
        $this->revisionUrl = route('abteilung-revision.show', $abteilung->revision_token);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Softwareliste zur Prüfung: ' . $this->abteilung->anzeigename,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.abteilung_revision',
        );
    }
}
