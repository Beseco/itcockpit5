<?php

namespace App\Mail;

use App\Modules\AdUsers\Models\OffboardingRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OffboardingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $confirmUrl;

    public function __construct(public OffboardingRecord $record)
    {
        $this->confirmUrl = route('offboarding.confirm', $record->bestaetigungstoken);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bitte bestätigen: Ausscheiden aus dem Dienstverhältnis',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.offboarding_confirmation',
        );
    }
}
