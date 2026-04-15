<?php

namespace App\Mail;

use App\Models\User;
use App\Modules\Tickets\Models\TicketsSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TicketScoreMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User            $user,
        public float           $score,
        public Collection      $yellowTickets,
        public Collection      $redTickets,
        public TicketsSettings $settings,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ihr Ticket-Score: ' . number_format($this->score, 1) . ' Punkte',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket_score',
        );
    }
}
