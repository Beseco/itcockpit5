<?php

namespace App\Mail;

use App\Models\Applikation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RevisionCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Applikation $app,
        public array       $answers,
        public array       $changes
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Revision abgeschlossen] ' . $this->app->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.revision_completed',
        );
    }
}
