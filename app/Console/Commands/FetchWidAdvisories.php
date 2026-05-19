<?php

namespace App\Console\Commands;

use App\Modules\Wid\Models\WidSettings;
use App\Modules\Wid\Services\WidService;
use Illuminate\Console\Command;

class FetchWidAdvisories extends Command
{
    protected $signature   = 'wid:fetch-advisories';
    protected $description = 'WID-Portal Sicherheitswarnungen abrufen und in der Datenbank speichern';

    public function handle(): int
    {
        $settings = WidSettings::getInstance();

        if (!$settings->isConfigured()) {
            $this->warn('WID nicht konfiguriert oder deaktiviert – Abruf übersprungen.');
            return self::SUCCESS;
        }

        $this->line('Rufe WID-Sicherheitswarnungen ab...');

        $service = new WidService();
        $items   = $service->fetchAdvisories();

        if ($items->isEmpty()) {
            $this->warn('Keine Einträge von der WID-API erhalten.');
            return self::SUCCESS;
        }

        $result = $service->syncToDatabase($items);

        $this->info("Fertig: {$result['created']} neu, {$result['updated']} aktualisiert ({$result['total']} gesamt).");
        return self::SUCCESS;
    }
}
