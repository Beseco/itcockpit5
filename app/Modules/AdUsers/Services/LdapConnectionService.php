<?php

namespace App\Modules\AdUsers\Services;

use App\Modules\AdUsers\Models\AdUserSettings;
use Illuminate\Support\Collection;

class LdapConnectionService
{
    private AdUserSettings $settings;

    public function __construct()
    {
        $this->settings = AdUserSettings::getSingleton();
    }

    /** LDAP-Verbindung aufbauen (native PHP) */
    private function connect(): mixed
    {
        if (!extension_loaded('ldap')) {
            throw new \RuntimeException('PHP LDAP-Extension ist nicht aktiviert.');
        }

        $host = ($this->settings->use_ssl ? 'ldaps://' : 'ldap://') . $this->settings->server;
        $conn = ldap_connect($host, $this->settings->port);

        if (!$conn) {
            throw new \RuntimeException('Verbindung zu ' . $host . ' konnte nicht hergestellt werden.');
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, 5);

        return $conn;
    }

    /** Bind (mit oder ohne Credentials) */
    private function bind(mixed $conn): void
    {
        if ($this->settings->anonymous_bind) {
            $ok = @ldap_bind($conn);
        } else {
            $ok = @ldap_bind($conn, $this->settings->bind_dn, $this->settings->bind_password);
        }

        if (!$ok) {
            throw new \RuntimeException('Authentifizierung fehlgeschlagen: ' . ldap_error($conn));
        }
    }

    /** Verbindung testen */
    public function testConnection(): array
    {
        if (empty($this->settings->server)) {
            return ['success' => false, 'message' => 'Kein Server konfiguriert.'];
        }

        try {
            $conn = $this->connect();
            $this->bind($conn);
            ldap_unbind($conn);
            return ['success' => true, 'message' => 'Verbindung erfolgreich hergestellt.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** Testabfrage: Anzahl gefundener Benutzer */
    public function testQuery(): array
    {
        if (empty($this->settings->server) || empty($this->settings->base_dn)) {
            return ['success' => false, 'count' => 0, 'message' => 'Server oder Base DN nicht konfiguriert.'];
        }

        try {
            $conn = $this->connect();
            $this->bind($conn);

            $result = @ldap_search(
                $conn,
                $this->settings->base_dn,
                '(&(objectClass=user)(objectCategory=person))',
                ['samaccountname']
            );

            if (!$result) {
                throw new \RuntimeException('Suchanfrage fehlgeschlagen: ' . ldap_error($conn));
            }

            $count = ldap_count_entries($conn, $result);
            ldap_unbind($conn);

            return ['success' => true, 'count' => $count, 'message' => "{$count} Benutzer gefunden."];
        } catch (\Exception $e) {
            return ['success' => false, 'count' => 0, 'message' => $e->getMessage()];
        }
    }

    /** Alle Benutzer aus dem AD laden */
    public function getAllUsers(): Collection
    {
        $conn = $this->connect();
        $this->bind($conn);

        $attrs = [
            'samaccountname', 'givenname', 'sn', 'displayname',
            'mail', 'company', 'department', 'telephonenumber',
            'distinguishedname', 'useraccountcontrol',
        ];

        $result = @ldap_search(
            $conn,
            $this->settings->base_dn,
            '(&(objectClass=user)(objectCategory=person))',
            $attrs,
            0,    // attrsonly
            0,    // sizelimit (0 = unlimitiert)
        );

        if (!$result) {
            throw new \RuntimeException('Suchanfrage fehlgeschlagen: ' . ldap_error($conn));
        }

        $entries = ldap_get_entries($conn, $result);
        ldap_unbind($conn);

        $users = collect();
        for ($i = 0; $i < ($entries['count'] ?? 0); $i++) {
            $users->push($entries[$i]);
        }

        return $users;
    }

    /**
     * Suche mit benutzerdefinierter Base DN – wiederverwendbar für andere Module (z.B. Server-Sync).
     *
     * @param  string   $baseDn  Die Base DN für diese Suche (überschreibt die gespeicherte Einstellung)
     * @param  string   $filter  LDAP-Suchfilter
     * @param  string[] $attrs   Zu ladende Attribute
     * @return \Illuminate\Support\Collection
     */
    public function searchWithBaseDn(string $baseDn, string $filter, array $attrs): \Illuminate\Support\Collection
    {
        $conn = $this->connect();
        $this->bind($conn);

        $result = @ldap_search($conn, $baseDn, $filter, $attrs, 0, 0);

        if (!$result) {
            throw new \RuntimeException('Suchanfrage fehlgeschlagen: ' . ldap_error($conn));
        }

        $entries = ldap_get_entries($conn, $result);
        ldap_unbind($conn);

        $items = collect();
        for ($i = 0; $i < ($entries['count'] ?? 0); $i++) {
            $items->push($entries[$i]);
        }

        return $items;
    }

    /** Prüft ob ein Konto deaktiviert ist (Bit 2 von userAccountControl) */
    public static function isDisabled(array $user): bool
    {
        $uac = (int) ($user['useraccountcontrol'][0] ?? 0);
        return (bool) ($uac & 2);
    }

    /** Attributwert sicher auslesen */
    public static function getAttr(array $user, string $key): ?string
    {
        $val = $user[$key][0] ?? null;
        return $val !== null && $val !== '' ? (string) $val : null;
    }
}
