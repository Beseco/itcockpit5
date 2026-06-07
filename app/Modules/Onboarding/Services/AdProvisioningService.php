<?php

namespace App\Modules\Onboarding\Services;

use App\Modules\AdUsers\Models\AdUserSettings;
use App\Modules\Onboarding\Models\OnboardingSettings;
use App\Modules\Onboarding\Models\OnboardingVorlage;

class AdProvisioningService
{
    /** Erstellt einen AD-Benutzer basierend auf einer Vorlage und Personendaten. */
    public function createUser(OnboardingVorlage $vorlage, array $data): array
    {
        if (!extension_loaded('ldap')) {
            throw new \RuntimeException('PHP LDAP-Extension ist nicht aktiviert.');
        }

        $adSettings = AdUserSettings::getSingleton();
        $obSettings = OnboardingSettings::getSingleton();

        $conn = $this->connect($adSettings);
        $this->bind($conn, $adSettings, $obSettings);

        $samaccountname = $data['samaccountname'];
        $upn            = $data['upn'];
        $password       = $data['password'];
        $vorname        = $data['vorname'];
        $nachname       = $data['nachname'];
        $rufnummer      = $data['rufnummer'] ?? null;

        $cn     = trim("{$vorname} {$nachname}");
        $ouDn   = $vorlage->abteilung?->ad_path ?? $adSettings->base_dn;
        $userDn = $this->buildUniqueDn($conn, $cn, $ouDn, $samaccountname);

        // Schritt 1: Konto mit absoluten Mindest-Attributen anlegen.
        // Nur objectClass + cn + sAMAccountName – alles andere per ldap_modify im Folgeschritt,
        // da AD sonst bei UPN-Suffix, mail oder anderen Attributen eine Constraint Violation wirft.
        $createAttrs = [
            'objectClass'    => ['top', 'person', 'organizationalPerson', 'user'],
            'cn'             => $cn,
            'sAMAccountName' => $samaccountname,
        ];

        if (!@ldap_add($conn, $userDn, $createAttrs)) {
            $err      = ldap_error($conn);
            $diagMsg  = '';
            @ldap_get_option($conn, LDAP_OPT_DIAGNOSTIC_MESSAGE, $diagMsg);
            ldap_unbind($conn);
            $detail = $diagMsg ? " – Details: {$diagMsg}" : '';
            throw new \RuntimeException("Benutzer konnte nicht angelegt werden: {$err}{$detail}");
        }

        // Schritt 2: Passwort setzen (erfordert LDAPS / Port 636).
        // Fehler sind nicht fatal – Konto bleibt dann deaktiviert, Admin muss Passwort manuell setzen.
        $passwordWarning = null;
        try {
            $this->setPassword($conn, $userDn, $password);
            // Schritt 3: Konto aktivieren – temporäres Passwort, kein Zwang zur Änderung (Phase 1)
            $this->enableAccount($conn, $userDn, forceChange: false);
        } catch (\RuntimeException $e) {
            $passwordWarning = $e->getMessage();
            \Illuminate\Support\Facades\Log::warning("Onboarding: Passwort konnte nicht gesetzt werden für {$userDn}: {$passwordWarning}");
        }

        // Schritt 4: Alle weiteren Attribute per ldap_modify setzen
        $extraAttrs = array_filter([
            'givenName'                => $vorname,
            'sn'                       => $nachname,
            'displayName'              => $cn,
            'userPrincipalName'        => $upn,
            'mail'                     => $upn,
            'telephoneNumber'               => $rufnummer,
            'mobile'                        => $data['mobile'] ?? null,
            'facsimileTelephoneNumber'      => $data['fax'] ?? null,
            'physicalDeliveryOfficeName'    => $data['buero'] ?? null,
            'streetAddress'            => $vorlage->strasse,
            'postalCode'               => $vorlage->plz,
            'l'                        => $vorlage->ort,
            'profilePath'              => $data['profilpfad'] ?? null,
            'homeDirectory'            => $data['heimatverzeichnis'] ?? null,
            'homeDrive'                => $data['heimatverzeichnis_laufwerk'] ?? null,
            'scriptPath'               => $vorlage->anmeldeskript,
            'department'               => $vorlage->abteilung_ad,
            'description'              => $vorlage->ad_beschreibung,
            'company'                  => $vorlage->firma,
            'manager'                  => ($data['vorgesetzter_dn'] ?? null) ?: null,
        ], fn($v) => $v !== null && $v !== '');

        if (!empty($extraAttrs)) {
            if (!@ldap_modify($conn, $userDn, $extraAttrs)) {
                $err     = ldap_error($conn);
                $diagMsg = '';
                @ldap_get_option($conn, LDAP_OPT_DIAGNOSTIC_MESSAGE, $diagMsg);
                $detail = $diagMsg ? " – Details: {$diagMsg}" : '';
                // Konto wurde angelegt – Fehler melden aber nicht abbrechen
                \Illuminate\Support\Facades\Log::warning("Onboarding: ldap_modify teilweise fehlgeschlagen für {$userDn}: {$err}{$detail}");
            }
        }

        // Schritt 5: Sicherheitsgruppen zuweisen
        foreach ($vorlage->gruppen as $gruppe) {
            $this->addToGroup($conn, $userDn, $gruppe->ad_group_dn);
        }

        ldap_unbind($conn);

        return [
            'distinguished_name' => $userDn,
            'samaccountname'     => $samaccountname,
            'upn'                => $upn,
            'password_warning'   => $passwordWarning,
        ];
    }

