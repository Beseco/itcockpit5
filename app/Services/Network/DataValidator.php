<?php

namespace App\Services\Network;

class DataValidator
{
    /**
     * Validate VLAN record
     * 
     * @param array $vlanData VLAN data array
     * @return ValidationResult
     */
    public function validateVlan(array $vlanData): ValidationResult
    {
        $errors = [];
        
        // Check for required field: vlan_id
        if (!isset($vlanData['vlan_id']) || $vlanData['vlan_id'] === null || $vlanData['vlan_id'] === '') {
            $errors[] = 'Missing required field: vlan_id';
        } else {
            // Validate VLAN ID (including 999)
            if (!$this->isValidVlanId($vlanData['vlan_id'])) {
                $errors[] = 'Invalid VLAN ID: must be between 1 and 4094';
            }
        }
        
        // Check for required field: network_address
        if (!isset($vlanData['network_address']) || $vlanData['network_address'] === null || $vlanData['network_address'] === '') {
            $errors[] = 'Missing required field: network_address';
        } else {
            // Validate network address format
            if (!$this->isValidNetworkAddress($vlanData['network_address'])) {
                $errors[] = 'Invalid network address format';
            }
        }
        
        // Optional: Validate CIDR suffix if present
        if (isset($vlanData['cidr_suffix']) && $vlanData['cidr_suffix'] !== null) {
            $cidr = (int) $vlanData['cidr_suffix'];
            if ($cidr < 0 || $cidr > 32) {
                $errors[] = 'Invalid CIDR suffix: must be between 0 and 32';
            }
        }
        
        // Optional: Validate gateway if present
        if (isset($vlanData['gateway']) && $vlanData['gateway'] !== null && $vlanData['gateway'] !== '') {
            if (!$this->isValidIpAddress($vlanData['gateway'])) {
                $errors[] = 'Invalid gateway IP address format';
            }
        }
        
        // Optional: Validate DHCP range if present
        if (isset($vlanData['dhcp_from']) && $vlanData['dhcp_from'] !== null && $vlanData['dhcp_from'] !== '') {
            if (!$this->isValidIpAddress($vlanData['dhcp_from'])) {
                $errors[] = 'Invalid dhcp_from IP address format';
            }
        }
        
        if (isset($vlanData['dhcp_to']) && $vlanData['dhcp_to'] !== null && $vlanData['dhcp_to'] !== '') {
            if (!$this->isValidIpAddress($vlanData['dhcp_to'])) {
                $errors[] = 'Invalid dhcp_to IP address format';
            }
        }
        
        if (empty($errors)) {
            return ValidationResult::success();
        }
        
        return ValidationResult::failure($errors);
    }
    
    /**
     * Validate IP address record
     * 
     * @param array $ipData IP address data array
     * @return ValidationResult
     */
    public function validateIpAddress(array $ipData): ValidationResult
    {
        $errors = [];
        
        // Check for required field: ip_address
        if (!isset($ipData['ip_address']) || $ipData['ip_address'] === null || $ipData['ip_address'] === '') {
            $errors[] = 'Missing required field: ip_address';
        } else {
            // Validate IP address format
            if (!$this->isValidIpAddress($ipData['ip_address'])) {
                $errors[] = 'Invalid IP address format';
            }
        }
        
        // Check for required field: vlan_liste_id (old FK reference)
        if (!isset($ipData['vlan_liste_id']) || $ipData['vlan_liste_id'] === null || $ipData['vlan_liste_id'] === '') {
            $errors[] = 'Missing required field: vlan_liste_id';
        }
        
        // Optional: Validate MAC address if present
        if (isset($ipData['mac_address']) && $ipData['mac_address'] !== null && $ipData['mac_address'] !== '') {
            if (!$this->isValidMacAddress($ipData['mac_address'])) {
                $errors[] = 'Invalid MAC address format';
            }
        }
        
        // Optional: Validate ping_response_time_ms if present
        if (isset($ipData['ping_response_time_ms']) && $ipData['ping_response_time_ms'] !== null && $ipData['ping_response_time_ms'] !== '') {
            $pingMs = (float) $ipData['ping_response_time_ms'];
            if ($pingMs < 0) {
                $errors[] = 'Invalid ping_response_time_ms: must be non-negative';
            }
        }
        
        if (empty($errors)) {
            return ValidationResult::success();
        }
        
        return ValidationResult::failure($errors);
    }
    
    /**
     * Check if VLAN ID is valid (including 999)
     * 
     * @param mixed $vlanId VLAN ID to validate
     * @return bool
     */
    private function isValidVlanId(mixed $vlanId): bool
    {
        // Convert to integer
        $vlanId = (int) $vlanId;
        
        // Valid VLAN IDs are 1-4094 (including 999)
        // VLAN ID 999 is explicitly allowed as per requirements
        return $vlanId >= 1 && $vlanId <= 4094;
    }
    
    /**
     * Check if IP address format is valid
     * 
     * @param string $ipAddress IP address to validate
     * @return bool
     */
    private function isValidIpAddress(string $ipAddress): bool
    {
        // Validate IPv4 or IPv6 address
        return filter_var($ipAddress, FILTER_VALIDATE_IP) !== false;
    }
    
    /**
     * Check if network address format is valid
     * 
     * @param string $networkAddress Network address to validate
     * @return bool
     */
    private function isValidNetworkAddress(string $networkAddress): bool
    {
        // Network address should be a valid IP address
        return $this->isValidIpAddress($networkAddress);
    }
    
    /**
     * Check if MAC address format is valid
     * 
     * @param string $macAddress MAC address to validate
     * @return bool
     */
    private function isValidMacAddress(string $macAddress): bool
    {
        // Validate MAC address format (various formats supported)
        // Examples: 00:1A:2B:3C:4D:5E, 00-1A-2B-3C-4D-5E, 001A.2B3C.4D5E
        $patterns = [
            '/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',  // 00:1A:2B:3C:4D:5E or 00-1A-2B-3C-4D-5E
            '/^([0-9A-Fa-f]{4}\.){2}([0-9A-Fa-f]{4})$/',    // 001A.2B3C.4D5E
            '/^([0-9A-Fa-f]{12})$/',                         // 001A2B3C4D5E
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $macAddress)) {
                return true;
            }
        }
        
        return false;
    }
}
