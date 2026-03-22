<?php

namespace Tests\Unit\Services\Network;

use App\Modules\Network\Models\IpAddress;
use App\Modules\Network\Models\Vlan;
use App\Services\Network\IpAddressImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IpAddressImporterTest extends TestCase
{
    use RefreshDatabase;

    private IpAddressImporter $importer;
    private Vlan $testVlan;
    private array $vlanMapping;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importer = new IpAddressImporter();

        // Create a test VLAN
        $this->testVlan = Vlan::create([
            'vlan_id' => 100,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
            'description' => 'Test VLAN for IP import',
        ]);

        // Create VLAN mapping (old vlan_liste_id => new vlan_id)
        $this->vlanMapping = [
            1 => $this->testVlan->id,
        ];
    }

    /** @test */
    public function it_imports_a_new_ip_address_successfully()
    {
        $ipData = [
            'vlan_liste_id' => 1,
            'ip_address' => '192.168.1.10',
            'dns_name' => 'test-server.local',
            'mac_address' => '00:1A:2B:3C:4D:5E',
            'is_online' => 1,
            'lastonline' => '2024-01-15 10:30:00',
            'lasttest' => '2024-01-15 10:30:00',
            'ping_response_time_ms' => 15.5,
            'kommentar' => 'Test server',
        ];

        $result = $this->importer->import($ipData, $this->vlanMapping);

        $this->assertTrue($result->success);
        $this->assertFalse($result->isDuplicate);
        $this->assertNotNull($result->recordId);
        $this->assertDatabaseHas('ip_addresses', [
            'vlan_id' => $this->testVlan->id,
            'ip_address' => '192.168.1.10',
            'dns_name' => 'test-server.local',
            'mac_address' => '00:1A:2B:3C:4D:5E',
            'is_online' => true,
            'ping_ms' => 15.5,
            'comment' => 'Test server',
        ]);
    }

    /** @test */
    public function it_correctly_maps_foreign_key_from_old_to_new_vlan_id()
    {
        $ipData = [
            'vlan_liste_id' => 1,
            'ip_address' => '192.168.1.10',
            'dns_name' => null,
            'mac_address' => null,
            'is_online' => 0,
        ];

        $result = $this->importer->import($ipData, $this->vlanMapping);

        $this->assertTrue($result->success);

        $ip = IpAddress::find($result->recordId);
        $this->assertEquals($this->testVlan->id, $ip->vlan_id);
        $this->assertEquals($this->testVlan->id, $ip->vlan->id);
    }

    /** @test */
    public function it_correctly_renames_fields_during_import()
    {
        $ipData = [
            'vlan_liste_id' => 1,
            'ip_address' => '192.168.1.10',
            'lastonline' => '2024-01-15 10:30:00',      // Should become last_online_at
            'lasttest' => '2024-01-15 11:00:00',        // Should become last_scanned_at
            'ping_response_time_ms' => 25.3,            // Should become ping_ms
            'kommentar' => 'Important server',          // Should become comment
        ];

        $result = $this->importer->import($ipData, $this->vlanMapping);

        $this->assertTrue($result->success);

        $ip = IpAddress::find($result->recordId);
        $this->assertEquals('2024-01-15 10:30:00', $ip->last_online_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-01-15 11:00:00', $ip->last_scanned_at->format('Y-m-d H:i:s'));
        $this->assertEquals(25.3, $ip->ping_ms);
        $this->assertEquals('Important server', $ip->comment);
    }

    /** @test */
    public function it_skips_orphaned_ip_when_vlan_does_not_exist()
    {
        $ipData = [
            'vlan_liste_id' => 999,  // Non-existent VLAN
            'ip_address' => '192.168.1.10',
        ];

        $result = $this->importer->import($ipData, $this->vlanMapping);

        $this->assertFalse($result->success);
        $this->assertFalse($result->isDuplicate);
        $this->assertStringContainsString('VLAN not found', $result->errorMessage);
        $this->assertDatabaseMissing('ip_addresses', [
            'ip_address' => '192.168.1.10',
        ]);
    }

    /** @test */
    public function it_skips_duplicate_ip_based_on_vlan_id_and_ip_address()
    {
        // Create existing IP address
        IpAddress::create([
            'vlan_id' => $this->testVlan->id,
            'ip_address' => '192.168.1.10',
            'dns_name' => 'existing-server.local',
            'is_online' => true,
        ]);

        $ipData = [
            'vlan_liste_id' => 1,
            'ip_address' => '192.168.1.10',
            'dns_name' => 'new-server.local',
            'is_online' => 0,
        ];

        $result = $this->importer->import($ipData, $this->vlanMapping);

        $this->assertTrue($result->success);
        $this->assertTrue($result->isDuplicate);
        $this->assertNull($result->recordId);

        // Verify original IP is unchanged
        $ip = IpAddress::where('vlan_id', $this->testVlan->id)
            ->where('ip_address', '192.168.1.10')
            ->first();
        $this->assertEquals('existing-server.local', $ip->dns_name);
        $this->assertTrue($ip->is_online);

        // Verify only one IP with this combination exists
        $this->assertEquals(1, IpAddress::where('vlan_id', $this->testVlan->id)
            ->where('ip_address', '192.168.1.10')
            ->count());
    }

    /** @test */
    public function it_allows_same_ip_address_in_different_vlans()
    {
        // Create second VLAN
        $vlan2 = Vlan::create([
            'vlan_id' => 200,
            'vlan_name' => 'Test VLAN 2',
            'network_address' => '192.168.2.0',
            'cidr_suffix' => 24,
            'description' => 'Second test VLAN',
        ]);

        $this->vlanMapping[2] = $vlan2->id;

        // Import IP in first VLAN
        $ipData1 = [
            'vlan_liste_id' => 1,
            'ip_address' => '192.168.1.10',
        ];
        $result1 = $this->importer->import($ipData1, $this->vlanMapping);
        $this->assertTrue($result1->success);

        // Import same IP address in second VLAN (should succeed)
        $ipData2 = [
            'vlan_liste_id' => 2,
            'ip_address' => '192.168.1.10',
        ];
        $result2 = $this->importer->import($ipData2, $this->vlanMapping);
        $this->assertTrue($result2->success);
        $this->assertFalse($result2->isDuplicate);

        // Verify both IPs exist
        $this->assertEquals(2, IpAddress::where('ip_address', '192.168.1.10')->count());
    }

    /** @test */
    public function it_handles_null_values_correctly()
    {
        $ipData = [
            'vlan_liste_id' => 1,
            'ip_address' => '192.168.1.10',
            'dns_name' => 'NULL',
            'mac_address' => 'NULL',
            'lastonline' => 'NULL',
            'lasttest' => 'NULL',
            'ping_response_time_ms' => 'NULL',
            'kommentar' => 'NULL',
        ];

        $result = $this->importer->import($ipData, $this->vlanMapping);

        $this->assertTrue($result->success);

        $ip = IpAddress::find($result->recordId);
        $this->assertNull($ip->dns_name);
        $this->assertNull($ip->mac_address);
        $this->assertNull($ip->last_online_at);
        $this->assertNull($ip->last_scanned_at);
        $this->assertNull($ip->ping_ms);
        $this->assertNull($ip->comment);
    }

    /** @test */
    public function it_handles_empty_string_values_as_null()
    {
        $ipData = [
            'vlan_liste_id' => 1,
            'ip_address' => '192.168.1.10',
            'dns_name' => '',
            'mac_address' => '',
            'kommentar' => '',
        ];

        $result = $this->importer->import($ipData, $this->vlanMapping);

        $this->assertTrue($result->success);

        $ip = IpAddress::find($result->recordId);
        $this->assertNull($ip->dns_name);
        $this->assertNull($ip->mac_address);
        $this->assertNull($ip->comment);
    }

    /** @test */
    public function it_returns_failure_when_ip_address_is_missing()
    {
        $ipData = [
            'vlan_liste_id' => 1,
            'dns_name' => 'test-server.local',
        ];

        $result = $this->importer->import($ipData, $this->vlanMapping);

        $this->assertFalse($result->success);
        $this->assertFalse($result->isDuplicate);
        $this->assertStringContainsString('Missing ip_address', $result->errorMessage);
    }

    /** @test */
    public function it_returns_failure_when_vlan_liste_id_is_missing()
    {
        $ipData = [
            'ip_address' => '192.168.1.10',
            'dns_name' => 'test-server.local',
        ];

        $result = $this->importer->import($ipData, $this->vlanMapping);

        $this->assertFalse($result->success);
        $this->assertFalse($result->isDuplicate);
        $this->assertStringContainsString('Missing vlan_liste_id', $result->errorMessage);
    }

    /** @test */
    public function it_converts_ping_ms_to_float()
    {
        $ipData = [
            'vlan_liste_id' => 1,
            'ip_address' => '192.168.1.10',
            'ping_response_time_ms' => '42.7',
        ];

        $result = $this->importer->import($ipData, $this->vlanMapping);

        $this->assertTrue($result->success);

        $ip = IpAddress::find($result->recordId);
        $this->assertIsFloat($ip->ping_ms);
        $this->assertEquals(42.7, $ip->ping_ms);
    }

    /** @test */
    public function it_converts_is_online_to_boolean()
    {
        $ipData1 = [
            'vlan_liste_id' => 1,
            'ip_address' => '192.168.1.10',
            'is_online' => 1,
        ];

        $result1 = $this->importer->import($ipData1, $this->vlanMapping);
        $this->assertTrue($result1->success);

        $ip1 = IpAddress::find($result1->recordId);
        $this->assertTrue($ip1->is_online);

        $ipData2 = [
            'vlan_liste_id' => 1,
            'ip_address' => '192.168.1.11',
            'is_online' => 0,
        ];

        $result2 = $this->importer->import($ipData2, $this->vlanMapping);
        $this->assertTrue($result2->success);

        $ip2 = IpAddress::find($result2->recordId);
        $this->assertFalse($ip2->is_online);
    }
}
