<?php

namespace App\Services;

use App\Models\Dienstleister;
use App\Modules\AdUsers\Models\AdUser;
use App\Support\PhoneNumberNormalizer;

/**
 * Ermittelt zu einer eingehenden Telefonnummer (E.164) den/die passenden
 * Dienstleister, Ansprechpartner oder internen AD-Benutzer.
 *
 * Die gespeicherten Nummern bleiben unverändert (frei formatiert); der Abgleich
 * erfolgt zur Laufzeit über die Normalisierung in PhoneNumberNormalizer.
 *
 * Matching:
 *  - exact: normalisierte Nummern sind identisch
 *  - range: die gespeicherte (kürzere) Stammnummer ist Präfix der eingehenden
 *           Durchwahl (>= 6 Stellen) – nur als Fallback, wenn kein Exakttreffer
 */
class CallerLookupService
{
    private const MIN_RANGE_DIGITS = 6;

    /**
     * @return array{input:string, e164:?string, key:?string, matches:array<int,array>}
     */
    public function lookup(string $phone): array
    {
        $e164 = PhoneNumberNormalizer::toE164($phone);
        $key  = $e164 !== null ? substr($e164, 1) : null;

        $matches = $key !== null ? $this->collectMatches($key) : [];

        return [
            'input'   => $phone,
            'e164'    => $e164,
            'key'     => $key,
            'matches' => $matches,
        ];
    }

    /**
     * Klassifiziert eine gespeicherte Nummer gegen den gesuchten Schlüssel.
     * Gibt 'exact', 'range' oder null zurück.
     */
    private function classify(?string $stored, string $key): ?string
    {
        $candidate = PhoneNumberNormalizer::digitsKey($stored);
        if ($candidate === null) {
            return null;
        }
        if ($candidate === $key) {
            return 'exact';
        }
        if (strlen($candidate) >= self::MIN_RANGE_DIGITS && str_starts_with($key, $candidate)) {
            return 'range';
        }
        return null;
    }

    /** Rang eines Match-Typs (höher = besser). */
    private function rank(?string $type): int
    {
        return ['exact' => 2, 'range' => 1][$type] ?? 0;
    }

    private function collectMatches(string $key): array
    {
        $results = [];

        // ── Dienstleister (Firmennummer) + Ansprechpartner ──────────────────
        foreach (Dienstleister::with('kontakte')->get() as $d) {
            $companyType = $this->classify($d->telefon, $key);

            $contactHit = null;       // ['contact'=>..., 'type'=>...]
            foreach ($d->kontakte as $kontakt) {
                $telType  = $this->classify($kontakt->telefon, $key);
                $handyType = $this->classify($kontakt->handy, $key);
                $type = $this->rank($telType) >= $this->rank($handyType) ? $telType : $handyType;

                if ($type !== null && ($contactHit === null || $this->rank($type) > $this->rank($contactHit['type']))) {
                    $contactHit = ['contact' => $kontakt, 'type' => $type];
                }
            }

            // Bestes Ergebnis für diesen Dienstleister bestimmen.
            $best = null;
            if ($companyType !== null) {
                $best = ['via' => 'firmennummer', 'type' => $companyType, 'contact' => null];
            }
            if ($contactHit !== null && ($best === null || $this->rank($contactHit['type']) > $this->rank($best['type']))) {
                $best = ['via' => 'ansprechpartner', 'type' => $contactHit['type'], 'contact' => $contactHit['contact']];
            }

            if ($best !== null) {
                $results[] = [
                    'kind'          => 'dienstleister',
                    'dienstleister' => $d,
                    'contact'       => $best['contact'],
                    'via'           => $best['via'],
                    'match_type'    => $best['type'],
                ];
            }
        }

        // ── Interne AD-Benutzer ─────────────────────────────────────────────
        $adUsers = AdUser::where('ad_vorhanden', true)
            ->whereNotNull('telefon')
            ->where('telefon', '!=', '')
            ->get();

        foreach ($adUsers as $u) {
            $type = $this->classify($u->telefon, $key);
            if ($type !== null) {
                $results[] = [
                    'kind'       => 'aduser',
                    'aduser'     => $u,
                    'via'        => 'aduser',
                    'match_type' => $type,
                ];
            }
        }

        // Exakte Treffer vor Bereich-Treffern.
        usort($results, fn ($a, $b) =>
            ($a['match_type'] === 'exact' ? 0 : 1) <=> ($b['match_type'] === 'exact' ? 0 : 1)
        );

        return $results;
    }
}
