<?php

namespace App\Modules\AdUsers\Console\Commands;

use App\Mail\OffboardingDeaktivierungMail;
use App\Mail\OffboardingLoeschungMail;
use App\Modules\AdUsers\Models\OffboardingRecord;
use App\Modules\AdUsers\Services\LdapConnectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OffboardingReminders extends Command
{
    protected $signature   = 'adusers:offboarding-reminders {--dry-run : Nur anzeigen, keine Mails senden}';
    protected $description = 'Sendet Deaktivierungs- und Löschungs-Erinnerungen für fällige Offboarding-Vorgänge';

    public function handle(): int
    {
        $dryRun     = $this->option('dry-run');
        $deaktCount = 0;
        $loeschCount = 0;

        // LDAP-Service (optional – schlägt nicht fehl wenn nicht konfiguriert)
        $ldap = $this->getLdapService();

        // ── Deaktivierungen (datum_ausscheiden = heute) ──────────────────────
        $deaktRecords = OffboardingRecord::whereDate('datum_ausscheiden', today())
            ->whereNull('deaktivierung_benachrichtigt_at')
            ->whereNotIn('status', ['abgeschlossen'])
            ->whereNotNull('anleger_user_id')
            ->with('anleger')
            ->get();

        foreach ($deaktRecords as $record) {
            if (!$record->anleger?->email) {
                $this->warn("Kein Admin-E-Mail für Vorgang #{$record->id} ({$record->voller_name})");
                continue;
            }

            // LDAP prüfen: ist Konto schon deaktiviert?
            $ldapBestaetigt = $ldap ? $this->isAccountDisabled($ldap, $record->samaccountname) : false;

            if (!$dryRun) {
                $record->deaktivierung_token = Str::random(64);
                $record->deaktivierung_benachrichtigt_at = now();
                $record->save();

                Mail::to($record->anleger->email)
                    ->send(new OffboardingDeaktivierungMail($record, $ldapBestaetigt));
            }

            $ldapStatus = $ldapBestaetigt ? '(LDAP: bereits deaktiviert)' : '(LDAP: noch aktiv)';
            $this->info("Deaktivierung: {$record->voller_name} → {$record->anleger->email} {$ldapStatus}");
            $deaktCount++;
        }

        // ── Löschungen (datum_ausscheiden + 60 Tage = heute) ─────────────────
        $loeschRecords = OffboardingRecord::whereDate(
                \DB::raw('DATE_ADD(datum_ausscheiden, INTERVAL 60 DAY)'), today()
            )
            ->whereNull('loeschung_benachrichtigt_at')
            ->whereNull('loeschung_bestaetigt_at')
            ->whereNotIn('status', ['abgeschlossen'])
            ->whereNotNull('anleger_user_id')
            ->with('anleger')
            ->get();

        foreach ($loeschRecords as $record) {
            if (!$record->anleger?->email) {
                $this->warn("Kein Admin-E-Mail für Vorgang #{$record->id} ({$record->voller_name})");
                continue;
            }

            // LDAP prüfen: ist Konto schon gelöscht/nicht mehr vorhanden?
            $ldapBestaetigt = $ldap ? $this->isAccountGone($ldap, $record->samaccountname) : false;

            if (!$dryRun) {
                $record->loeschung_token = Str::random(64);
                $record->loeschung_benachrichtigt_at = now();
                $record->save();

                Mail::to($record->anleger->email)
                    ->send(new OffboardingLoeschungMail($record, $ldapBestaetigt));
            }

            $ldapStatus = $ldapBestaetigt ? '(LDAP: nicht mehr vorhanden)' : '(LDAP: noch vorhanden)';
            $this->info("Löschung: {$record->voller_name} → {$record->anleger->email} {$ldapStatus}");
            $loeschCount++;
        }

        $this->info("Fertig: {$deaktCount} Deaktivierungs-, {$loeschCount} Löschungs-Mails.");
        if ($dryRun) $this->warn('Dry-Run: Keine Mails gesendet.');

        return Command::SUCCESS;
    }

    /** LDAP-Service laden (gibt null zurück wenn nicht konfiguriert) */
    private function getLdapService(): ?LdapConnectionService
    {
        try {
            $service = app(LdapConnectionService::class);
            $test    = $service->testConnection();
            return $test['success'] ? $service : null;
        } catch (\Exception) {
            return null;
        }
    }

    /** Prüft ob Konto deaktiviert ist (userAccountControl Bit 2) */
    private function isAccountDisabled(LdapConnectionService $ldap, string $sam): bool
    {
        try {
            $entries = $ldap->searchWithBaseDn(
                'DC=lra,DC=lan',
                "(&(objectClass=user)(samaccountname={$sam}))",
                ['useraccountcontrol']
            );
            if ($entries->isEmpty()) return false;
            return LdapConnectionService::isDisabled($entries->first());
        } catch (\Exception) {
            return false;
        }
    }

    /** Prüft ob Konto im AD nicht mehr vorhanden ist */
    private function isAccountGone(LdapConnectionService $ldap, string $sam): bool
    {
        try {
            $entries = $ldap->searchWithBaseDn(
                'DC=lra,DC=lan',
                "(&(objectClass=user)(samaccountname={$sam}))",
                ['samaccountname']
            );
            return $entries->isEmpty();
        } catch (\Exception) {
            return false;
        }
    }
}
