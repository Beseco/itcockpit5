<?php

namespace App\Console\Commands;

use App\Mail\RevisionDigestMail;
use App\Models\Applikation;
use App\Models\ApplikationRevisionSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendRevisionDigest extends Command
{
    protected $signature   = 'applikationen:send-revision-digest';
    protected $description = 'Sendet eine Zusammenfassungs-E-Mail an jeden IT-Administrator mit offenen Revisionen (konfigurierbar).';

    public function handle(): int
    {
        $settings = ApplikationRevisionSettings::getSingleton();

        if (! $settings->enabled) {
            $this->info('Digest-Erinnerung ist deaktiviert.');
            return self::SUCCESS;
        }

        if (! $settings->isDue()) {
            $this->info('Noch nicht fällig (Wochentag, Stunde oder Intervall nicht erfüllt).');
            return self::SUCCESS;
        }

        // Alle offenen Revisionen laden (fällig + noch nicht abgeschlossen)
        $apps = Applikation::with('adminUser')
            ->whereNotNull('revision_date')
            ->whereDate('revision_date', '<=', now())
            ->whereNull('revision_completed_at')
            ->get();

        if ($apps->isEmpty()) {
            $this->info('Keine offenen Revisionen vorhanden.');
            $settings->last_sent_at = now();
            $settings->save();
            return self::SUCCESS;
        }

        // Nach Admin-User gruppieren
        $grouped = $apps->filter(fn ($app) => $app->adminUser && ! empty($app->adminUser->email))
                        ->groupBy('admin_user_id');

        $sent    = 0;
        $skipped = $apps->count() - $apps->filter(fn ($app) => $app->adminUser && ! empty($app->adminUser->email))->count();

        foreach ($grouped as $adminUserId => $adminApps) {
            $admin = $adminApps->first()->adminUser;

            try {
                Mail::to($admin->email)->send(new RevisionDigestMail($admin, $adminApps));
                $this->info("  Digest gesendet: {$admin->name} ({$admin->email}) – {$adminApps->count()} Revision(en)");
                $sent++;
            } catch (\Exception $e) {
                $this->error("  Fehler bei {$admin->name}: " . $e->getMessage());
            }
        }

        $settings->last_sent_at = now();
        $settings->save();

        $this->info("Fertig: {$sent} Digest-E-Mail(s) gesendet, {$skipped} Revision(en) übersprungen (kein Admin/E-Mail).");
        return self::SUCCESS;
    }
}
