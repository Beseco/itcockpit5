<?php

namespace App\Mail;

use App\Models\Applikation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RevisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $revisionUrl;

    public function __construct(public Applikation $app)
    {
        $this->revisionUrl = route('revision.show', $app->revision_token);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Revisionsaufforderung: ' . $this->app->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.revision',
        );
    }
}
