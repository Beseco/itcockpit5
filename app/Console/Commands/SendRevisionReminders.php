<?php

namespace App\Console\Commands;

use App\Mail\RevisionMail;
use App\Models\Applikation;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendRevisionReminders extends Command
{
    protected $signature   = 'applikationen:send-revision-reminders';
    protected $description = 'Sendet Revisions-E-Mails für Applikationen, deren Revisionsdatum erreicht ist.';

    public function __construct(private AuditLogger $auditLogger)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $apps = Applikation::with('adminUser')
            ->whereNotNull('revision_date')
            ->whereDate('revision_date', '<=', now())
            ->whereNull('revision_notified_at')
            ->get();

        if ($apps->isEmpty()) {
            $this->info('Keine fälligen Revisionen.');
            return self::SUCCESS;
        }

        $sent   = 0;
        $skipped = 0;

        foreach ($apps as $app) {
            if (!$app->adminUser || empty($app->adminUser->email)) {
                $this->warn("  Übersprungen (kein Admin/E-Mail): {$app->name}");
                $skipped++;
                continue;
            }

            // Token sicherstellen
            if (!$app->revision_token) {
                $app->revision_token = Str::random(64);
            }

            try {
                Mail::to($app->adminUser->email)->send(new RevisionMail($app));

                $app->revision_notified_at = now();
                $app->save();

                try {
                    $this->auditLogger->log('Applikation', 'Revision E-Mail versendet', [
                        'id'    => $app->id,
                        'name'  => $app->name,
                        'email' => $app->adminUser->email,
                    ]);
                } catch (\Exception) {
                    // AuditLog erfordert eingeloggten User – im CLI-Kontext überspringen
                }

                $this->info("  E-Mail gesendet: {$app->name} → {$app->adminUser->email}");
                $sent++;
            } catch (\Exception $e) {
                $this->error("  Fehler bei {$app->name}: " . $e->getMessage());
            }
        }

        $this->info("Fertig: {$sent} gesendet, {$skipped} übersprungen.");
        return self::SUCCESS;
    }
}
