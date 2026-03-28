<?php

namespace App\Mail;

use App\Modules\AdUsers\Models\OffboardingRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OffboardingDeaktivierungMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $confirmUrl;
    public bool   $ldapBestaetigt;

    public function __construct(public OffboardingRecord $record, bool $ldapBestaetigt = false)
    {
        $this->ldapBestaetigt = $ldapBestaetigt;
        $this->confirmUrl     = route('offboarding.admin.deaktivierung', $record->deaktivierung_token);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Offboarding] Deaktivierung: ' . $this->record->voller_name,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.offboarding_deaktivierung');
    }
}
