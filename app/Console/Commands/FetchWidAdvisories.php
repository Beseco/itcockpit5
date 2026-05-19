<?php

namespace App\Console\Commands;

use App\Modules\Wid\Models\WidSettings;
use App\Modules\Wid\Services\WidService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchWidAdvisories extends Command
{
    protected $signature   = 'wid:fetch-advisories {--debug : Zeigt die rohe API-Antwort}';
    protected $description = 'WID-Portal Sicherheitswarnungen abrufen und in der Datenbank speichern';

    public function handle(): int
    {
        $settings = WidSettings::getInstance();

        if (!$settings->isConfigured()) {
            $this->warn('WID nicht konfiguriert oder deaktiviert – Abruf übersprungen.');
            return self::SUCCESS;
        }

        $this->line("URL: {$settings->api_url}/public/securityAdvisory");

        if ($this->option('debug')) {
            return $this->runDebug($settings);
        }

        $this->line('Rufe WID-Sicherheitswarnungen ab...');

        $service = new WidService();
        $items   = $service->doFetch();

        if ($items->isEmpty()) {
            $this->warn('Keine Einträge von der WID-API erhalten. Tipp: --debug für Details.');
            return self::SUCCESS;
        }

        $result = $service->syncToDatabase($items);

        $this->info("Fertig: {$result['created']} neu, {$result['updated']} aktualisiert ({$result['total']} gesamt).");
        return self::SUCCESS;
    }

    private function runDebug(WidSettings $settings): int
    {
        $this->line('=== DEBUG-MODUS ===');
        $this->line("API-Key: " . substr($settings->api_key, 0, 12) . '...');

        try {
            $response = Http::withHeaders(['X-Api-Key' => $settings->api_key])
                ->timeout(15)
                ->get("{$settings->api_url}/public/securityAdvisory", [
                    'sort' => 'published,desc',
                    'size' => 3,
                    'page' => 0,
                ]);

            $this->line("HTTP-Status: " . $response->status());
            $this->line("Content-Type: " . $response->header('Content-Type'));

            $body = $response->body();
            $this->line("Antwort-Länge: " . strlen($body) . " Bytes");
            $this->line("Antwort (erste 1000 Zeichen):");
            $this->line(substr($body, 0, 1000));

            $json = $response->json();
            if (is_array($json)) {
                $this->line("\nJSON-Keys auf oberster Ebene: " . implode(', ', array_keys($json)));
                if (isset($json['content'])) {
                    $this->line("Anzahl Einträge in 'content': " . count($json['content']));
                }
                if (isset($json['totalElements'])) {
                    $this->line("totalElements: " . $json['totalElements']);
                }
            }
        } catch (\Exception $e) {
            $this->error("Exception: " . $e->getMessage());
        }

        return self::SUCCESS;
    }
}
