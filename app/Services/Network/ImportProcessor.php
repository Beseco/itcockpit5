<?php

namespace App\Services\Network;

use App\Modules\Network\Models\Vlan;
use Illuminate\Support\Facades\Log;

class ImportProcessor
{
    private const BATCH_SIZE = 100;

    private VlanImporter $vlanImporter;
    private IpAddressImporter $ipAddressImporter;
    private DataValidator $dataValidator;

    public function __construct(
        VlanImporter $vlanImporter,
        IpAddressImporter $ipAddressImporter,
        DataValidator $dataValidator
    ) {
        $this->vlanImporter = $vlanImporter;
        $this->ipAddressImporter = $ipAddressImporter;
        $this->dataValidator = $dataValidator;
    }

    /**
     * Process VLANs in batches
     *
     * @param array $vlans Array of VLAN records
     * @param callable|null $progressCallback Callback for progress updates (current, total)
     * @return ImportStatistics
     */
    public function processVlans(array $vlans, ?callable $progressCallback = null): ImportStatistics
    {
        $statistics = new ImportStatistics();
        $total = count($vlans);
        $batches = array_chunk($vlans, self::BATCH_SIZE);

        Log::info("Starting VLAN batch processing", [
            'total_vlans' => $total,
            'batch_size' => self::BATCH_SIZE,
            'total_batches' => count($batches)
        ]);

        $processed = 0;

        foreach ($batches as $batchIndex => $batch) {
            foreach ($batch as $vlanData) {
                // Validate VLAN data
                $validationResult = $this->dataValidator->validateVlan($vlanData);

                if (!$validationResult->isValid) {
                    $errorMessage = "VLAN validation failed: " . implode(', ', $validationResult->errors);
                    $statistics->addError($errorMessage);
                    Log::warning($errorMessage, ['vlan_data' => $vlanData]);
                    continue;
                }

                // Import VLAN
                $result = $this->vlanImporter->import($vlanData);

                if ($result->isDuplicate) {
                    $statistics->addDuplicate();
                } elseif ($result->success) {
                    $statistics->addSuccess();
                } else {
                    $statistics->addError($result->errorMessage ?? 'Unknown error');
                }

                $processed++;

                // Call progress callback every 100 records
                if ($progressCallback && $processed % self::BATCH_SIZE === 0) {
                    $progressCallback($processed, $total);
                }
            }

            // Log batch completion
            Log::info("Completed VLAN batch", [
                'batch' => $batchIndex + 1,
                'total_batches' => count($batches),
                'processed' => $processed,
                'total' => $total
            ]);
        }

        // Final progress callback if there are remaining records
        if ($progressCallback && $processed % self::BATCH_SIZE !== 0) {
            $progressCallback($processed, $total);
        }

        Log::info("VLAN batch processing completed", [
            'total_processed' => $statistics->totalProcessed,
            'successful' => $statistics->successfullyImported,
            'duplicates' => $statistics->skippedDuplicates,
            'errors' => $statistics->validationErrors
        ]);

        return $statistics;
    }

