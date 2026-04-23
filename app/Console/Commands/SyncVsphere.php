<?php

namespace App\Console\Commands;

use App\Modules\Server\Services\VsphereService;
use Illuminate\Console\Command;

class SyncVsphere extends Command
{
    protected $signature   = 'server:vsphere-sync';
    protected $description = 'Synchronisiert Server-Daten aus VMware vSphere (CPU, RAM, Disk, Status).';

    public function handle(): int
    {
        $service = VsphereService::make();

        if (!$service->isConfigured()) {
            $this->info('vSphere ist deaktiviert oder nicht konfiguriert. Sync übersprungen.');
            return self::SUCCESS;
        }

        try {
            $result = $service->sync();
            $this->info("vSphere-Sync abgeschlossen: {$result['updated']} aktualisiert, {$result['created']} neu angelegt, {$result['skipped']} übersprungen.");
        } catch (\Exception $e) {
            $this->error('vSphere-Sync fehlgeschlagen: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
