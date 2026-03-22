<?php

namespace App\Mail;

use App\Models\ReminderMail;
use Illuminate\Mail\Mailable;

class ReminderMailable extends Mailable
{
    public function __construct(public readonly ReminderMail $reminder) {}

    public function build(): static
    {
        return $this
            ->to($this->reminder->mailto)
            ->subject($this->reminder->titel)
            ->view('emails.reminder');
    }
}
