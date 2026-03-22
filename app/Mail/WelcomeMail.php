<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;

class WelcomeMail extends Mailable
{
    public function __construct(
        public readonly User $user,
        public readonly string $plaintextPassword,
    ) {}

    public function build(): static
    {
        return $this
            ->to($this->user->email)
            ->subject('Zugangsdaten für IT-Cockpit')
            ->view('emails.welcome');
    }
}
