<?php

namespace App\Modules\AdUsers\Services;

use App\Modules\AdUsers\Models\AdUserSettings;
use Illuminate\Support\Collection;
use LdapRecord\Connection;
use LdapRecord\LdapRecordException;

class LdapConnectionService
{
    private AdUserSettings $settings;

    public function __construct()
    {
        $this->settings = AdUserSettings::getSingleton();
    }

    /** LdapRecord-Connection aus den Einstellungen aufbauen */
    public function buildConnection(): Connection
    {
        $config = [
            'hosts'    => [$this->settings->server],
            'port'     => $this->settings->port,
            'base_dn'  => $this->settings->base_dn,
            'username' => $this->settings->anonymous_bind ? null : $this->settings->bind_dn,
            'password' => $this->settings->anonymous_bind ? null : $this->settings->bind_password,
            'use_ssl'  => $this->settings->use_ssl,
            'use_tls'  => false,
            'timeout'  => 5,
        ];

        return new Connection($config);
    }

    /** Verbindung testen (Bind) */
    public function testConnection(): array
    {
        if (empty($this->settings->server)) {
            return ['success' => false, 'message' => 'Kein Server konfiguriert.'];
        }

        try {
            $connection = $this->buildConnection();
            $connection->connect();

            return ['success' => true, 'message' => 'Verbindung erfolgreich hergestellt.'];
        } catch (LdapRecordException $e) {
            return ['success' => false, 'message' => 'LDAP-Fehler: ' . $e->getMessage()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Verbindungsfehler: ' . $e->getMessage()];
        }
    }

    /** Testabfrage: Anzahl gefundener Benutzer */
    public function testQuery(): array
    {
        try {
            $connection = $this->buildConnection();
            $connection->connect();

            $results = $connection->query()
                ->in($this->settings->base_dn)
                ->rawFilter('(&(objectClass=user)(objectCategory=person))')
                ->select(['samaccountname'])
                ->get();

            return ['success' => true, 'count' => count($results), 'message' => count($results) . ' Benutzer gefunden.'];
        } catch (\Exception $e) {
            return ['success' => false, 'count' => 0, 'message' => 'Fehler: ' . $e->getMessage()];
        }
    }

    /** Alle Benutzer aus dem AD laden */
    public function getAllUsers(): Collection
    {
        $connection = $this->buildConnection();
        $connection->connect();

        $results = $connection->query()
            ->in($this->settings->base_dn)
            ->rawFilter('(&(objectClass=user)(objectCategory=person))')
            ->select([
                'samaccountname', 'givenname', 'sn', 'displayname',
                'mail', 'company', 'department', 'telephonenumber',
                'distinguishedname', 'useraccountcontrol',
            ])
            ->paginate(1000);

        return collect($results);
    }

    /** Prüft ob ein Konto deaktiviert ist (Bit 2 von userAccountControl) */
    public static function isDisabled(array $user): bool
    {
        $uac = (int) ($user['useraccountcontrol'][0] ?? 0);
        return (bool) ($uac & 2);
    }

    public static function getAttr(array $user, string $key): ?string
    {
        $val = $user[$key][0] ?? null;
        return $val !== null ? (string) $val : null;
    }
}
