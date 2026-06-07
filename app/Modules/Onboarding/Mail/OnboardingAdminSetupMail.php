<?php

namespace App\Modules\Onboarding\Mail;

use App\Modules\Onboarding\Models\OnboardingRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingAdminSetupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly OnboardingRecord $record,
        public readonly string           $tempPassword,
        public readonly string           $todoUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Neues Konto angelegt: {$this->record->vorname} {$this->record->nachname} – Todo-Liste ausstehend");
    }

    public function content(): Content
    {
        return new Content(view: 'onboarding::emails.admin_setup');
    }
}
