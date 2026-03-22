<?php

namespace Tests\Feature\Console;

use App\Modules\Network\Models\IpAddress;
use App\Modules\Network\Models\Vlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RollbackNetworkImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_displays_warning_header(): void
    {
        $this->artisan('network:rollback-import --force')
            ->expectsOutputToContain('DANGER ZONE')
            ->expectsOutputToContain('Network Import Rollback')
            ->assertExitCode(0);
    }

    public function test_command_shows_no_data_message_when_database_is_empty(): void
    {
        // Ensure database is truly empty
        IpAddress::query()->delete();
        Vlan::query()->delete();

        $this->artisan('network:rollback-import --force')
            ->expectsOutputToContain('No data to rollback')
            ->assertExitCode(0);
    }

    public function test_command_displays_current_counts_before_deletion(): void
    {
        $vlan = Vlan::factory()->create();
        IpAddress::factory()->count(5)->create(['vlan_id' => $vlan->id]);

        $this->artisan('network:rollback-import --force')
            ->expectsOutputToContain('Current Database Status')
            ->expectsOutputToContain('VLANs:')
            ->expectsOutputToContain('IP Addresses:')
            ->assertExitCode(0);
    }

    public function test_command_deletes_all_vlans_with_force_flag(): void
    {
        Vlan::factory()->count(3)->create();

        $this->assertEquals(3, Vlan::count());

        $this->artisan('network:rollback-import --force')
            ->assertExitCode(0);

        $this->assertEquals(0, Vlan::count());
    }

    public function test_command_deletes_all_ip_addresses_with_force_flag(): void
    {
        $vlan = Vlan::factory()->create();
        IpAddress::factory()->count(10)->create(['vlan_id' => $vlan->id]);

        $this->assertEquals(10, IpAddress::count());

        $this->artisan('network:rollback-import --force')
            ->assertExitCode(0);

        $this->assertEquals(0, IpAddress::count());
    }

    public function test_command_deletes_ip_addresses_before_vlans(): void
    {
        $vlan = Vlan::factory()->create();
        IpAddress::factory()->count(5)->create(['vlan_id' => $vlan->id]);

        $this->artisan('network:rollback-import --force')
            ->expectsOutputToContain('Deleting IP addresses')
            ->expectsOutputToContain('Deleting VLANs')
            ->assertExitCode(0);

        $this->assertEquals(0, IpAddress::count());
        $this->assertEquals(0, Vlan::count());
    }

    public function test_command_displays_deletion_counts(): void
    {
        $vlan = Vlan::factory()->create();
        IpAddress::factory()->count(7)->create(['vlan_id' => $vlan->id]);

        $this->artisan('network:rollback-import --force')
            ->expectsOutputToContain('Deleted 7 IP address(es)')
            ->expectsOutputToContain('Deleted 1 VLAN(s)')
            ->assertExitCode(0);
    }

    public function test_command_displays_success_summary(): void
    {
        $vlan = Vlan::factory()->create();
        IpAddress::factory()->count(3)->create(['vlan_id' => $vlan->id]);

        $this->artisan('network:rollback-import --force')
            ->expectsOutputToContain('Rollback Completed Successfully')
            ->expectsOutputToContain('Deletion Summary')
            ->expectsOutputToContain('VLANs Deleted:')
            ->expectsOutputToContain('IP Addresses Deleted:')
            ->expectsOutputToContain('All network data has been successfully removed')
            ->assertExitCode(0);
    }

    public function test_command_suggests_reimport_after_successful_rollback(): void
    {
        $vlan = Vlan::factory()->create();

        $this->artisan('network:rollback-import --force')
            ->expectsOutputToContain('php artisan network:import-sql')
            ->assertExitCode(0);
    }

    public function test_command_requires_confirmation_without_force_flag(): void
    {
        $vlan = Vlan::factory()->create();

        $this->artisan('network:rollback-import')
            ->expectsQuestion('Are you absolutely sure you want to delete all network data?', false)
            ->expectsOutputToContain('Rollback cancelled by user')
            ->assertExitCode(0);

        // Data should still exist
        $this->assertEquals(1, Vlan::count());
    }

    public function test_command_requires_double_confirmation_without_force_flag(): void
    {
        $vlan = Vlan::factory()->create();

        $this->artisan('network:rollback-import')
            ->expectsQuestion('Are you absolutely sure you want to delete all network data?', true)
            ->expectsQuestion('Type "DELETE ALL" to confirm (case-sensitive)', 'wrong text')
            ->expectsOutputToContain('Confirmation text did not match')
            ->expectsOutputToContain('Rollback cancelled')
            ->assertExitCode(0);

        // Data should still exist
        $this->assertEquals(1, Vlan::count());
    }

    public function test_command_proceeds_with_correct_confirmation(): void
    {
        $vlan = Vlan::factory()->create();

        $this->artisan('network:rollback-import')
            ->expectsQuestion('Are you absolutely sure you want to delete all network data?', true)
            ->expectsQuestion('Type "DELETE ALL" to confirm (case-sensitive)', 'DELETE ALL')
            ->expectsOutputToContain('Starting rollback')
            ->assertExitCode(0);

        // Data should be deleted
        $this->assertEquals(0, Vlan::count());
    }

    public function test_command_handles_large_numbers_with_formatting(): void
    {
        $vlan = Vlan::factory()->create();
        IpAddress::factory()->count(1500)->create(['vlan_id' => $vlan->id]);

        $this->artisan('network:rollback-import --force')
            ->expectsOutputToContain('1,500')
            ->assertExitCode(0);
    }

    public function test_command_deletes_vlan_999_entries(): void
    {
        Vlan::factory()->create([
            'vlan_id' => 999,
            'vlan_name' => 'Test VLAN 999',
            'network_address' => '10.0.0.0',
            'cidr_suffix' => 24,
            'description' => 'Test description',
        ]);

        $this->assertEquals(1, Vlan::where('vlan_id', 999)->count());

        $this->artisan('network:rollback-import --force')
            ->assertExitCode(0);

        $this->assertEquals(0, Vlan::where('vlan_id', 999)->count());
    }
}
