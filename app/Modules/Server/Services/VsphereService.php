<?php

namespace App\Modules\Server\Services;

use App\Modules\Server\Models\Server;
use App\Modules\Server\Models\VsphereSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VsphereService
{
    private ?string $sessionToken = null;

    public function __construct(private readonly VsphereSettings $settings) {}

    public static function make(): self
    {
        return new self(VsphereSettings::getSingleton());
    }

    public function isConfigured(): bool
    {
        return $this->settings->isConfigured();
    }

    private function baseUrl(): string
    {
        return rtrim($this->settings->vcenter_url, '/') . '/api';
    }

    private function authenticate(): void
    {
        $response = Http::withBasicAuth($this->settings->username, $this->settings->password)
            ->withOptions(['verify' => $this->settings->verify_ssl])
            ->post($this->baseUrl() . '/session');

        if (!$response->successful()) {
            throw new \RuntimeException('vSphere-Authentifizierung fehlgeschlagen (HTTP ' . $response->status() . '). Zugangsdaten prüfen.');
        }

        // Response is a plain JSON string (the session token)
        $this->sessionToken = trim($response->body(), '"');
    }

    private function get(string $path): mixed
    {
        if (!$this->sessionToken) {
            $this->authenticate();
        }

        $response = Http::withHeaders(['vmware-api-session-id' => $this->sessionToken])
            ->withOptions(['verify' => $this->settings->verify_ssl])
            ->get($this->baseUrl() . $path);

        if (!$response->successful()) {
            throw new \RuntimeException("vSphere API [{$path}] HTTP " . $response->status());
        }

        return $response->json();
    }

    public function testConnection(): array
    {
        try {
            $this->authenticate();
            $this->get('/vcenter/vm?max_results=1');
            return ['ok' => true, 'message' => 'Verbindung erfolgreich. vCenter ist erreichbar.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function sync(): array
    {
        $this->authenticate();

        $vms   = $this->get('/vcenter/vm') ?? [];
        $stats = ['updated' => 0, 'created' => 0, 'skipped' => 0];

        foreach ($vms as $vm) {
            try {
                $this->syncVm($vm, $stats);
            } catch (\Throwable $e) {
                Log::warning('vSphere sync VM ' . ($vm['name'] ?? $vm['vm'] ?? '?') . ': ' . $e->getMessage());
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    private function syncVm(array $vm, array &$stats): void
    {
        $vmId    = $vm['vm'];
        $details = $this->get("/vcenter/vm/{$vmId}");

        // Guest identity requires VMware Tools — may not be available
        $guest = null;
        try {
            $guest = $this->get("/vcenter/vm/{$vmId}/guest/identity");
        } catch (\Throwable) {}

        $cpuCount  = $details['cpu']['count'] ?? null;
        $memoryMb  = $details['memory']['size_MiB'] ?? null;
        $diskData  = $this->parseDiskData($details['disks'] ?? []);

        $status = match ($vm['power_state'] ?? '') {
            'POWERED_ON' => 'produktiv',
            default      => 'ausgeschaltet',
        };

        // Find existing server: by vsphere ID → by name → by hostname
        $server = Server::where('vsphere_vm_id', $vmId)->first()
            ?? Server::where('name', $vm['name'])->first()
            ?? (isset($guest['host_name']) && $guest['host_name']
                ? Server::where('dns_hostname', $guest['host_name'])->first()
                : null);

        $syncData = [
            'vsphere_vm_id'     => $vmId,
            'vsphere_synced'    => true,
            'vsphere_synced_at' => now(),
            'cpu_count'         => $cpuCount,
            'memory_mb'         => $memoryMb,
            'disk_gb'           => $diskData['total_gb'],
            'vsphere_datastore' => $diskData['datastore'],
            'status'            => $status,
            'type'              => 'vm',
        ];

        if ($server) {
            // Only fill empty fields — never overwrite manually maintained data
            if ($guest) {
                if (empty($server->dns_hostname) && !empty($guest['host_name'])) {
                    $syncData['dns_hostname'] = $guest['host_name'];
                }
                if (empty($server->ip_address) && !empty($guest['ip_address'])) {
                    $syncData['ip_address'] = $guest['ip_address'];
                }
                if (empty($server->operating_system) && !empty($guest['full_name']['default_message'])) {
                    $syncData['operating_system'] = $guest['full_name']['default_message'];
                }
            }
            $server->update($syncData);
            $stats['updated']++;
        } else {
            $syncData['name'] = $vm['name'];
            if ($guest) {
                $syncData['dns_hostname']     = $guest['host_name'] ?? null;
                $syncData['ip_address']       = $guest['ip_address'] ?? null;
                $syncData['operating_system'] = $guest['full_name']['default_message'] ?? null;
            }
            Server::create($syncData);
            $stats['created']++;
        }
    }

    private function parseDiskData(array $disks): array
    {
        $totalBytes = 0;
        $datastore  = null;

        foreach ($disks as $disk) {
            $totalBytes += $disk['capacity'] ?? 0;

            if (!$datastore && isset($disk['backing']['vmdk_file'])) {
                // Extract from "[DatastoreName] folder/disk.vmdk"
                if (preg_match('/^\[([^\]]+)\]/', $disk['backing']['vmdk_file'], $m)) {
                    $datastore = $m[1];
                }
            }
        }

        return [
            'total_gb'  => $totalBytes > 0 ? (int) round($totalBytes / (1024 ** 3)) : null,
            'datastore' => $datastore,
        ];
    }
}
