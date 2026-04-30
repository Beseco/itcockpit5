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

    /**
     * Gibt alle Ordner aus CheckMK zurück.
     * Rückgabe: [['path' => '~', 'title' => 'Main', 'label' => 'Main (/)'], ...]
     * CheckMK nutzt "~" als Wurzel-Prefix: "~" = root "/", "~linux" = "/linux"
     */
    public function getFolders(): array
    {
        try {
            $response = $this->get('/domain-types/folder_config/collections/all', [
                'recursive' => 'true',
            ]);
            return collect($response['value'] ?? [])
                ->map(function ($f) {
                    $cmkPath = $f['id'] ?? '~';
                    $title   = $f['extensions']['title'] ?? $cmkPath;
                    // Build a readable path: replace leading ~ with / and ~ between segments with /
                    $display = $cmkPath === '~' ? '/' : '/' . ltrim(str_replace('~', '/', $cmkPath), '/');
                    return [
                        'path'  => $cmkPath,   // original CheckMK path used for API filter
                        'title' => $title,
                        'label' => $title . ' (' . $display . ')',
                    ];
                })
                ->sortBy('path')
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            Log::warning('CheckMK getFolders: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Gibt alle Hosts aus CheckMK zurück, optional gefiltert nach Ordner-Pfaden.
     * Rückgabe: [['name' => ..., 'alias' => ..., 'address' => ..., 'folder' => ..., 'tags' => [...]], ...]
     *
     * @param string[] $folderPaths Leeres Array = alle Ordner
     */
    public function getAllHosts(array $folderPaths = []): array
    {
        try {
            $folderPaths = array_filter(array_map('trim', $folderPaths));

            if (count($folderPaths) === 1) {
                $query = json_encode(['op' => '=', 'left' => 'folder', 'right' => reset($folderPaths)]);
            } elseif (count($folderPaths) > 1) {
                $query = json_encode([
                    'op'   => 'or',
                    'expr' => array_values(array_map(
                        fn($p) => ['op' => '=', 'left' => 'folder', 'right' => $p],
                        $folderPaths
                    )),
                ]);
            } else {
                $query = null;
            }

            $params = ['columns' => ['name', 'alias', 'address', 'folder', 'tags']];
            if ($query) {
                $params['query'] = $query;
            }

            $response = $this->get('/domain-types/host/collections/all', $params);
            return collect($response['value'] ?? [])
                ->map(fn($h) => [
                    'name'    => $h['id'] ?? '',
                    'alias'   => $h['extensions']['alias'] ?? '',
                    'address' => $h['extensions']['address'] ?? '',
                    'folder'  => $h['extensions']['folder'] ?? '/',
                    'tags'    => $h['extensions']['tags'] ?? [],
                ])
                ->filter(fn($h) => !empty($h['name']))
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            Log::warning('CheckMK getAllHosts: ' . $e->getMessage());
            return [];
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