    /** Prüft ob die Write-Verbindung hergestellt werden kann. */
    public function testWriteConnection(): array
    {
        try {
            $adSettings = AdUserSettings::getSingleton();
            $obSettings = OnboardingSettings::getSingleton();

            if (empty($adSettings->server)) {
                return ['ok' => false, 'message' => 'Kein LDAP-Server konfiguriert (AD-Benutzer Einstellungen).'];
            }

            $conn = $this->connect($adSettings);
            $this->bind($conn, $adSettings, $obSettings);
            ldap_unbind($conn);

            $account = $obSettings->ldap_write_bind_dn ?: $adSettings->bind_dn;
            return ['ok' => true, 'message' => "Verbindung erfolgreich als: {$account}"];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── Public write operations (für AdUserManageController) ─────────────────

    /** Fügt einen Benutzer zu einer AD-Gruppe hinzu. */
    public function addUserToGroup(string $userDn, string $groupDn): void
    {
        $adSettings = AdUserSettings::getSingleton();
        $obSettings = OnboardingSettings::getSingleton();
        $conn       = $this->connect($adSettings);
        $this->bind($conn, $adSettings, $obSettings);

        if (!@ldap_mod_add($conn, $groupDn, ['member' => [$userDn]])) {
            $err = ldap_error($conn);
            $diagMsg = '';
            @ldap_get_option($conn, LDAP_OPT_DIAGNOSTIC_MESSAGE, $diagMsg);
            ldap_unbind($conn);
            $detail = $diagMsg ? " – Details: {$diagMsg}" : '';
            throw new \RuntimeException("Benutzer konnte nicht zur Gruppe hinzugefügt werden: {$err}{$detail}");
        }

        ldap_unbind($conn);
    }

    /** Entfernt einen Benutzer aus einer AD-Gruppe. */
    public function removeUserFromGroup(string $userDn, string $groupDn): void
    {
        $adSettings = AdUserSettings::getSingleton();
        $obSettings = OnboardingSettings::getSingleton();
        $conn       = $this->connect($adSettings);
        $this->bind($conn, $adSettings, $obSettings);

        if (!@ldap_mod_del($conn, $groupDn, ['member' => [$userDn]])) {
            $err = ldap_error($conn);
            $diagMsg = '';
            @ldap_get_option($conn, LDAP_OPT_DIAGNOSTIC_MESSAGE, $diagMsg);
            ldap_unbind($conn);
            $detail = $diagMsg ? " – Details: {$diagMsg}" : '';
            throw new \RuntimeException("Benutzer konnte nicht aus der Gruppe entfernt werden: {$err}{$detail}");
        }

        ldap_unbind($conn);
    }

    /** Sucht AD-Gruppen (Sicherheits- und Verteilergruppen) anhand eines Suchbegriffs. */
    public function searchGroups(string $query): array
    {
        if (!extension_loaded('ldap')) return [];

        try {
            $adSettings = AdUserSettings::getSingleton();
            $obSettings = OnboardingSettings::getSingleton();
            $conn       = $this->connect($adSettings);
            $this->bind($conn, $adSettings, $obSettings);

            $baseDn  = $obSettings->group_search_base_dn ?: $adSettings->base_dn;
            $escaped = ldap_escape($query, '', LDAP_ESCAPE_FILTER);
            $filter  = "(&(objectClass=group)(cn=*{$escaped}*))";

            $result = @ldap_search($conn, $baseDn, $filter,
                ['cn', 'distinguishedname', 'grouptype'], 0, 50);

            if (!$result) {
                ldap_unbind($conn);
                return [];
            }

            $entries = ldap_get_entries($conn, $result);
            ldap_unbind($conn);

            $groups = [];
            for ($i = 0; $i < ($entries['count'] ?? 0); $i++) {
                $groupType = (int)($entries[$i]['grouptype'][0] ?? 0);
                $groups[] = [
                    'dn'   => $entries[$i]['distinguishedname'][0] ?? '',
                    'name' => $entries[$i]['cn'][0] ?? '',
                    'type' => $groupType < 0 ? 'security' : 'distribution',
                ];
            }

            return $groups;
        } catch (\Throwable) {
            return [];
        }
    }

    /** Gibt Gruppentypen (security|distribution) für eine Liste von DNs zurück. */
    public function getGroupTypes(array $groupDns): array
    {
        if (empty($groupDns) || !extension_loaded('ldap')) return [];

        $adSettings = AdUserSettings::getSingleton();
        $obSettings = OnboardingSettings::getSingleton();
        $conn       = $this->connect($adSettings);
        $this->bind($conn, $adSettings, $obSettings);

        $escaped   = array_map(fn($dn) => '(distinguishedName=' . ldap_escape($dn, '', LDAP_ESCAPE_FILTER) . ')', $groupDns);
        $dnFilter  = count($escaped) === 1 ? $escaped[0] : '(|' . implode('', $escaped) . ')';
        $filter    = "(&(objectClass=group){$dnFilter})";
        $baseDn    = $adSettings->base_dn;

        $result = @ldap_search($conn, $baseDn, $filter, ['distinguishedname', 'grouptype'], 0, 500);

        $types = [];
        if ($result) {
            $entries = ldap_get_entries($conn, $result);
            for ($i = 0; $i < ($entries['count'] ?? 0); $i++) {
                $dn        = strtolower($entries[$i]['distinguishedname'][0] ?? '');
                $groupType = (int)($entries[$i]['grouptype'][0] ?? 0);
                if ($dn) $types[$dn] = $groupType < 0 ? 'security' : 'distribution';
            }
        }

        ldap_unbind($conn);
        return $types;
    }

    /** Zählt alle Gruppen in der konfigurierten Suchbasis (für den Test-Button). */
    public function countGroups(): array
    {
        if (!extension_loaded('ldap')) {
            throw new \RuntimeException('PHP LDAP-Extension ist nicht aktiviert.');
        }

        $adSettings = AdUserSettings::getSingleton();
        $obSettings = OnboardingSettings::getSingleton();
        $conn       = $this->connect($adSettings);
        $this->bind($conn, $adSettings, $obSettings);

        $baseDn = $obSettings->group_search_base_dn ?: $adSettings->base_dn;
        $result = @ldap_search($conn, $baseDn, '(objectClass=group)', ['grouptype'], 0, 0);

        if (!$result) {
            $err = ldap_error($conn);
            ldap_unbind($conn);
            throw new \RuntimeException("Gruppensuche fehlgeschlagen: {$err}");
        }

        $entries  = ldap_get_entries($conn, $result);
        ldap_unbind($conn);

        $security     = 0;
        $distribution = 0;
        for ($i = 0; $i < ($entries['count'] ?? 0); $i++) {
            $groupType = (int)($entries[$i]['grouptype'][0] ?? 0);
            if ($groupType < 0) {
                $security++;
            } else {
                $distribution++;
            }
        }

        return [
            'security'     => $security,
            'distribution' => $distribution,
            'total'        => $security + $distribution,
            'base_dn'      => $baseDn,
        ];
    }

    // ─── private ─────────────────────────────────────────────────────────────

    private function connect(AdUserSettings $settings): mixed
    {
        putenv('LDAPTLS_REQCERT=never');
        ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);

        if ($settings->use_ssl) {
            // STARTTLS (ldap:// Port 389 + TLS-Upgrade) ist in PHP zuverlässiger als
            // ldaps:// auf Port 636, weil ldaps:// bei Windows-internen CA-Zertifikaten
            // stillschweigend ohne TLS verbindet und AD dann unicodePwd ablehnt.
            $conn = ldap_connect('ldap://' . $settings->server, 389);
        } else {
            $conn = ldap_connect('ldap://' . $settings->server, $settings->port);
        }

        if (!$conn) {
            throw new \RuntimeException("Verbindung zu {$settings->server} konnte nicht hergestellt werden.");
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, 10);

        if ($settings->use_ssl) {
            if (!@ldap_start_tls($conn)) {
                throw new \RuntimeException('STARTTLS fehlgeschlagen: ' . ldap_error($conn));
            }
        }

        return $conn;
    }

    private function bind(mixed $conn, AdUserSettings $adSettings, OnboardingSettings $obSettings): void
    {
        // Write-Account aus Onboarding-Einstellungen bevorzugen; sonst Read-Account nutzen
        $bindDn  = $obSettings->ldap_write_bind_dn ?: $adSettings->bind_dn;
        $bindPw  = $obSettings->ldap_write_bind_password ?: $adSettings->bind_password;

        if (empty($bindDn)) {
            throw new \RuntimeException('Kein LDAP-Bind-Account konfiguriert. Bitte in den Onboarding-Einstellungen einen Write-Account hinterlegen.');
        }

        if (!@ldap_bind($conn, $bindDn, $bindPw)) {
            throw new \RuntimeException('LDAP-Authentifizierung fehlgeschlagen: ' . ldap_error($conn));
        }
    }

    private function setPassword(mixed $conn, string $userDn, string $password): void
    {
        // AD erwartet das Passwort als UTF-16LE, eingeschlossen in Anführungszeichen
        $encoded = mb_convert_encoding('"' . $password . '"', 'UTF-16LE');

        if (!@ldap_modify($conn, $userDn, ['unicodePwd' => [$encoded]])) {
            $err     = ldap_error($conn);
            $diagMsg = '';
            @ldap_get_option($conn, LDAP_OPT_DIAGNOSTIC_MESSAGE, $diagMsg);
            $detail = $diagMsg ? " – AD-Details: {$diagMsg}" : '';
            throw new \RuntimeException("Passwort konnte nicht gesetzt werden: {$err}{$detail}");
        }
    }

    /** Setzt das finale Passwort eines bereits angelegten Benutzers (Phase 2). */
    public function setFinalPassword(string $userDn, string $password): void
    {
        if (!extension_loaded('ldap')) {
            throw new \RuntimeException('PHP LDAP-Extension ist nicht aktiviert.');
        }

        $adSettings = AdUserSettings::getSingleton();
        $obSettings = OnboardingSettings::getSingleton();
        $conn       = $this->connect($adSettings);
        $this->bind($conn, $adSettings, $obSettings);

        try {
            $this->setPassword($conn, $userDn, $password);
            // pwdLastSet = 0 → Passwort muss beim nächsten Login geändert werden
            @ldap_modify($conn, $userDn, ['pwdLastSet' => ['0']]);
        } finally {
            ldap_unbind($conn);
        }
    }

    /** Entfernt homeDirectory und homeDrive aus dem AD-Profil (nach erstem Login des Benutzers). */
    public function clearHomeDirectory(string $userDn): void
    {
        if (!extension_loaded('ldap')) {
            throw new \RuntimeException('PHP LDAP-Extension ist nicht aktiviert.');
        }

        $adSettings = AdUserSettings::getSingleton();
        $obSettings = OnboardingSettings::getSingleton();
        $conn       = $this->connect($adSettings);
        $this->bind($conn, $adSettings, $obSettings);

        try {
            // Attribute auf leeren String setzen – AD löscht sie daraufhin
            @ldap_mod_del($conn, $userDn, ['homeDirectory' => []]);
            @ldap_mod_del($conn, $userDn, ['homeDrive'     => []]);
        } finally {
            ldap_unbind($conn);
        }
    }

    private function enableAccount(mixed $conn, string $userDn, bool $forceChange = false): void
    {
        // 512 = normales Konto, aktiviert
        @ldap_modify($conn, $userDn, ['userAccountControl' => ['512']]);
        // pwdLastSet = -1 → kein Zwang zur Änderung (Phase 1 / temporäres Passwort)
        // pwdLastSet =  0 → Änderung beim nächsten Login erzwungen (Phase 2 / finales Passwort)
        @ldap_modify($conn, $userDn, ['pwdLastSet' => [$forceChange ? '0' : '-1']]);
    }

    private function addToGroup(mixed $conn, string $userDn, string $groupDn): void
    {
        @ldap_mod_add($conn, $groupDn, ['member' => [$userDn]]);
    }

    /**
     * Gibt einen DN zurück, der in der OU noch nicht existiert.
     * Bei Namenskollision wird erst der sAMAccountName angehängt, dann eine Zahl –
     * ohne Klammern, da Windows AD () in DN-Werten ablehnt (BAD_ATT_SYNTAX).
     */
    private function buildUniqueDn(mixed $conn, string $cn, string $ouDn, string $samaccountname): string
    {
        $candidates = [
            "CN={$cn},{$ouDn}",
            "CN={$cn} {$samaccountname},{$ouDn}",
        ];
        for ($i = 2; $i <= 9; $i++) {
            $candidates[] = "CN={$cn} {$i},{$ouDn}";
        }

        foreach ($candidates as $dn) {
            $result = @ldap_read($conn, $dn, '(objectClass=*)', ['dn'], 0, 1);
            if (!$result || ldap_count_entries($conn, $result) === 0) {
                return $dn;
            }
        }

        throw new \RuntimeException("Kein eindeutiger CN für '{$cn}' in der OU gefunden.");
    }
}
