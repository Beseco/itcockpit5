<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Modules\Network\Models\Vlan;
use App\Modules\Network\Models\IpAddress;

class ImportOldNetworkData extends Command
{
    protected $signature = 'network:import-old-data';
    protected $description = 'Import VLAN and IP data from old ticketsystem_db1 database';

    public function handle()
    {
        $this->info('Starting import of old network data...');

        // Import VLANs
        $this->info('Importing VLANs...');
        $vlanCount = $this->importVlans();
        $this->info("Imported {$vlanCount} VLANs");

        // Import IP Addresses
        $this->info('Importing IP Addresses...');
        $ipCount = $this->importIpAddresses();
        $this->info("Imported {$ipCount} IP Addresses");

        $this->info('Import completed successfully!');
        
        return 0;
    }

    private function importVlans(): int
    {
        $oldVlans = $this->getOldVlansData();
        $count = 0;

        foreach ($oldVlans as $oldVlan) {
            // Check if VLAN already exists
            $exists = Vlan::where('vlan_id', $oldVlan['vlan_id'])->exists();
            
            if ($exists) {
                $this->warn("VLAN {$oldVlan['vlan_id']} already exists, skipping...");
                continue;
            }

            Vlan::create([
                'vlan_id' => $oldVlan['vlan_id'],
                'vlan_name' => $oldVlan['vlan_name'],
                'network_address' => $oldVlan['network_address'],
                'cidr_suffix' => $oldVlan['cidr_suffix'],
                'gateway' => $oldVlan['gateway'] ?: null,
                'dhcp_from' => $oldVlan['dhcp_from'],
                'dhcp_to' => $oldVlan['dhcp_to'],
                'description' => $oldVlan['description'],
                'internes_netz' => (bool) $oldVlan['internes_netz'],
                'ipscan' => (bool) $oldVlan['ipscan'],
            ]);

            $count++;
        }

        return $count;
    }

    private function importIpAddresses(): int
    {
        $oldIps = $this->getOldIpAddressesData();
        $count = 0;

        foreach ($oldIps as $oldIp) {
            // Map old vlan_liste_id to new vlan id
            $vlan = Vlan::where('id', $oldIp['vlan_liste_id'])->first();
            
            if (!$vlan) {
                $this->warn("VLAN with old ID {$oldIp['vlan_liste_id']} not found, skipping IP {$oldIp['ip_address']}");
                continue;
            }

            // Check if IP already exists
            $exists = IpAddress::where('vlan_id', $vlan->id)
                ->where('ip_address', $oldIp['ip_address'])
                ->exists();
            
            if ($exists) {
                continue;
            }

            IpAddress::create([
                'vlan_id' => $vlan->id,
                'ip_address' => $oldIp['ip_address'],
                'dns_name' => $oldIp['dns_name'],
                'mac_address' => $oldIp['mac_address'],
                'is_online' => (bool) $oldIp['is_online'],
                'last_online_at' => $oldIp['lastonline'],
                'last_scanned_at' => $oldIp['lasttest'],
                'ping_ms' => $oldIp['ping_response_time_ms'],
                'comment' => $oldIp['kommentar'],
            ]);

            $count++;
        }

        return $count;
    }

