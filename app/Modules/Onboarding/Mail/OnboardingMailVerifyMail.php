<?php

namespace App\Modules\Onboarding\Mail;

use App\Modules\Onboarding\Models\OnboardingRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingMailVerifyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly OnboardingRecord $record,
        public readonly string           $verifyUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'IT-Cockpit: Bitte bestätigen Sie Ihre E-Mail-Adresse');
    }

    public function content(): Content
    {
        return new Content(view: 'onboarding::emails.mail_verify');
    }
}
