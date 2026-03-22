<?php

namespace App\Services\Network;

use App\Modules\Network\Models\Vlan;
use Illuminate\Support\Facades\Log;

class VlanImporter
{
    /**
     * Import a single VLAN record
     *
     * @param array $vlanData VLAN data from old system
     * @return ImportResult
     */
    public function import(array $vlanData): ImportResult
    {
        try {
            // Extract vlan_id and network_address from the data
            $vlanId = isset($vlanData['vlan_id']) ? (int) $vlanData['vlan_id'] : null;
            $networkAddress = $vlanData['network_address'] ?? null;

            if ($vlanId === null) {
                Log::warning("VLAN import failed: Missing vlan_id", ['vlan_data' => $vlanData]);
                return ImportResult::failure('Missing vlan_id');
            }

            // Check if VLAN already exists (duplicate check)
            // For VLAN-ID 999, check both vlan_id and network_address
            if ($this->exists($vlanId, $networkAddress)) {
                Log::debug("Skipping duplicate VLAN", [
                    'vlan_id' => $vlanId,
                    'network_address' => $networkAddress
                ]);
                return ImportResult::duplicate();
            }

            // Transform old data to new structure
            $transformedData = $this->transform($vlanData);

            // Create new VLAN record
            $vlan = Vlan::create($transformedData);

            Log::info("Successfully imported VLAN", [
                'vlan_id' => $vlanId,
                'vlan_name' => $transformedData['vlan_name'],
                'network_address' => $transformedData['network_address'],
                'record_id' => $vlan->id
            ]);

            return ImportResult::success($vlan->id);

        } catch (\Illuminate\Database\QueryException $e) {
            // Database-specific error
            $errorMessage = "Database error while importing VLAN: " . $e->getMessage();
            Log::error($errorMessage, [
                'vlan_data' => $vlanData,
                'sql_error_code' => $e->getCode(),
                'exception' => $e
            ]);
            return ImportResult::failure($errorMessage);
            
        } catch (\Exception $e) {
            // General error
            $errorMessage = "Failed to import VLAN: " . $e->getMessage();
            Log::error($errorMessage, [
                'vlan_data' => $vlanData,
                'exception' => $e
            ]);
            return ImportResult::failure($errorMessage);
        }
    }

    /**
     * Check if VLAN already exists
     * 
     * For VLAN-ID 999 (placeholder for unassigned networks), check both vlan_id and network_address
     * to allow multiple 999 VLANs with different network addresses.
     * For other VLAN-IDs, only check vlan_id.
     *
     * @param int $vlanId VLAN ID to check
     * @param string|null $networkAddress Network address (required for VLAN-ID 999)
     * @return bool
     */
    private function exists(int $vlanId, ?string $networkAddress = null): bool
    {
        // For VLAN-ID 999, check both vlan_id and network_address
        if ($vlanId === 999 && $networkAddress !== null) {
            return Vlan::where('vlan_id', $vlanId)
                ->where('network_address', $networkAddress)
                ->exists();
        }
        
        // For other VLAN-IDs, only check vlan_id
        return Vlan::where('vlan_id', $vlanId)->exists();
    }

    /**
     * Transform old VLAN data to new structure
     *
     * @param array $oldData Old VLAN data
     * @return array New VLAN data
     */
    private function transform(array $oldData): array
    {
        return [
            'vlan_id' => (int) $oldData['vlan_id'],
            'vlan_name' => $oldData['vlan_name'] ?? '',
            'network_address' => $oldData['network_address'] ?? '',
            'cidr_suffix' => isset($oldData['cidr_suffix']) ? (int) $oldData['cidr_suffix'] : 24,
            'gateway' => $this->normalizeValue($oldData['gateway'] ?? null),
            'dhcp_from' => $this->normalizeValue($oldData['dhcp_from'] ?? null),
            'dhcp_to' => $this->normalizeValue($oldData['dhcp_to'] ?? null),
            'description' => $oldData['description'] ?? '',
            'internes_netz' => isset($oldData['internes_netz']) ? (bool) $oldData['internes_netz'] : false,
            'ipscan' => isset($oldData['ipscan']) ? (bool) $oldData['ipscan'] : false,
            'scan_interval_minutes' => 60,  // Default value as per requirements
            'last_scanned_at' => null,      // Default value as per requirements
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
}
