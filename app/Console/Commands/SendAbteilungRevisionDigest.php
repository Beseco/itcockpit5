<?php

namespace App\Console\Commands;

use App\Mail\AbteilungRevisionMail;
use App\Models\Abteilung;
use App\Models\AbteilungRevisionSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendAbteilungRevisionDigest extends Command
{
    protected $signature   = 'abteilungen:send-revision-digest';
    protected $description = 'Sendet Erinnerungs-E-Mails an Vorgesetzte mit überfälligem Revisionsdatum.';

    public function handle(): int
    {
        $settings = AbteilungRevisionSettings::getSingleton();

        if (! $settings->enabled) {
            $this->info('Abteilungs-Revisions-Digest ist deaktiviert.');
            return self::SUCCESS;
        }

        if (! $settings->isDue()) {
            $this->info('Noch nicht fällig (Wochentag, Stunde oder Intervall nicht erfüllt).');
            return self::SUCCESS;
        }

        $abteilungen = Abteilung::with(['vorgesetzter', 'stellvertreter'])
            ->whereNotNull('revision_date')
            ->whereDate('revision_date', '<=', now())
            ->whereNull('revision_completed_at')
            ->get();

        if ($abteilungen->isEmpty()) {
            $this->info('Keine überfälligen Revisionen vorhanden.');
            $settings->last_sent_at = now();
            $settings->save();
            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($abteilungen as $abteilung) {
            $empfaenger = collect();

            if ($abteilung->vorgesetzter?->email) {
                $empfaenger->push([
                    'email' => $abteilung->vorgesetzter->email,
                    'name'  => $abteilung->vorgesetzter->anzeigename,
                ]);
            }

            if ($abteilung->stellvertreter?->email
                && $abteilung->stellvertreter->email !== $abteilung->vorgesetzter?->email) {
                $empfaenger->push([
                    'email' => $abteilung->stellvertreter->email,
                    'name'  => $abteilung->stellvertreter->anzeigename,
                ]);
            }

            if ($empfaenger->isEmpty()) {
                $this->warn("  Übersprungen: {$abteilung->anzeigename} – kein Vorgesetzter mit E-Mail");
                continue;
            }

            $abteilung->ensureRevisionToken();

            foreach ($empfaenger as $e) {
                try {
                    Mail::to($e['email'])
                        ->send(new AbteilungRevisionMail($abteilung, $e['name']));
                    $this->info("  Gesendet: {$abteilung->anzeigename} → {$e['name']} ({$e['email']})");
                    $sent++;
                } catch (\Exception $ex) {
                    $this->error("  Fehler bei {$e['email']}: " . $ex->getMessage());
                }
            }
        }

        $settings->last_sent_at = now();
        $settings->save();

        $this->info("Fertig: {$sent} E-Mail(s) gesendet.");
        return self::SUCCESS;
    }
}
