<?php

namespace App\Mail;

use App\Models\Applikation;
use App\Modules\AdUsers\Models\AdUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerantwortlicherAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Applikation $app,
        public AdUser      $adUser
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sie wurden als Verfahrensverantwortliche/r eingetragen: ' . $this->app->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verantwortlicher_assigned',
        );
    }
}
