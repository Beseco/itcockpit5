<?php

namespace App\Mail;

use App\Models\Abteilung;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class AbteilungNeueSoftwareMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $apps,
        public Abteilung $abteilung,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Software-Vorschlag] ' . $this->abteilung->anzeigename . ' – ' . $this->apps->count() . ' neue App(s)',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.abteilung_neue_software');
    }
}
