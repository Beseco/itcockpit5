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
        $userDn = "CN={$cn},{$ouDn}";

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
            // Schritt 3: Nur wenn Passwort gesetzt wurde, Konto aktivieren
            $this->enableAccount($conn, $userDn);
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
            'telephoneNumber'          => $rufnummer,
            'facsimileTelephoneNumber' => $data['fax'] ?? null,
            'streetAddress'            => $vorlage->strasse,
            'postalCode'               => $vorlage->plz,
            'l'                        => $vorlage->ort,
            'profilePath'              => $data['profilpfad'] ?? null,
            'homeDirectory'            => $data['heimatverzeichnis'] ?? null,
            'scriptPath'               => $vorlage->anmeldeskript,
            'department'               => $vorlage->abteilung_ad,
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

    /** Sucht AD-Gruppen anhand eines Suchbegriffs. */
    public function searchGroups(string $query): array
    {
        if (!extension_loaded('ldap')) return [];

        try {
            $adSettings = AdUserSettings::getSingleton();
            $obSettings = OnboardingSettings::getSingleton();
            $conn       = $this->connect($adSettings);
            $this->bind($conn, $adSettings, $obSettings);

            $escaped = ldap_escape($query, '', LDAP_ESCAPE_FILTER);
            $filter  = "(&(objectClass=group)(cn=*{$escaped}*))";

            $result = @ldap_search($conn, $adSettings->base_dn, $filter,
                ['cn', 'distinguishedname'], 0, 50);

            if (!$result) {
                ldap_unbind($conn);
                return [];
            }

            $entries = ldap_get_entries($conn, $result);
            ldap_unbind($conn);

            $groups = [];
            for ($i = 0; $i < ($entries['count'] ?? 0); $i++) {
                $groups[] = [
                    'dn'   => $entries[$i]['distinguishedname'][0] ?? '',
                    'name' => $entries[$i]['cn'][0] ?? '',
                ];
            }

            return $groups;
        } catch (\Throwable) {
            return [];
        }
    }

    // ─── private ─────────────────────────────────────────────────────────────

    private function connect(AdUserSettings $settings): mixed
    {
        $host = ($settings->use_ssl ? 'ldaps://' : 'ldap://') . $settings->server;
        $conn = ldap_connect($host, $settings->port);

        if (!$conn) {
            throw new \RuntimeException("Verbindung zu {$host} konnte nicht hergestellt werden.");
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, 10);

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
            $err = ldap_error($conn);
            // Passwort konnte nicht gesetzt werden – Konto trotzdem anlegen, aber warnen
            throw new \RuntimeException(
                "Passwort konnte nicht gesetzt werden: {$err}. " .
                "Hinweis: Für das Setzen von AD-Passwörtern ist LDAPS (Port 636, SSL) erforderlich."
            );
        }
    }

    private function enableAccount(mixed $conn, string $userDn): void
    {
        // 512 = normales Konto, aktiviert
        @ldap_modify($conn, $userDn, ['userAccountControl' => ['512']]);
        // pwdLastSet = 0 → Passwort muss beim nächsten Login geändert werden
        @ldap_modify($conn, $userDn, ['pwdLastSet' => ['0']]);
    }

    private function addToGroup(mixed $conn, string $userDn, string $groupDn): void
    {
        @ldap_mod_add($conn, $groupDn, ['member' => [$userDn]]);
    }
}
