<?php

namespace App\Modules\Server\Console\Commands;

use App\Modules\Server\Services\ServerSyncService;
use Illuminate\Console\Command;

class SyncServers extends Command
{
    protected $signature   = 'server:sync {--dry-run : Nur anzeigen, nicht speichern}';
    protected $description = 'Synchronisiert Serverkonten aus dem Active Directory';

    public function handle(ServerSyncService $syncService): int
    {
        $this->info('Starte Server-LDAP-Synchronisation…');

        try {
            $result = $syncService->sync($this->option('dry-run'));

            $this->info("✓ Synchronisiert: {$result['synced']}");
            $this->info("✓ Als nicht synchronisiert markiert: {$result['marked_unsynced']}");

            if ($this->option('dry-run')) {
                $this->warn('Dry-Run: Keine Änderungen gespeichert.');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Fehler: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
