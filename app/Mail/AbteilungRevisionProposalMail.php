<?php

namespace App\Mail;

use App\Models\Abteilung;
use App\Models\AbteilungRevisionProposal;
use App\Models\Applikation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbteilungRevisionProposalMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $approveUrl;

    public function __construct(
        public AbteilungRevisionProposal $proposal,
        public Applikation $app,
        public Abteilung $abteilung,
    ) {
        $this->approveUrl = route('abteilung-revision.approve', $proposal->approval_token);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Revisionsvorschlag] ' . $this->app->name . ' – Änderungen zur Prüfung',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.abteilung_revision_proposal');
    }
}
