<?php

namespace App\Services;

use App\Modules\Server\Models\CheckMkSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckMkService
{
    private CheckMkSettings $cfg;

    public function __construct()
    {
        $this->cfg = CheckMkSettings::getSingleton();
    }

    public function isConfigured(): bool
    {
        return $this->cfg->isConfigured();
    }

    /**
     * Holt alle relevanten Monitoring-Daten für einen Host.
     * Rückgabe: ['state' => int, 'state_label' => string, 'services' => [...]]
     *           oder ['error' => string]
     */
    public function getHostData(string $hostname): array
    {
        $hostname = strtolower(trim($hostname));
        try {
            $hostState = $this->fetchHostState($hostname);
            $services  = $this->fetchServices($hostname);

            return [
                'hostname'    => $hostname,
                'state'       => $hostState['state'] ?? null,
                'state_label' => $this->hostStateLabel($hostState['state'] ?? null),
                'services'    => $services,
            ];
        } catch (\Exception $e) {
            Log::warning("CheckMK Fehler für Host '{$hostname}': " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    // ─── Private ─────────────────────────────────────────────────────────────

    private function fetchHostState(string $hostname): array
    {
        $response = $this->get('/domain-types/host/collections/all', [
            'query'   => json_encode(['op' => '=', 'left' => 'name', 'right' => $hostname]),
            'columns' => ['state', 'plugin_output'],
        ]);

        return $response['value'][0]['extensions'] ?? [];
    }

    private function fetchServices(string $hostname): array
    {
        $response = $this->get('/domain-types/service/collections/all', [
            'host_name' => $hostname,
            'columns'   => ['display_name', 'state', 'plugin_output'],
        ]);

        $keywords = ['CPU', 'Memory', 'Speicher', 'Uptime', 'Filesystem', 'Disk', 'Ping', 'PING'];
        $exclude  = ['DotNet', '.NET'];

        return collect($response['value'] ?? [])
            ->filter(function ($s) use ($keywords, $exclude) {
                $name = $s['extensions']['display_name'] ?? '';
                foreach ($exclude as $ex) {
                    if (stripos($name, $ex) !== false) return false;
                }
                foreach ($keywords as $kw) {
                    if (stripos($name, $kw) !== false) return true;
                }
                return false;
            })
            ->map(fn($s) => [
                'name'          => $s['extensions']['display_name'] ?? '—',
                'state'         => $s['extensions']['state'] ?? null,
                'state_label'   => $this->serviceStateLabel($s['extensions']['state'] ?? null),
                'plugin_output' => $this->stripPerfData($s['extensions']['plugin_output'] ?? ''),
            ])
            ->sortBy('name')
            ->values()
            ->toArray();
    }

    private function get(string $path, array $query = []): array
    {
        $http = Http::withHeaders([
            'Authorization' => "Bearer {$this->cfg->username} {$this->cfg->secret}",
            'Accept'        => 'application/json',
        ])->timeout(10);

        if (! $this->cfg->verify_ssl) {
            $http = $http->withoutVerifying();
        }

        // CheckMK erwartet wiederholte Parameter ohne Brackets:
        // columns=state&columns=plugin_output statt columns[0]=state&columns[1]=plugin_output
        $url = $this->cfg->apiBase() . $path;
        if ($query) {
            $url .= '?' . $this->buildQuery($query);
        }

        $response = $http->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("CheckMK API {$response->status()}: " . $response->body());
        }

        return $response->json();
    }

    /** Baut Query-String mit wiederholten Schlüsseln statt Array-Brackets */
    private function buildQuery(array $params): string
    {
        $parts = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    $parts[] = urlencode((string) $key) . '=' . urlencode((string) $v);
                }
            } else {
                $parts[] = urlencode((string) $key) . '=' . urlencode((string) $value);
            }
        }
        return implode('&', $parts);
    }

    private function hostStateLabel(?int $state): string
    {
        return match ($state) {
            0       => 'Erreichbar (UP)',
            1       => 'Nicht erreichbar (DOWN)',
            2       => 'Unerreichbar (UNREACHABLE)',
            default => 'Unbekannt',
        };
    }

    private function serviceStateLabel(?int $state): string
    {
        return match ($state) {
            0       => 'OK',
            1       => 'WARNING',
            2       => 'CRITICAL',
            3       => 'UNKNOWN',
            default => '—',
        };
    }

    /** Entfernt Perf-Data-Anhang aus CheckMK plugin_output */
    private function stripPerfData(string $output): string
    {
        return trim(explode('|', $output)[0]);
    }
}
