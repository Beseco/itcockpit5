<?php

namespace App\Modules\Onboarding\Mail;

use App\Modules\Onboarding\Models\OnboardingRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingSupervisorMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string           $mailSubject,
        public readonly string           $mailBody,
        public readonly OnboardingRecord $record,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(view: 'onboarding::emails.supervisor');
    }
}
