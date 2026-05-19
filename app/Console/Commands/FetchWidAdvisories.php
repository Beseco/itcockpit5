<?php

namespace App\Console\Commands;

use App\Modules\Wid\Models\WidSettings;
use App\Modules\Wid\Services\WidService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchWidAdvisories extends Command
{
    protected $signature   = 'wid:fetch-advisories {--debug : Zeigt die rohe API-Antwort} {--no-details : Detail-Abruf überspringen}';
    protected $description = 'WID-Portal Sicherheitswarnungen abrufen und in der Datenbank speichern';

    public function handle(): int
    {
        $settings = WidSettings::getInstance();

        if (!$settings->isConfigured()) {
            $this->warn('WID nicht konfiguriert oder deaktiviert – Abruf übersprungen.');
            return self::SUCCESS;
        }

        if ($this->option('debug')) {
            return $this->runDebug($settings);
        }

        $this->line("URL: {$settings->api_url}/public/securityAdvisory");
        $this->line('Rufe WID-Sicherheitswarnungen ab...');

        $service = new WidService();
        $items   = $service->doFetch();

        if ($items->isEmpty()) {
            $this->warn('Keine Einträge von der WID-API erhalten.');
            return self::SUCCESS;
        }

        $result = $service->syncToDatabase($items);
        $this->info("Liste: {$result['created']} neu, {$result['updated']} aktualisiert ({$result['total']} gesamt).");

        if (!$this->option('no-details')) {
            $this->line('Lade Descriptions nach...');
            $detailCount = $service->fetchMissingDetails();
            $this->info("Details: {$detailCount} Descriptions geladen.");
        }

        return self::SUCCESS;
    }

    private function runDebug(WidSettings $settings): int
    {
        $this->line('=== DEBUG-MODUS ===');
        $this->line("API-Key: " . substr($settings->api_key, 0, 12) . '...');

        $http = Http::withHeaders(['X-Api-Key' => $settings->api_key])
            ->timeout(15)
            ->withoutVerifying();

        $this->line("\n--- Schritt 1: Liste ---");
        try {
            $response = $http->get("{$settings->api_url}/public/securityAdvisory", [
                'sort' => 'published,desc',
                'size' => 2,
                'page' => 0,
            ]);

            $this->line("HTTP-Status: " . $response->status());
            $json = $response->json();

            if (is_array($json)) {
                $this->line("JSON-Keys: " . implode(', ', array_keys($json)));
                if (isset($json['totalElements'])) {
                    $this->line("totalElements: " . $json['totalElements']);
                }
            }

            $firstUuid = $json['content'][0]['uuid'] ?? null;
            $firstName = $json['content'][0]['name'] ?? null;

            if ($firstUuid) {
                $this->line("\n--- Schritt 2: Detail für {$firstName} ({$firstUuid}) ---");

                $detail = $http->get("{$settings->api_url}/public/securityAdvisory/{$firstUuid}");
                $this->line("HTTP-Status: " . $detail->status());
                $this->line("Antwort-Länge: " . strlen($detail->body()) . " Bytes");

                $detailJson = $detail->json();
                if (is_array($detailJson)) {
                    $this->line("JSON-Keys: " . implode(', ', array_keys($detailJson)));
                    $this->line("\nVollständige Detail-Antwort:");
                    $this->line(json_encode($detailJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                } else {
                    $this->line(substr($detail->body(), 0, 2000));
                }
            }
        } catch (\Exception $e) {
            $this->error("Exception: " . $e->getMessage());
        }

        return self::SUCCESS;
    }
}
