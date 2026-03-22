<?php

namespace App\Console\Commands;

use App\Modules\Network\Models\IpAddress;
use App\Modules\Network\Models\Vlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyNetworkImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'network:verify-import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify network data import completeness and integrity';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('===========================================');
        $this->info('  Network Import Verification');
        $this->info('===========================================');
        $this->newLine();

        // Run all checks
        $this->checkVlanCount();
        $this->newLine();

        $this->checkIpAddressCount();
        $this->newLine();

        $this->checkVlan999Entries();
        $this->newLine();

        $this->checkOrphanedIpAddresses();
        $this->newLine();

        $this->checkReferentialIntegrity();
        $this->newLine();

        $this->info('===========================================');
        $this->info('  Verification Complete');
        $this->info('===========================================');

        return 0;
    }

    /**
     * Check and display VLAN count.
     */
    private function checkVlanCount(): void
    {
        $count = Vlan::count();

        $this->line('<fg=cyan>VLAN Count:</>');
        $this->line("  Total VLANs: <fg=white;options=bold>{$count}</>");

        if ($count > 0) {
            $this->line('  <fg=green>✓ VLANs found in database</>');
        } else {
            $this->line('  <fg=red>✗ No VLANs found in database</>');
        }
    }

    /**
     * Check and display IP address count.
     */
    private function checkIpAddressCount(): void
    {
        $count = IpAddress::count();

        $this->line('<fg=cyan>IP Address Count:</>');
        $this->line("  Total IP Addresses: <fg=white;options=bold>{$count}</>");

        if ($count > 0) {
            $this->line('  <fg=green>✓ IP addresses found in database</>');
        } else {
            $this->line('  <fg=red>✗ No IP addresses found in database</>');
        }
    }

    /**
     * Check and list all VLAN-ID-999 entries.
     */
    private function checkVlan999Entries(): void
    {
        $vlans = Vlan::where('vlan_id', 999)->get();

        $this->line('<fg=cyan>VLAN-ID 999 Entries:</>');

        if ($vlans->isEmpty()) {
            $this->line('  <fg=yellow>⚠ No VLAN-ID 999 entries found</>');
            return;
        }

        $this->line("  Found <fg=white;options=bold>{$vlans->count()}</> VLAN(s) with ID 999:");
        $this->newLine();

        foreach ($vlans as $vlan) {
            $ipCount = $vlan->ipAddresses()->count();
            $this->line("  • ID: {$vlan->id}");
            $this->line("    Name: <fg=white>{$vlan->vlan_name}</>");
            $this->line("    Network: <fg=white>{$vlan->network_address}/{$vlan->cidr_suffix}</>");
            $this->line("    Description: {$vlan->description}");
            $this->line("    IP Addresses: <fg=white>{$ipCount}</>");
            $this->newLine();
        }

        $this->line('  <fg=green>✓ VLAN-ID 999 entries verified</>');
    }

    /**
     * Find and display IP addresses without valid VLAN.
     */
    private function checkOrphanedIpAddresses(): void
    {
        // Find IPs where the vlan_id doesn't exist in vlans table
        $orphanedIps = IpAddress::whereNotIn('vlan_id', function ($query) {
            $query->select('id')->from('vlans');
        })->get();

        $this->line('<fg=cyan>Orphaned IP Addresses:</>');

        if ($orphanedIps->isEmpty()) {
            $this->line('  <fg=green>✓ No orphaned IP addresses found</>');
            $this->line('  All IP addresses have valid VLAN references');
            return;
        }

        $this->line("  <fg=red>✗ Found {$orphanedIps->count()} orphaned IP address(es):</>");
        $this->newLine();

        foreach ($orphanedIps as $ip) {
            $this->line("  • IP: <fg=white>{$ip->ip_address}</>");
            $this->line("    Invalid VLAN ID: <fg=red>{$ip->vlan_id}</>");
            if ($ip->dns_name) {
                $this->line("    DNS Name: {$ip->dns_name}");
            }
            $this->newLine();
        }

        $this->line('  <fg=red>⚠ Action required: Fix or remove orphaned IP addresses</>');
    }

    /**
     * Check referential integrity of foreign key constraints.
     */
    private function checkReferentialIntegrity(): void
    {
        $this->line('<fg=cyan>Referential Integrity Check:</>');

        // Check 1: All IPs have valid VLAN references
        $totalIps = IpAddress::count();
        $validIps = IpAddress::whereIn('vlan_id', function ($query) {
            $query->select('id')->from('vlans');
        })->count();

        $this->line("  IP Address → VLAN References:");
        $this->line("    Total IP Addresses: <fg=white>{$totalIps}</>");
        $this->line("    Valid References: <fg=white>{$validIps}</>");

        if ($totalIps === $validIps) {
            $this->line('    <fg=green>✓ All IP addresses have valid VLAN references</>');
        } else {
            $invalidCount = $totalIps - $validIps;
            $this->line("    <fg=red>✗ {$invalidCount} IP address(es) with invalid VLAN references</>");
        }

        $this->newLine();

        // Check 2: VLAN uniqueness
        $totalVlans = Vlan::count();
        $uniqueVlanIds = Vlan::distinct('vlan_id')->count();

        $this->line("  VLAN ID Uniqueness:");
        $this->line("    Total VLANs: <fg=white>{$totalVlans}</>");
        $this->line("    Unique VLAN IDs: <fg=white>{$uniqueVlanIds}</>");

        if ($totalVlans === $uniqueVlanIds) {
            $this->line('    <fg=green>✓ All VLAN IDs are unique</>');
        } else {
            $duplicateCount = $totalVlans - $uniqueVlanIds;
            $this->line("    <fg=red>✗ {$duplicateCount} duplicate VLAN ID(s) found</>");
        }

        $this->newLine();

        // Check 3: IP address uniqueness within VLANs
        $totalIpRecords = IpAddress::count();
        $uniqueIpCombinations = DB::table('ip_addresses')
            ->select('vlan_id', 'ip_address')
            ->distinct()
            ->count();

        $this->line("  IP Address Uniqueness (per VLAN):");
        $this->line("    Total IP Records: <fg=white>{$totalIpRecords}</>");
        $this->line("    Unique (VLAN + IP) Combinations: <fg=white>{$uniqueIpCombinations}</>");

        if ($totalIpRecords === $uniqueIpCombinations) {
            $this->line('    <fg=green>✓ All IP addresses are unique within their VLANs</>');
        } else {
            $duplicateCount = $totalIpRecords - $uniqueIpCombinations;
            $this->line("    <fg=red>✗ {$duplicateCount} duplicate IP address(es) found</>");
        }

        $this->newLine();

        // Overall integrity status
        if ($totalIps === $validIps && $totalVlans === $uniqueVlanIds && $totalIpRecords === $uniqueIpCombinations) {
            $this->line('  <fg=green;options=bold>✓ All referential integrity checks passed</>');
        } else {
            $this->line('  <fg=red;options=bold>✗ Referential integrity issues detected</>');
        }
    }
}
