<?php

namespace App\Modules\AdUsers\Services;

use App\Modules\AdUsers\Models\AdUser;
use App\Services\AuditLogger;

class AdUserSyncService
{
    public function __construct(
        private LdapConnectionService $ldap,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * Alle AD-Benutzer synchronisieren.
     * @return array{updated: int, deactivated: int}
     */
    public function sync(bool $dryRun = false): array
    {
        $adUsers   = $this->ldap->getAllUsers();
        $updated   = 0;
        $foundSams = [];

        foreach ($adUsers as $adUser) {
            $sam = LdapConnectionService::getAttr($adUser, 'samaccountname');
            if (!$sam) continue;

            $foundSams[] = strtolower($sam);

            $data = [
                'vorname'            => LdapConnectionService::getAttr($adUser, 'givenname'),
                'nachname'           => LdapConnectionService::getAttr($adUser, 'sn'),
                'anzeigename'        => LdapConnectionService::getAttr($adUser, 'displayname'),
                'email'              => LdapConnectionService::getAttr($adUser, 'mail'),
                'organisation'       => LdapConnectionService::getAttr($adUser, 'company'),
                'abteilung'          => LdapConnectionService::getAttr($adUser, 'department'),
                'telefon'            => LdapConnectionService::getAttr($adUser, 'telephonenumber'),
                'distinguished_name' => LdapConnectionService::getAttr($adUser, 'distinguishedname'),
                'ad_vorhanden'       => true,
                'ad_aktiv'           => !LdapConnectionService::isDisabled($adUser),
                'letzter_import_at'  => now(),
                'raw_data'           => $adUser,
            ];

            if (!$dryRun) {
                AdUser::updateOrCreate(
                    ['samaccountname' => $sam],
                    $data
                );
            }
            $updated++;
        }

        // Benutzer die nicht mehr im AD gefunden wurden → als nicht vorhanden markieren
        $deactivated = 0;
        if (!$dryRun && count($foundSams) > 0) {
            $deactivated = AdUser::where('ad_vorhanden', true)
                ->whereNotIn(\DB::raw('LOWER(samaccountname)'), $foundSams)
                ->update(['ad_vorhanden' => false]);
        }

        if (!$dryRun) {
            $this->auditLogger->logModuleAction('AdUsers', 'sync', [
                'updated'     => $updated,
                'deactivated' => $deactivated,
            ]);
        }

        return ['updated' => $updated, 'deactivated' => $deactivated];
    }
}
