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
     * @return array{synced: int, marked_unsynced: int}
     */
    public function sync(bool $dryRun = false): array
    {
        $entries   = $this->ldap->searchWithBaseDn(self::BASE_DN, self::FILTER, self::ATTRS);
        $synced    = 0;
        $foundDns  = [];

        foreach ($entries as $entry) {
            $dn = LdapConnectionService::getAttr($entry, 'distinguishedname');
            if (!$dn) {
                continue;
            }

            $foundDns[] = $dn;

            $data = [
                'name'             => LdapConnectionService::getAttr($entry, 'cn'),
                'dns_hostname'     => LdapConnectionService::getAttr($entry, 'dnshostname'),
                'operating_system' => LdapConnectionService::getAttr($entry, 'operatingsystem'),
                'os_version'       => LdapConnectionService::getAttr($entry, 'operatingsystemversion'),
                'description'      => LdapConnectionService::getAttr($entry, 'description'),
                'managed_by_ldap'  => LdapConnectionService::getAttr($entry, 'managedby'),
                'ldap_synced'      => true,
                'last_sync_at'     => now(),
                'raw_ldap_data'    => $entry,
            ];

            if (!$dryRun) {
                Server::updateOrCreate(['distinguished_name' => $dn], $data);
            }

            $synced++;
        }

        // Zuvor synchronisierte Server die nicht mehr in LDAP gefunden wurden als nicht synchronisiert markieren
        $markedUnsynced = 0;
        if (!$dryRun && count($foundDns) > 0) {
            $markedUnsynced = Server::where('ldap_synced', true)
                ->whereNotIn('distinguished_name', $foundDns)
                ->update(['ldap_synced' => false]);
        }

        if (!$dryRun) {
            $this->auditLogger->logModuleAction('Server', 'sync', [
                'synced'          => $synced,
                'marked_unsynced' => $markedUnsynced,
            ]);
        }

        return ['synced' => $synced, 'marked_unsynced' => $markedUnsynced];
    }
}
