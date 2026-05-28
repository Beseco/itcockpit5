<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeedbackRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $recipientName,
        public readonly string $feedbackUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ihre Meinung ist gefragt – IT-Support Bewertung',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.feedback_request',
        );
    }
}
