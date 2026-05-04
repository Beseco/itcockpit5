<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;

class UserOnboardingMail extends Mailable
{
    public function __construct(
        public readonly User $user,
        public readonly string $resetUrl,
    ) {}

    public function build(): static
    {
        return $this
            ->to($this->user->email)
            ->subject('Ihr Zugang zum IT-Cockpit – Erste Schritte')
            ->view('emails.user-onboarding');
    }
}
