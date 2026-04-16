<?php

namespace App\Mail;

use App\Modules\SslCerts\Models\SslCertificate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SslCertExpiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SslCertificate $cert,
        public readonly int $daysRemaining,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match(true) {
            $this->daysRemaining <= 1  => "⚠️ SSL-Zertifikat läuft morgen ab: {$this->cert->name}",
            $this->daysRemaining <= 7  => "⚠️ SSL-Zertifikat läuft in {$this->daysRemaining} Tagen ab: {$this->cert->name}",
            $this->daysRemaining <= 14 => "SSL-Zertifikat läuft in {$this->daysRemaining} Tagen ab: {$this->cert->name}",
            default                    => "SSL-Zertifikat läuft in {$this->daysRemaining} Tagen ab: {$this->cert->name}",
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.ssl_cert_expiry');
    }
}
