<?php

namespace App\Services;

class PasswordGeneratorService
{
    private const UPPERCASE = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    private const LOWERCASE = 'abcdefghjkmnpqrstuvwxyz';
    private const DIGITS    = '23456789';
    private const SPECIAL   = '!@#$%&*-+=?';
    private const LENGTH    = 12;

    /**
     * Generiert ein 12-Zeichen-Passwort das alle vier AD-Komplexitätskategorien erfüllt
     * (Großbuchstabe, Kleinbuchstabe, Ziffer, Sonderzeichen).
     * Ambige Zeichen (0, O, 1, I, l) sind ausgeschlossen.
     */
    public function generate(): string
    {
        $combined = self::UPPERCASE . self::LOWERCASE . self::DIGITS . self::SPECIAL;

        $chars = [
            self::UPPERCASE[random_int(0, strlen(self::UPPERCASE) - 1)],
            self::LOWERCASE[random_int(0, strlen(self::LOWERCASE) - 1)],
            self::DIGITS[random_int(0, strlen(self::DIGITS) - 1)],
            self::SPECIAL[random_int(0, strlen(self::SPECIAL) - 1)],
        ];

        for ($i = 4; $i < self::LENGTH; $i++) {
            $chars[] = $combined[random_int(0, strlen($combined) - 1)];
        }

        shuffle($chars);

        return implode('', $chars);
    }
}
