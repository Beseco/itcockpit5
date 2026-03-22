<?php

namespace App\Modules\Network\Services;

use App\Modules\Network\Models\IpAddress;
use App\Modules\Network\Models\Vlan;
use Illuminate\Support\Facades\Log;

class IpGeneratorService
{
    /**
     * Generate IP addresses for a VLAN.
     *
     * This method calculates all valid host IP addresses within the subnet
     * defined by the VLAN's network_address and cidr_suffix, then creates
     * IpAddress records for each host address.
     *
     * @param Vlan $vlan The VLAN to generate IP addresses for
     * @return int The count of generated IP addresses
     */
    public function generateIpAddresses(Vlan $vlan): int
    {
        try {
            // Convert network address to integer
            $networkLong = ip2long($vlan->network_address);
            
            if ($networkLong === false) {
                Log::error('Invalid network address format', [
                    'vlan_id' => $vlan->id,
                    'network_address' => $vlan->network_address,
                ]);
                return 0;
            }

            // Calculate netmask from CIDR suffix
            // Formula: (0xFFFFFFFF << (32 - cidr_suffix)) & 0xFFFFFFFF
            $netmask = (0xFFFFFFFF << (32 - $vlan->cidr_suffix)) & 0xFFFFFFFF;

            // Calculate network address (base address of subnet)
            $network = $networkLong & $netmask;

            // Calculate broadcast address
            // Formula: network | (~netmask & 0xFFFFFFFF)
            $broadcast = $network | (~$netmask & 0xFFFFFFFF);

            // Calculate first and last host addresses
            // For /31 and /32 subnets, we need special handling
            if ($vlan->cidr_suffix == 32) {
                // /32 subnet: only one address (the network address itself)
                $firstHost = $network;
                $lastHost = $network;
            } elseif ($vlan->cidr_suffix == 31) {
                // /31 subnet: point-to-point link, both addresses are usable
                $firstHost = $network;
                $lastHost = $network + 1;
            } else {
                // Standard subnets: exclude network and broadcast addresses
                $firstHost = $network + 1;
                $lastHost = $broadcast - 1;
            }

            // Generate IP address records
            $count = 0;
            for ($ipLong = $firstHost; $ipLong <= $lastHost; $ipLong++) {
                $ipString = long2ip($ipLong);
                
                if ($ipString === false) {
                    Log::warning('Failed to convert IP address', [
                        'vlan_id' => $vlan->id,
                        'ip_long' => $ipLong,
                    ]);
                    continue;
                }

                IpAddress::create([
                    'vlan_id' => $vlan->id,
                    'ip_address' => $ipString,
                ]);

                $count++;
            }

            Log::info('IP addresses generated successfully', [
                'vlan_id' => $vlan->id,
                'vlan_name' => $vlan->vlan_name,
                'subnet' => $vlan->subnet,
                'count' => $count,
            ]);

            return $count;

        } catch (\Exception $e) {
            Log::error('IP address generation failed', [
                'vlan_id' => $vlan->id,
                'vlan_name' => $vlan->vlan_name,
                'network_address' => $vlan->network_address,
                'cidr_suffix' => $vlan->cidr_suffix,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Calculate subnet information for a given network address and CIDR suffix.
     *
     * This helper method returns detailed subnet information including
     * network, broadcast, first host, last host addresses, and host count.
     *
     * @param string $networkAddress The network address in dotted decimal notation
     * @param int $cidrSuffix The CIDR suffix (0-32)
     * @return array Array containing subnet information
     */
    public function calculateSubnetInfo(string $networkAddress, int $cidrSuffix): array
    {
        $networkLong = ip2long($networkAddress);
        
        if ($networkLong === false) {
            return [
                'network' => null,
                'broadcast' => null,
                'first_host' => null,
                'last_host' => null,
                'host_count' => 0,
            ];
        }

        // Calculate netmask
        $netmask = (0xFFFFFFFF << (32 - $cidrSuffix)) & 0xFFFFFFFF;

        // Calculate network and broadcast addresses
        $network = $networkLong & $netmask;
        $broadcast = $network | (~$netmask & 0xFFFFFFFF);

        // Calculate first and last host addresses based on CIDR suffix
        if ($cidrSuffix == 32) {
            $firstHost = $network;
            $lastHost = $network;
            $hostCount = 1;
        } elseif ($cidrSuffix == 31) {
            $firstHost = $network;
            $lastHost = $network + 1;
            $hostCount = 2;
        } else {
            $firstHost = $network + 1;
            $lastHost = $broadcast - 1;
            $hostCount = ($lastHost - $firstHost) + 1;
        }

        return [
            'network' => long2ip($network),
            'broadcast' => long2ip($broadcast),
            'first_host' => long2ip($firstHost),
            'last_host' => long2ip($lastHost),
            'host_count' => $hostCount,
        ];
    }
}
