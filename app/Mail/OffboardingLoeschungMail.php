<?php

namespace App\Mail;

use App\Modules\AdUsers\Models\OffboardingRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OffboardingLoeschungMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $confirmUrl;
    public bool   $ldapBestaetigt;

    public function __construct(public OffboardingRecord $record, bool $ldapBestaetigt = false)
    {
        $this->ldapBestaetigt = $ldapBestaetigt;
        $this->confirmUrl     = route('offboarding.admin.loeschung', $record->loeschung_token);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Offboarding] Löschung: ' . $this->record->voller_name,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.offboarding_loeschung');
    }
}
