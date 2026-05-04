<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;

class UserMissingMail extends Mailable
{
    public function __construct(
        public readonly User $user,
        public readonly int $inactiveDays,
    ) {}

    public function build(): static
    {
        return $this
            ->to($this->user->email)
            ->subject('Wir vermissen Sie im IT-Cockpit')
            ->view('emails.user-missing');
    }
}
