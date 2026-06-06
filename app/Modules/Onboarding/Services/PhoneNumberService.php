<?php

namespace App\Modules\Onboarding\Services;

use App\Modules\AdUsers\Services\LdapConnectionService;

class PhoneNumberService
{
    /**
     * Ermittelt die nächste freie Rufnummer für ein gegebenes Präfix.
     *
     * Das Präfix endet auf "XX" als Platzhalter für zwei freie Stellen.
     * Beispiel: "+498161600314XX" → findet die kleinste zweistellige Endung 01–99
     * die noch nicht vergeben ist.
     *
     * Gibt null zurück wenn kein Präfix konfiguriert oder keine freie Nummer vorhanden.
     */
    public function findNextFree(string $praefix, LdapConnectionService $ldap): ?string
    {
        $basePreaefix = rtrim($praefix, 'Xx');
        if (empty($basePreaefix)) return null;

        $usedSuffixes = $this->getUsedSuffixes($basePreaefix, $ldap);

        for ($i = 1; $i <= 99; $i++) {
            $suffix = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            if (!in_array($suffix, $usedSuffixes, true)) {
                return $basePreaefix . $suffix;
            }
        }

        return null;
    }

    // ─── private ─────────────────────────────────────────────────────────────

    private function getUsedSuffixes(string $basePrefix, LdapConnectionService $ldap): array
    {
        try {
            $users = $ldap->getAllUsers();
        } catch (\Throwable) {
            return [];
        }

        $prefixLen = strlen($basePrefix);
        $suffixes  = [];

        foreach ($users as $user) {
            $tel = $user['telephonenumber'][0] ?? null;
            if (!$tel) continue;

            // Leerzeichen und Trennzeichen normalisieren
            $telNorm = preg_replace('/[\s\-]/', '', $tel);
            $prefNorm = preg_replace('/[\s\-]/', '', $basePrefix);

            if (str_starts_with($telNorm, $prefNorm)) {
                $suffix = substr($telNorm, strlen($prefNorm));
                if (preg_match('/^\d{1,2}$/', $suffix)) {
                    $suffixes[] = str_pad($suffix, 2, '0', STR_PAD_LEFT);
                }
            }
        }

        return $suffixes;
    }
}
