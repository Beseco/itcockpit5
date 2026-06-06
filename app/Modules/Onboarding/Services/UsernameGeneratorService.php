<?php

namespace App\Modules\Onboarding\Services;

use App\Modules\AdUsers\Services\LdapConnectionService;

class UsernameGeneratorService
{
    /** Übersetzungstabelle für deutsche Sonderzeichen */
    private const UMLAUT_MAP = [
        'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue',
        'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue',
        'ß' => 'ss',
    ];

    /**
     * Löst ein Benutzername-Muster auf.
     *
     * Unterstützte Variablen:
     *  %vorname%    → vollständiger Vorname (lowercase, Umlaute ersetzt)
     *  %nachname%   → vollständiger Nachname (lowercase, Umlaute ersetzt)
     *  %F%          → erster Buchstabe Vorname (uppercase)
     *  %N%          → erster Buchstabe Nachname (uppercase)
     *  %f%          → erster Buchstabe Vorname (lowercase)
     *  %n%          → erster Buchstabe Nachname (lowercase)
     */
    public function resolvePattern(string $pattern, string $vorname, string $nachname): string
    {
        $vornameClean   = $this->clean($vorname);
        $nachnameClean  = $this->clean($nachname);

        return str_replace(
            ['%vorname%', '%nachname%', '%F%', '%N%', '%f%', '%n%'],
            [
                $vornameClean,
                $nachnameClean,
                strtoupper(substr($vornameClean, 0, 1)),
                strtoupper(substr($nachnameClean, 0, 1)),
                strtolower(substr($vornameClean, 0, 1)),
                strtolower(substr($nachnameClean, 0, 1)),
            ],
            $pattern
        );
    }

    /**
     * Gibt einen eindeutigen sAMAccountName zurück.
     * Wenn der Wunschname vergeben ist, werden Alternativen vorgeschlagen.
     *
     * @return array{samaccountname: string, alternatives: string[]}
     */
    public function findAvailable(string $wunsch, string $vorname, string $nachname, LdapConnectionService $ldap): array
    {
        $existing = $this->getExistingNames($ldap);

        if (!in_array(strtolower($wunsch), $existing, true)) {
            return ['samaccountname' => $wunsch, 'alternatives' => []];
        }

        $alternatives = $this->buildAlternatives($wunsch, $vorname, $nachname, $existing);
        $first        = $alternatives[0] ?? ($wunsch . '2');

        return ['samaccountname' => $first, 'alternatives' => $alternatives];
    }

    /** Prüft ob ein gegebener sAMAccountName im AD bereits vorhanden ist. */
    public function isAvailable(string $name, LdapConnectionService $ldap): bool
    {
        $existing = $this->getExistingNames($ldap);
        return !in_array(strtolower($name), $existing, true);
    }

    // ─── private ─────────────────────────────────────────────────────────────

    private function getExistingNames(LdapConnectionService $ldap): array
    {
        try {
            return $ldap->getAllUsers()
                ->map(fn($u) => strtolower($u['samaccountname'][0] ?? ''))
                ->filter()
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function buildAlternatives(string $base, string $vorname, string $nachname, array $existing): array
    {
        $v = $this->clean($vorname);
        $n = $this->clean($nachname);
        $candidates = [];

        // Variante: nachnameVorname (komplett)
        $candidates[] = $n . $v;
        // Variante: v.nachname
        $candidates[] = substr($v, 0, 1) . '.' . $n;
        // Variante: vorname.n
        $candidates[] = $v . '.' . substr($n, 0, 1);
        // Nummerierung
        for ($i = 2; $i <= 9; $i++) {
            $candidates[] = $base . $i;
        }

        return array_values(array_filter(
            $candidates,
            fn($c) => !in_array(strtolower($c), $existing, true)
        ));
    }

    private function clean(string $input): string
    {
        $result = strtr($input, self::UMLAUT_MAP);
        $result = preg_replace('/[^a-zA-Z0-9\.\-]/', '', $result);
        return strtolower($result);
    }
}
