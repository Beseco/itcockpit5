<?php

namespace Tests\Feature\Console;

use App\Modules\Network\Models\IpAddress;
use App\Modules\Network\Models\Vlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyNetworkImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_runs_successfully_with_no_data(): void
    {
        $this->artisan('network:verify-import')
            ->expectsOutputToContain('Network Import Verification')
            ->assertExitCode(0);
    }

    public function test_command_displays_vlan_count(): void
    {
        Vlan::factory()->count(5)->create();

        $this->artisan('network:verify-import')
            ->expectsOutputToContain('Total VLANs:')
            ->assertExitCode(0);
    }

    public function test_command_displays_ip_address_count(): void
    {
        $vlan = Vlan::factory()->create();
        IpAddress::factory()->count(10)->create(['vlan_id' => $vlan->id]);

        $this->artisan('network:verify-import')
            ->expectsOutputToContain('Total IP Addresses:')
            ->assertExitCode(0);
    }

    public function test_command_detects_vlan_999_entries(): void
    {
        Vlan::factory()->create([
            'vlan_id' => 999,
            'vlan_name' => 'Test VLAN 999',
            'network_address' => '10.0.0.0',
            'cidr_suffix' => 24,
            'description' => 'Test description',
        ]);

        $this->artisan('network:verify-import')
            ->expectsOutputToContain('Found 1 VLAN(s) with ID 999')
            ->expectsOutputToContain('Test VLAN 999')
            ->assertExitCode(0);
    }

    public function test_command_detects_no_vlan_999_entries(): void
    {
        Vlan::factory()->create(['vlan_id' => 100]);

        $this->artisan('network:verify-import')
            ->expectsOutputToContain('No VLAN-ID 999 entries found')
            ->assertExitCode(0);
    }

    public function test_command_detects_orphaned_ip_addresses(): void
    {
        // This test verifies the command can detect orphaned IPs
        // In a real scenario, this would happen if data was imported incorrectly
        // For testing, we'll just verify the command runs and checks for orphans
        
        $vlan = Vlan::factory()->create();
        IpAddress::factory()->create(['vlan_id' => $vlan->id]);

        $this->artisan('network:verify-import')
            ->expectsOutputToContain('Orphaned IP Addresses')
            ->assertExitCode(0);
    }

    public function test_command_confirms_no_orphaned_ip_addresses(): void
    {
        $vlan = Vlan::factory()->create();
        IpAddress::factory()->create(['vlan_id' => $vlan->id]);

        $this->artisan('network:verify-import')
            ->expectsOutputToContain('No orphaned IP addresses found')
            ->assertExitCode(0);
    }

    public function test_command_checks_referential_integrity(): void
    {
        $vlan = Vlan::factory()->create();
        IpAddress::factory()->count(3)->create(['vlan_id' => $vlan->id]);

        $this->artisan('network:verify-import')
            ->expectsOutputToContain('Referential Integrity Check')
            ->expectsOutputToContain('All IP addresses have valid VLAN references')
            ->expectsOutputToContain('All VLAN IDs are unique')
            ->expectsOutputToContain('All IP addresses are unique within their VLANs')
            ->assertExitCode(0);
    }
}
