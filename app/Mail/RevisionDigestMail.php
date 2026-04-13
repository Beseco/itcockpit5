<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RevisionDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $admin,
        public Collection $apps,
    ) {}

    public function envelope(): Envelope
    {
        $count = $this->apps->count();
        return new Envelope(
            subject: "Offene Revisionen: {$count} Applikation" . ($count !== 1 ? 'en' : '') . ' warten auf Ihre Überprüfung',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.revision_digest',
        );
    }
}
