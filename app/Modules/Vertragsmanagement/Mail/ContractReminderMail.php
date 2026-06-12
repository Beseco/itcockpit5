<?php

namespace App\Modules\Vertragsmanagement\Mail;

use App\Modules\Vertragsmanagement\Models\Vertrag;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Vertrag $vertrag,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "IT Cockpit · Vertragsmanagement: {$this->vertrag->name}"
        );
    }

    public function content(): Content
    {
        return new Content(view: 'vertragsmanagement::emails.reminder');
    }
}
