<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServerAdminMissingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $servers,
    ) {}

    public function envelope(): Envelope
    {
        $count = $this->servers->count();
        return new Envelope(
            subject: "Server ohne Administrator: {$count} Server " . ($count === 1 ? 'benötigt' : 'benötigen') . ' einen Verantwortlichen',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.server_admin_missing',
        );
    }
}
