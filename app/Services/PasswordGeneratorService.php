<?php

namespace App\Services;

class PasswordGeneratorService
{
    private const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
    private const DIGITS    = '0123456789';
    private const LENGTH    = 8;

    /**
     * Generiert ein sicheres 8-Zeichen-Passwort.
     * Garantiert: mind. 1 Großbuchstabe, 1 Kleinbuchstabe, 1 Ziffer.
     * Restliche Zeichen werden zufällig aus dem Gesamtzeichensatz gewählt.
     * Am Ende wird das Array gemischt (shuffle).
     */
    public function generate(): string
    {
        $combined = self::UPPERCASE . self::LOWERCASE . self::DIGITS;

        $chars = [
            self::UPPERCASE[random_int(0, strlen(self::UPPERCASE) - 1)],
            self::LOWERCASE[random_int(0, strlen(self::LOWERCASE) - 1)],
            self::DIGITS[random_int(0, strlen(self::DIGITS) - 1)],
        ];

        for ($i = 3; $i < self::LENGTH; $i++) {
            $chars[] = $combined[random_int(0, strlen($combined) - 1)];
        }

        shuffle($chars);

        return implode('', $chars);
    }
}
