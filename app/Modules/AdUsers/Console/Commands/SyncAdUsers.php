<?php

namespace App\Modules\AdUsers\Console\Commands;

use App\Modules\AdUsers\Services\AdUserSyncService;
use Illuminate\Console\Command;

class SyncAdUsers extends Command
{
    protected $signature = 'adusers:sync {--dry-run : Nur anzeigen, nicht speichern}';
    protected $description = 'Synchronisiert Benutzer aus dem Active Directory';

    public function handle(AdUserSyncService $syncService): int
    {
        $this->info('Starte AD-Benutzer-Synchronisation…');

        try {
            $result = $syncService->sync($this->option('dry-run'));

            $this->info("✓ Importiert/Aktualisiert: {$result['updated']}");
            $this->info("✓ Als nicht vorhanden markiert: {$result['deactivated']}");

            if ($this->option('dry-run')) {
                $this->warn('Dry-Run: Keine Änderungen gespeichert.');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Fehler: ' . $e->getMessage());
            return 1;
        }
    }
}
