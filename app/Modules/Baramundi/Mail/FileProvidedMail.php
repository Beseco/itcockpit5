<?php

namespace App\Modules\Baramundi\Mail;

use App\Modules\Baramundi\Models\WatchedPackage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FileProvidedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly WatchedPackage $package,
        public readonly string $version,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Installationsdatei bereitgestellt: {$this->package->name} {$this->version}"
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.bara_file_provided');
    }
}
