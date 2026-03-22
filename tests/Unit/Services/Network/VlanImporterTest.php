<?php

namespace Tests\Unit\Services\Network;

use App\Modules\Network\Models\Vlan;
use App\Services\Network\VlanImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VlanImporterTest extends TestCase
{
    use RefreshDatabase;

    private VlanImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importer = new VlanImporter();
    }

    /** @test */
    public function it_imports_a_new_vlan_successfully()
    {
        $vlanData = [
            'vlan_id' => 100,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
            'gateway' => '192.168.1.1',
            'dhcp_from' => '192.168.1.10',
            'dhcp_to' => '192.168.1.250',
            'description' => 'Test Description',
            'internes_netz' => 1,
            'ipscan' => 1,
        ];

        $result = $this->importer->import($vlanData);

        $this->assertTrue($result->success);
        $this->assertFalse($result->isDuplicate);
        $this->assertNotNull($result->recordId);
        $this->assertDatabaseHas('vlans', [
            'vlan_id' => 100,
            'vlan_name' => 'Test VLAN',
            'scan_interval_minutes' => 60,
        ]);

        $vlan = Vlan::find($result->recordId);
        $this->assertNull($vlan->last_scanned_at);
    }

    /** @test */
    public function it_imports_vlan_with_id_999()
    {
        $vlanData = [
            'vlan_id' => 999,
            'vlan_name' => 'Unassigned Networks',
            'network_address' => '10.0.0.0',
            'cidr_suffix' => 8,
            'gateway' => null,
            'dhcp_from' => null,
            'dhcp_to' => null,
            'description' => 'Networks without VLAN assignment',
            'internes_netz' => 0,
            'ipscan' => 0,
        ];

        $result = $this->importer->import($vlanData);

        $this->assertTrue($result->success);
        $this->assertFalse($result->isDuplicate);
        $this->assertDatabaseHas('vlans', ['vlan_id' => 999]);
    }

    /** @test */
    public function it_skips_duplicate_vlan()
    {
        // Create existing VLAN
        Vlan::create([
            'vlan_id' => 100,
            'vlan_name' => 'Existing VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
            'description' => 'Existing',
        ]);

        $vlanData = [
            'vlan_id' => 100,
            'vlan_name' => 'New VLAN',
            'network_address' => '192.168.2.0',
            'cidr_suffix' => 24,
            'description' => 'Should not be imported',
        ];

        $result = $this->importer->import($vlanData);

        $this->assertTrue($result->success);
        $this->assertTrue($result->isDuplicate);
        $this->assertNull($result->recordId);

        // Verify original VLAN is unchanged
        $vlan = Vlan::where('vlan_id', 100)->first();
        $this->assertEquals('Existing VLAN', $vlan->vlan_name);
        $this->assertEquals('192.168.1.0', $vlan->network_address);

        // Verify only one VLAN with this ID exists
        $this->assertEquals(1, Vlan::where('vlan_id', 100)->count());
    }

    /** @test */
    public function it_handles_null_dhcp_values()
    {
        $vlanData = [
            'vlan_id' => 100,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
            'gateway' => '192.168.1.1',
            'dhcp_from' => 'NULL',
            'dhcp_to' => 'NULL',
            'description' => 'Test',
            'internes_netz' => 0,
            'ipscan' => 0,
        ];

        $result = $this->importer->import($vlanData);

        $this->assertTrue($result->success);

        $vlan = Vlan::find($result->recordId);
        $this->assertNull($vlan->dhcp_from);
        $this->assertNull($vlan->dhcp_to);
    }

    /** @test */
    public function it_handles_empty_string_values_as_null()
    {
        $vlanData = [
            'vlan_id' => 100,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
            'gateway' => '',
            'dhcp_from' => '',
            'dhcp_to' => '',
            'description' => 'Test',
            'internes_netz' => 0,
            'ipscan' => 0,
        ];

        $result = $this->importer->import($vlanData);

        $this->assertTrue($result->success);

        $vlan = Vlan::find($result->recordId);
        $this->assertNull($vlan->gateway);
        $this->assertNull($vlan->dhcp_from);
        $this->assertNull($vlan->dhcp_to);
    }

    /** @test */
    public function it_sets_default_values_correctly()
    {
        $vlanData = [
            'vlan_id' => 100,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
            'description' => 'Test',
        ];

        $result = $this->importer->import($vlanData);

        $this->assertTrue($result->success);

        $vlan = Vlan::find($result->recordId);
        $this->assertEquals(60, $vlan->scan_interval_minutes);
        $this->assertNull($vlan->last_scanned_at);
        $this->assertFalse($vlan->internes_netz);
        $this->assertFalse($vlan->ipscan);
    }

    /** @test */
    public function it_allows_multiple_vlans_with_id_999_and_different_network_addresses()
    {
        // Create first VLAN with ID 999
        $vlanData1 = [
            'vlan_id' => 999,
            'vlan_name' => 'Unassigned Network 1',
            'network_address' => '10.0.0.0',
            'cidr_suffix' => 24,
            'description' => 'First unassigned network',
        ];

        $result1 = $this->importer->import($vlanData1);
        $this->assertTrue($result1->success);
        $this->assertFalse($result1->isDuplicate);

        // Create second VLAN with ID 999 but different network address
        $vlanData2 = [
            'vlan_id' => 999,
            'vlan_name' => 'Unassigned Network 2',
            'network_address' => '10.1.0.0',
            'cidr_suffix' => 24,
            'description' => 'Second unassigned network',
        ];

        $result2 = $this->importer->import($vlanData2);
        $this->assertTrue($result2->success);
        $this->assertFalse($result2->isDuplicate);

        // Verify both VLANs exist
        $this->assertEquals(2, Vlan::where('vlan_id', 999)->count());
        $this->assertDatabaseHas('vlans', [
            'vlan_id' => 999,
            'network_address' => '10.0.0.0',
        ]);
        $this->assertDatabaseHas('vlans', [
            'vlan_id' => 999,
            'network_address' => '10.1.0.0',
        ]);
    }

    /** @test */
    public function it_skips_duplicate_vlan_999_with_same_network_address()
    {
        // Create first VLAN with ID 999
        Vlan::create([
            'vlan_id' => 999,
            'vlan_name' => 'Existing Unassigned',
            'network_address' => '10.0.0.0',
            'cidr_suffix' => 24,
            'description' => 'Existing',
        ]);

        // Try to import same VLAN-ID 999 with same network address
        $vlanData = [
            'vlan_id' => 999,
            'vlan_name' => 'New Unassigned',
            'network_address' => '10.0.0.0',
            'cidr_suffix' => 24,
            'description' => 'Should be skipped',
        ];

        $result = $this->importer->import($vlanData);

        $this->assertTrue($result->success);
        $this->assertTrue($result->isDuplicate);
        $this->assertNull($result->recordId);

        // Verify original VLAN is unchanged
        $vlan = Vlan::where('vlan_id', 999)->where('network_address', '10.0.0.0')->first();
        $this->assertEquals('Existing Unassigned', $vlan->vlan_name);

        // Verify only one VLAN with this combination exists
        $this->assertEquals(1, Vlan::where('vlan_id', 999)->where('network_address', '10.0.0.0')->count());
    }

    /** @test */
    public function it_returns_failure_when_vlan_id_is_missing()
    {
        $vlanData = [
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
            'description' => 'Test',
        ];

        $result = $this->importer->import($vlanData);

        $this->assertFalse($result->success);
        $this->assertFalse($result->isDuplicate);
        $this->assertStringContainsString('Missing vlan_id', $result->errorMessage);
    }
}
