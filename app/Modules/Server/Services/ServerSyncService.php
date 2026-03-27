<?php

namespace App\Modules\Server\Services;

use App\Modules\AdUsers\Services\LdapConnectionService;
use App\Modules\Server\Models\Server;
use App\Services\AuditLogger;

class ServerSyncService
{
    private const BASE_DN = 'OU=Server,OU=LRA-FS,DC=lra,DC=lan';
    private const FILTER  = '(objectClass=computer)';
    private const ATTRS   = [
        'cn', 'dnshostname', 'operatingsystem', 'operatingsystemversion',
        'description', 'managedby', 'distinguishedname',
    ];

    public function __construct(
        private LdapConnectionService $ldap,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @return array{synced: int, marked_unsynced: int, ips_resolved: int}
     */
    public function sync(bool $dryRun = false): array
    {
        $entries      = $this->ldap->searchWithBaseDn(self::BASE_DN, self::FILTER, self::ATTRS);
        $synced       = 0;
        $ipsResolved  = 0;
        $foundDns     = [];

        foreach ($entries as $entry) {
            $dn = LdapConnectionService::getAttr($entry, 'distinguishedname');
            if (!$dn) {
                continue;
            }

            $foundDns[]   = $dn;
            $dnsHostname  = LdapConnectionService::getAttr($entry, 'dnshostname');

            $data = [
                'name'             => LdapConnectionService::getAttr($entry, 'cn'),
                'dns_hostname'     => $dnsHostname,
                'operating_system' => LdapConnectionService::getAttr($entry, 'operatingsystem'),
                'os_version'       => LdapConnectionService::getAttr($entry, 'operatingsystemversion'),
                'description'      => LdapConnectionService::getAttr($entry, 'description'),
                'managed_by_ldap'  => LdapConnectionService::getAttr($entry, 'managedby'),
                'ldap_synced'      => true,
                'last_sync_at'     => now(),
                'raw_ldap_data'    => $entry,
            ];

            // DNS-Auflösung: Hostname → IP
            if ($dnsHostname) {
                $resolved = self::resolveDns($dnsHostname);
                if ($resolved) {
                    $data['ip_address'] = $resolved;
                    $ipsResolved++;
                }
            }

            if (!$dryRun) {
                $existing = Server::where('distinguished_name', $dn)->first();
                if ($existing) {
                    $existing->update($data);
                } else {
                    $data['revision_date'] = now()->addDays(7);
                    Server::create(array_merge(['distinguished_name' => $dn], $data));
                }
            }

            $synced++;
        }

        $markedUnsynced = 0;
        if (!$dryRun && count($foundDns) > 0) {
            $markedUnsynced = Server::where('ldap_synced', true)
                ->whereNotIn('distinguished_name', $foundDns)
                ->update(['ldap_synced' => false]);
        }

        if (!$dryRun) {
            $this->auditLogger->logModuleAction('Server', 'sync', [
                'synced'          => $synced,
                'ips_resolved'    => $ipsResolved,
                'marked_unsynced' => $markedUnsynced,
            ]);
        }

        return ['synced' => $synced, 'marked_unsynced' => $markedUnsynced, 'ips_resolved' => $ipsResolved];
    }

    /**
     * Löst für alle Server mit Hostname aber ohne IP die IP per DNS auf.
     *
     * @return int Anzahl aufgelöster IPs
     */
    public function resolveIpAddresses(): int
    {
        $resolved = 0;

        Server::whereNotNull('dns_hostname')
            ->chunk(100, function ($servers) use (&$resolved) {
                foreach ($servers as $server) {
                    $ip = self::resolveDns($server->dns_hostname);
                    if ($ip && $ip !== $server->ip_address) {
                        $server->update(['ip_address' => $ip]);
                        $resolved++;
                    }
                }
            });

        return $resolved;
    }

    /**
     * DNS-Auflösung: gibt die IP zurück oder null wenn nicht auflösbar.
     */
    public static function resolveDns(string $hostname): ?string
    {
        $result = @gethostbyname($hostname);
        // gethostbyname() gibt den Hostnamen zurück wenn er nicht auflösbar ist
        return ($result !== $hostname) ? $result : null;
    }
}