    private function getOldVlansData(): array
    {
        return [
            ['id' => 1, 'vlan_id' => 130, 'vlan_name' => 'LRA-130-AppSrv', 'network_address' => '10.22.3.0', 'cidr_suffix' => 24, 'gateway' => '10.22.3.254', 'dhcp_from' => '10.22.3.10', 'dhcp_to' => '10.22.3.250', 'description' => 'Application Server', 'internes_netz' => 1, 'ipscan' => 1],
            ['id' => 2, 'vlan_id' => 140, 'vlan_name' => 'LRA-140-TeamsSIP', 'network_address' => '10.22.4.0', 'cidr_suffix' => 23, 'gateway' => '10.22.5.254', 'dhcp_from' => '10.22.4.11', 'dhcp_to' => '10.22.5.253', 'description' => 'Teams SIP-Telefone', 'internes_netz' => 1, 'ipscan' => 1],
            ['id' => 3, 'vlan_id' => 131, 'vlan_name' => 'LRA-131-Drucker', 'network_address' => '10.22.6.0', 'cidr_suffix' => 23, 'gateway' => '10.22.7.254', 'dhcp_from' => '10.22.6.11', 'dhcp_to' => '10.22.7.249', 'description' => 'Drucker/Scanner', 'internes_netz' => 1, 'ipscan' => 1],
            ['id' => 4, 'vlan_id' => 114, 'vlan_name' => 'LRA-114-SG14', 'network_address' => '10.22.8.0', 'cidr_suffix' => 26, 'gateway' => '10.22.8.62', 'dhcp_from' => '10.22.8.1', 'dhcp_to' => '10.22.8.50', 'description' => 'SG14', 'internes_netz' => 1, 'ipscan' => 1],
            ['id' => 5, 'vlan_id' => 136, 'vlan_name' => 'LRA-136-FinanceSrv', 'network_address' => '10.22.8.64', 'cidr_suffix' => 26, 'gateway' => '10.22.8.126', 'dhcp_from' => null, 'dhcp_to' => null, 'description' => 'Finance Server', 'internes_netz' => 1, 'ipscan' => 1],
            ['id' => 6, 'vlan_id' => 137, 'vlan_name' => 'LRA-137-MeetingBoards', 'network_address' => '10.22.8.128', 'cidr_suffix' => 25, 'gateway' => '10.22.8.254', 'dhcp_from' => '10.22.8.129', 'dhcp_to' => '10.22.8.189', 'description' => 'Besprechungsraumbildschirme', 'internes_netz' => 1, 'ipscan' => 1],
            ['id' => 7, 'vlan_id' => 200, 'vlan_name' => 'S-V-Server', 'network_address' => '10.22.11.0', 'cidr_suffix' => 24, 'gateway' => '10.22.11.254', 'dhcp_from' => null, 'dhcp_to' => null, 'description' => 'Karl-Meichelbeck-Realschule / Schulen Verwaltung Server', 'internes_netz' => 1, 'ipscan' => 1],
            ['id' => 8, 'vlan_id' => 230, 'vlan_name' => 'DOM-V', 'network_address' => '10.22.12.0', 'cidr_suffix' => 24, 'gateway' => '10.22.12.254', 'dhcp_from' => '10.22.12.100', 'dhcp_to' => '10.22.12.200', 'description' => 'Dom Gymnasium Freising / Dom-Verwaltung', 'internes_netz' => 1, 'ipscan' => 1],
            ['id' => 9, 'vlan_id' => 220, 'vlan_name' => 'S-V-JoHo', 'network_address' => '10.22.13.0', 'cidr_suffix' => 24, 'gateway' => '10.22.13.254', 'dhcp_from' => null, 'dhcp_to' => null, 'description' => '??? Realschule Au/Hallertau ???', 'internes_netz' => 1, 'ipscan' => 1],
            ['id' => 10, 'vlan_id' => 320, 'vlan_name' => 'JOHO - V', 'network_address' => '10.22.15.0', 'cidr_suffix' => 24, 'gateway' => '10.22.15.254', 'dhcp_from' => '10.22.15.21', 'dhcp_to' => '10.22.15.251', 'description' => 'Josef Hofmiller Gymnasium', 'internes_netz' => 1, 'ipscan' => 1],
            // Add remaining VLANs from the SQL dump...
        ];
    }

    private function getOldIpAddressesData(): array
    {
        // Sample IP addresses - in production, this would read from the SQL file or database
        return [
            ['vlan_liste_id' => 1, 'dns_name' => null, 'ip_address' => '10.22.3.1', 'mac_address' => null, 'is_online' => 0, 'lastonline' => null, 'lasttest' => '2026-02-20 10:15:09', 'ping_response_time_ms' => null, 'kommentar' => null],
            ['vlan_liste_id' => 1, 'dns_name' => null, 'ip_address' => '10.22.3.2', 'mac_address' => null, 'is_online' => 0, 'lastonline' => null, 'lasttest' => '2026-02-20 10:16:13', 'ping_response_time_ms' => null, 'kommentar' => null],
            ['vlan_liste_id' => 1, 'dns_name' => 'Tiefbau-01.lra.lan', 'ip_address' => '10.22.3.11', 'mac_address' => null, 'is_online' => 1, 'lastonline' => '2026-02-20 10:22:06', 'lasttest' => '2026-02-20 10:22:06', 'ping_response_time_ms' => 1, 'kommentar' => null],
            ['vlan_liste_id' => 1, 'dns_name' => 'Datango-01.lra.lan', 'ip_address' => '10.22.3.12', 'mac_address' => null, 'is_online' => 1, 'lastonline' => '2026-02-20 10:22:13', 'lasttest' => '2026-02-20 10:22:13', 'ping_response_time_ms' => 1, 'kommentar' => null],
            // Add more IPs as needed...
        ];
    }
}