    /**
     * Process IP addresses in batches
     *
     * @param array $ipAddresses Array of IP address records
     * @param array $oldVlanData Array of old VLAN data with old vlan_liste.id values
     * @param callable|null $progressCallback Callback for progress updates (current, total)
     * @return ImportStatistics
     */
    public function processIpAddresses(array $ipAddresses, array $oldVlanData = [], ?callable $progressCallback = null): ImportStatistics
    {
        $statistics = new ImportStatistics();
        $total = count($ipAddresses);
        $batches = array_chunk($ipAddresses, self::BATCH_SIZE);

        // Build VLAN mapping cache once before processing
        $vlanMapping = $this->buildVlanMapping($oldVlanData);

        Log::info("Starting IP address batch processing", [
            'total_ips' => $total,
            'batch_size' => self::BATCH_SIZE,
            'total_batches' => count($batches),
            'vlan_mapping_size' => count($vlanMapping)
        ]);

        $processed = 0;

        foreach ($batches as $batchIndex => $batch) {
            foreach ($batch as $ipData) {
                // Validate IP address data
                $validationResult = $this->dataValidator->validateIpAddress($ipData);

                if (!$validationResult->isValid) {
                    $errorMessage = "IP address validation failed: " . implode(', ', $validationResult->errors);
                    $statistics->addError($errorMessage);
                    Log::warning($errorMessage, ['ip_data' => $ipData]);
                    continue;
                }

                // Import IP address
                $result = $this->ipAddressImporter->import($ipData, $vlanMapping);

                if ($result->isDuplicate) {
                    $statistics->addDuplicate();
                } elseif ($result->success) {
                    $statistics->addSuccess();
                } else {
                    $statistics->addError($result->errorMessage ?? 'Unknown error');
                }

                $processed++;

                // Call progress callback every 100 records
                if ($progressCallback && $processed % self::BATCH_SIZE === 0) {
                    $progressCallback($processed, $total);
                }
            }

            // Log batch completion
            Log::info("Completed IP address batch", [
                'batch' => $batchIndex + 1,
                'total_batches' => count($batches),
                'processed' => $processed,
                'total' => $total
            ]);
        }

        // Final progress callback if there are remaining records
        if ($progressCallback && $processed % self::BATCH_SIZE !== 0) {
            $progressCallback($processed, $total);
        }

        Log::info("IP address batch processing completed", [
            'total_processed' => $statistics->totalProcessed,
            'successful' => $statistics->successfullyImported,
            'duplicates' => $statistics->skippedDuplicates,
            'errors' => $statistics->validationErrors
        ]);

        return $statistics;
    }

    /**
     * Build mapping of old VLAN list IDs to new VLAN IDs
     * This caches the mapping to minimize database queries during IP import
     *
     * The mapping works as follows:
     * - Old system: vlan_ip.vlan_liste_id references vlan_liste.id (auto-increment)
     * - New system: ip_addresses.vlan_id references vlans.id (auto-increment)
     * - We need to map: old vlan_liste.id -> new vlans.id
     * 
     * Strategy:
     * 1. If oldVlanData is provided (contains old vlan_liste records with id, vlan_id, and network_address),
     *    we build a mapping: old vlan_liste.id -> (vlan_id + network_address) -> new vlans.id
     * 2. This handles cases where multiple VLANs have the same vlan_id (e.g., 999) but different network addresses
     *
     * @param array $oldVlanData Optional array of old VLAN data with 'id', 'vlan_id', and 'network_address' keys
     * @return array Mapping array [old_vlan_liste_id => new_vlan_id]
     */
    private function buildVlanMapping(array $oldVlanData = []): array
    {
        Log::info("Building VLAN mapping cache");

        // Get all VLANs from database
        $vlans = Vlan::all(['id', 'vlan_id', 'network_address']);
        
        // Create a lookup: (vlan_id + network_address) -> new vlans.id
        // This handles multiple VLANs with the same vlan_id but different network addresses
        $vlanLookup = [];
        foreach ($vlans as $vlan) {
            $key = $vlan->vlan_id . '|' . $vlan->network_address;
            $vlanLookup[$key] = $vlan->id;
        }

        $mapping = [];

        if (!empty($oldVlanData)) {
            // Build mapping using old VLAN data: old vlan_liste.id -> (vlan_id + network_address) -> new vlans.id
            foreach ($oldVlanData as $oldVlan) {
                // $oldVlan is an associative array: ['id' => ..., 'vlan_id' => ..., 'network_address' => ...]
                $oldId = isset($oldVlan['id']) ? (int) $oldVlan['id'] : null;
                $vlanId = isset($oldVlan['vlan_id']) ? (int) $oldVlan['vlan_id'] : null;
                $networkAddress = isset($oldVlan['network_address']) ? $oldVlan['network_address'] : null;

                if ($oldId !== null && $vlanId !== null && $networkAddress !== null) {
                    $key = $vlanId . '|' . $networkAddress;
                    if (isset($vlanLookup[$key])) {
                        $mapping[$oldId] = $vlanLookup[$key];
                    } else {
                        Log::warning("No matching VLAN found for old vlan_liste.id", [
                            'old_id' => $oldId,
                            'vlan_id' => $vlanId,
                            'network_address' => $networkAddress
                        ]);
                    }
                }
            }
        }

        Log::info("VLAN mapping cache built", [
            'mapping_size' => count($mapping),
            'old_vlan_data_size' => count($oldVlanData)
        ]);

        return $mapping;
    }
}
