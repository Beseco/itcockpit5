<?php

namespace App\Services;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class UserMailService
{
    /**
     * Versendet die Willkommens-E-Mail mit Zugangsdaten.
     *
     * @param User   $user              Der neu angelegte Benutzer
     * @param string $plaintextPassword Das Klartext-Passwort (nur im Erstellungsmoment verfügbar)
     */
    public function sendWelcomeMail(User $user, string $plaintextPassword): void
    {
        Mail::to($user->email)->send(new WelcomeMail($user, $plaintextPassword));
    }
}
