<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Import old VLAN and IP data
     */
    public function up(): void
    {
        // Import VLANs from old system
        $this->importVlans();
        
        // Import IP addresses from old system
        $this->importIpAddresses();
    }

    /**
     * Import VLAN data from old database structure
     */
    private function importVlans(): void
    {
        $oldVlans = [
            ['id' => 1, 'vlan_id' => 130, 'vlan_name' => 'LRA-130-AppSrv', 'network_address' => '10.22.3.0', 'cidr_suffix' => 24, 'gateway' => '10.22.3.254', 'dhcp_from' => '10.22.3.10', 'dhcp_to' => '10.22.3.250', 'description' => 'Application Server', 'internes_netz' => 1, 'ipscan' => 1],
            ['id' => 2, 'vlan_id' => 140, 'vlan_name' => 'LRA-140-TeamsSIP', 'network_address' => '10.22.4.0', 'cidr_suffix' => 23, 'gateway' => '10.22.5.254', 'dhcp_from' => '10.22.4.11', 'dhcp_to' => '10.22.5.253', 'description' => 'Teams SIP-Telefone', 'internes_netz' => 1, 'ipscan' => 1],
            ['id' => 3, 'vlan_id' => 131, 'vlan_name' => 'LRA-131-Drucker', 'network_address' => '10.22.6.0', 'cidr_suffix' => 23, 'gateway' => '10.22.7.254', 'dhcp_from' => '10.22.6.11', 'dhcp_to' => '10.22.7.249', 'description' => 'Drucker/Scanner', 'internes_netz' => 1, 'ipscan' => 1],
            ['id' => 4, 'vlan_id' => 114, 'vlan_name' => 'LRA-114-SG14', 'network_address' => '10.22.8.0', 'cidr_suffix' => 26, 'gateway' => '10.22.8.62', 'dhcp_from' => '10.22.8.1', 'dhcp_to' => '10.22.8.50', 'description' => 'SG14', 'internes_netz' => 1, 'ipscan' => 1],
            ['id' => 5, 'vlan_id' => 136, 'vlan_name' => 'LRA-136-FinanceSrv', 'network_address' => '10.22.8.64', 'cidr_suffix' => 26, 'gateway' => '10.22.8.126', 'dhcp_from' => null, 'dhcp_to' => null, 'description' => 'Finance Server', 'internes_netz' => 1, 'ipscan' => 1],
            // Add more VLANs as needed - this is a sample
        ];

        foreach ($oldVlans as $vlan) {
            DB::table('vlans')->insert([
                'vlan_id' => $vlan['vlan_id'],
                'vlan_name' => $vlan['vlan_name'],
                'network_address' => $vlan['network_address'],
                'cidr_suffix' => $vlan['cidr_suffix'],
                'gateway' => $vlan['gateway'],
                'dhcp_from' => $vlan['dhcp_from'],
                'dhcp_to' => $vlan['dhcp_to'],
                'description' => $vlan['description'],
                'internes_netz' => (bool) $vlan['internes_netz'],
                'ipscan' => (bool) $vlan['ipscan'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Import IP address data from old database structure
     */
    private function importIpAddresses(): void
    {
        // This would contain the IP address imports
        // Due to the large dataset, this should be done via a separate command
        // See the ImportOldIpData command for the actual implementation
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally clear imported data
        DB::table('ip_addresses')->truncate();
        DB::table('vlans')->truncate();
    }
};
