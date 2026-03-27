<?php

namespace App\Mail;

use App\Modules\AdUsers\Models\OffboardingRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OffboardingConfirmedAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OffboardingRecord $record) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Offboarding bestätigt] ' . $this->record->voller_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.offboarding_confirmed_admin',
        );
    }
}
