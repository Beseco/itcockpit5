<?php

namespace App\Services\Network;

use App\Modules\Network\Models\IpAddress;
use Illuminate\Support\Facades\Log;

class IpAddressImporter
{
    /**
     * Import a single IP address record
     *
     * @param array $ipData IP data from old system
     * @param array $vlanMapping Mapping of old VLAN list IDs to new VLAN IDs
     * @return ImportResult
     */
    public function import(array $ipData, array $vlanMapping): ImportResult
    {
        try {
            // Extract old vlan_liste_id from the data
            $oldVlanListeId = isset($ipData['vlan_liste_id']) ? (int) $ipData['vlan_liste_id'] : null;
            $ipAddress = $ipData['ip_address'] ?? null;

            if ($oldVlanListeId === null) {
                Log::warning("IP import failed: Missing vlan_liste_id", ['ip_data' => $ipData]);
                return ImportResult::failure('Missing vlan_liste_id');
            }

            if ($ipAddress === null || $ipAddress === '') {
                Log::warning("IP import failed: Missing ip_address", ['ip_data' => $ipData]);
                return ImportResult::failure('Missing ip_address');
            }

            // Check if VLAN exists in mapping (orphaned IP check)
            if (!isset($vlanMapping[$oldVlanListeId])) {
                $errorMessage = "VLAN not found for vlan_liste_id: {$oldVlanListeId}";
                Log::warning("Skipping orphaned IP address", [
                    'ip_address' => $ipAddress,
                    'vlan_liste_id' => $oldVlanListeId,
                    'dns_name' => $ipData['dns_name'] ?? null
                ]);
                return ImportResult::failure($errorMessage);
            }

            $newVlanId = $vlanMapping[$oldVlanListeId];

            // Check if IP address already exists (duplicate check based on vlan_id + ip_address)
            if ($this->exists($newVlanId, $ipAddress)) {
                Log::debug("Skipping duplicate IP address", [
                    'vlan_id' => $newVlanId,
                    'ip_address' => $ipAddress
                ]);
                return ImportResult::duplicate();
            }

            // Transform old data to new structure
            $transformedData = $this->transform($ipData, $newVlanId);

            // Create new IP address record
            $ip = IpAddress::create($transformedData);

            Log::info("Successfully imported IP address", [
                'vlan_id' => $newVlanId,
                'ip_address' => $ipAddress,
                'dns_name' => $transformedData['dns_name'],
                'record_id' => $ip->id
            ]);

            return ImportResult::success($ip->id);

        } catch (\Illuminate\Database\QueryException $e) {
            // Database-specific error
            $errorMessage = "Database error while importing IP address: " . $e->getMessage();
            Log::error($errorMessage, [
                'ip_data' => $ipData,
                'sql_error_code' => $e->getCode(),
                'exception' => $e
            ]);
            return ImportResult::failure($errorMessage);
            
        } catch (\Exception $e) {
            // General error
            $errorMessage = "Failed to import IP address: " . $e->getMessage();
            Log::error($errorMessage, [
                'ip_data' => $ipData,
                'exception' => $e
            ]);
            return ImportResult::failure($errorMessage);
        }
    }

    /**
     * Check if IP address already exists
     *
     * @param int $vlanId VLAN ID
     * @param string $ipAddress IP address
     * @return bool
     */
    private function exists(int $vlanId, string $ipAddress): bool
    {
        return IpAddress::where('vlan_id', $vlanId)
            ->where('ip_address', $ipAddress)
            ->exists();
    }

    /**
     * Transform old IP data to new structure
     *
     * @param array $oldData Old IP data
     * @param int $newVlanId New VLAN ID
     * @return array New IP data
     */
    private function transform(array $oldData, int $newVlanId): array
    {
        return [
            'vlan_id' => $newVlanId,  // FK-Mapping from old vlan_liste_id to new vlan_id
            'ip_address' => $oldData['ip_address'],
            'dns_name' => $this->normalizeValue($oldData['dns_name'] ?? null),
            'mac_address' => $this->normalizeValue($oldData['mac_address'] ?? null),
            'is_online' => isset($oldData['is_online']) ? (bool) $oldData['is_online'] : false,
            'last_online_at' => $this->normalizeValue($oldData['lastonline'] ?? null),  // Renamed field
            'last_scanned_at' => $this->normalizeValue($oldData['lasttest'] ?? null),   // Renamed field
            'ping_ms' => $this->normalizePingMs($oldData['ping_response_time_ms'] ?? null),  // Renamed field
            'comment' => $this->normalizeValue($oldData['kommentar'] ?? null),  // Renamed field
        ];
    }

    /**
     * Normalize a value, converting 'NULL' string to actual null
     *
     * @param mixed $value Value to normalize
     * @return mixed Normalized value
     */
    private function normalizeValue(mixed $value): mixed
    {
        if ($value === 'NULL' || $value === '' || $value === null) {
            return null;
        }
        return $value;
    }

    /**
     * Normalize ping_ms value, converting to float or null
     *
     * @param mixed $value Value to normalize
     * @return float|null Normalized value
     */
    private function normalizePingMs(mixed $value): ?float
    {
        if ($value === 'NULL' || $value === '' || $value === null) {
            return null;
        }
        return (float) $value;
    }
}
