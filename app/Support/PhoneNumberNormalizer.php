<?php

namespace App\Support;

/**
 * Normalisiert Telefonnummern in beliebigem Format auf E.164 (z.B. +498161536789).
 *
 * Wird für das CTI-Screen-Pop genutzt: die eingehende Teams-Nummer und die in der
 * Datenbank gespeicherten (frei formatierten) Nummern werden zur Laufzeit über
 * dieselbe Normalisierung verglichen – ohne die Bestandsdaten zu verändern.
 */
class PhoneNumberNormalizer
{
    /** Standard-Ländervorwahl (ohne +), Deutschland. */
    public const DEFAULT_CC = '49';

    /**
     * Wandelt eine beliebig formatierte Nummer in E.164 um (z.B. "+498161536789").
     *
     * Akzeptiert u.a.: "+49 8161 536-789", "0049 8161/536789", "08161 536789",
     * "(08161) 53 67 89". Buchstaben/sonstige Zeichen werden verworfen.
     *
     * @return string|null  E.164 (mit führendem +) oder null, wenn keine verwertbare Nummer.
     */
    public static function toE164(?string $raw, string $defaultCc = self::DEFAULT_CC): ?string
    {
        if ($raw === null) {
            return null;
        }

        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // Deutsche Schreibweise mit Trunk-Null in Klammern entfernen,
        // z.B. "+49 (0)8161 …" → "+49 8161 …".
        $raw = preg_replace('/\(0\)/', '', $raw);

        // Internationales Präfix merken: führendes "+" oder "00".
        $hasPlus = str_starts_with($raw, '+');

        // Nur Ziffern behalten.
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === '' || $digits === null) {
            return null;
        }

        if ($hasPlus) {
            // Bereits internationale Notation.
            $national = $digits;
        } elseif (str_starts_with($digits, '00')) {
            // 00<CC>... → internationale Notation
            $national = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            // Nationale Notation mit führender 0 → Default-CC voranstellen.
            $national = $defaultCc . ltrim(substr($digits, 1), '0');
        } else {
            // Keine erkennbare Vorwahl-Notation → Default-CC annehmen.
            $national = $defaultCc . $digits;
        }

        $national = ltrim($national, '0');
        if ($national === '') {
            return null;
        }

        // Mindestlänge: Ländervorwahl + sinnvolle Teilnehmernummer.
        if (strlen($national) < 7) {
            return null;
        }

        return '+' . $national;
    }

    /**
     * Vergleichs-Schlüssel: nur Ziffern der E.164-Form (ohne "+").
     * Für Gleichheits- und Präfix-Vergleiche.
     */
    public static function digitsKey(?string $raw, string $defaultCc = self::DEFAULT_CC): ?string
    {
        $e164 = self::toE164($raw, $defaultCc);
        return $e164 === null ? null : substr($e164, 1);
    }
}
