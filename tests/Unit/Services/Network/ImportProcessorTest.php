<?php

namespace Tests\Unit\Services\Network;

use Tests\TestCase;
use App\Services\Network\ImportProcessor;
use App\Services\Network\VlanImporter;
use App\Services\Network\IpAddressImporter;
use App\Services\Network\DataValidator;
use App\Services\Network\ImportStatistics;
use App\Services\Network\ValidationResult;
use App\Services\Network\ImportResult;
use App\Modules\Network\Models\Vlan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImportProcessorTest extends TestCase
{
    use RefreshDatabase;

    private ImportProcessor $processor;
    private VlanImporter $vlanImporter;
    private IpAddressImporter $ipAddressImporter;
    private DataValidator $dataValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vlanImporter = $this->createMock(VlanImporter::class);
        $this->ipAddressImporter = $this->createMock(IpAddressImporter::class);
        $this->dataValidator = $this->createMock(DataValidator::class);

        $this->processor = new ImportProcessor(
            $this->vlanImporter,
            $this->ipAddressImporter,
            $this->dataValidator
        );
    }

    public function test_processes_vlans_in_batches()
    {
        // Arrange: Create 250 VLAN records (should be processed in 3 batches of 100, 100, 50)
        $vlans = [];
        for ($i = 1; $i <= 250; $i++) {
            $vlans[] = [
                'vlan_id' => $i,
                'vlan_name' => "VLAN-{$i}",
                'network_address' => "10.0.{$i}.0",
                'cidr_suffix' => 24,
            ];
        }

        // Mock validator to always return valid
        $this->dataValidator
            ->method('validateVlan')
            ->willReturn(new ValidationResult(true));

        // Mock importer to always succeed
        $this->dataValidator
            ->expects($this->exactly(250))
            ->method('validateVlan');

        $this->vlanImporter
            ->method('import')
            ->willReturn(ImportResult::success(1));

        // Track progress callbacks
        $progressCalls = [];
        $progressCallback = function ($current, $total) use (&$progressCalls) {
            $progressCalls[] = ['current' => $current, 'total' => $total];
        };

        // Act
        $statistics = $this->processor->processVlans($vlans, $progressCallback);

        // Assert
        $this->assertEquals(250, $statistics->totalProcessed);
        $this->assertEquals(250, $statistics->successfullyImported);
        $this->assertEquals(0, $statistics->skippedDuplicates);
        $this->assertEquals(0, $statistics->validationErrors);

        // Verify progress callbacks were called at 100, 200, and 250
        $this->assertCount(3, $progressCalls);
        $this->assertEquals(100, $progressCalls[0]['current']);
        $this->assertEquals(200, $progressCalls[1]['current']);
        $this->assertEquals(250, $progressCalls[2]['current']);
    }

    public function test_processes_vlans_with_validation_errors()
    {
        // Arrange
        $vlans = [
            ['vlan_id' => 100, 'vlan_name' => 'Valid'],
            ['vlan_id' => null, 'vlan_name' => 'Invalid'], // Missing vlan_id
            ['vlan_id' => 101, 'vlan_name' => 'Valid'],
        ];

        // Mock validator
        $this->dataValidator
            ->method('validateVlan')
            ->willReturnCallback(function ($vlanData) {
                if ($vlanData['vlan_id'] === null) {
                    return new ValidationResult(false, ['Missing vlan_id']);
                }
                return new ValidationResult(true);
            });

        // Mock importer
        $this->vlanImporter
            ->method('import')
            ->willReturn(ImportResult::success(1));

        // Act
        $statistics = $this->processor->processVlans($vlans);

        // Assert
        $this->assertEquals(3, $statistics->totalProcessed);
        $this->assertEquals(2, $statistics->successfullyImported);
        $this->assertEquals(0, $statistics->skippedDuplicates);
        $this->assertEquals(1, $statistics->validationErrors);
    }

    public function test_processes_vlans_with_duplicates()
    {
        // Arrange
        $vlans = [
            ['vlan_id' => 100, 'vlan_name' => 'VLAN-100'],
            ['vlan_id' => 101, 'vlan_name' => 'VLAN-101'],
            ['vlan_id' => 100, 'vlan_name' => 'VLAN-100-Duplicate'],
        ];

        // Mock validator
        $this->dataValidator
            ->method('validateVlan')
            ->willReturn(new ValidationResult(true));

        // Mock importer to return success for first two, duplicate for third
        $this->vlanImporter
            ->expects($this->exactly(3))
            ->method('import')
            ->willReturnOnConsecutiveCalls(
                ImportResult::success(1),
                ImportResult::success(2),
                ImportResult::duplicate()
            );

        // Act
        $statistics = $this->processor->processVlans($vlans);

        // Assert
        $this->assertEquals(3, $statistics->totalProcessed);
        $this->assertEquals(2, $statistics->successfullyImported);
        $this->assertEquals(1, $statistics->skippedDuplicates);
        $this->assertEquals(0, $statistics->validationErrors);
    }

    public function test_processes_ip_addresses_in_batches()
    {
        // Arrange: Create a VLAN first
        $vlan = Vlan::create([
            'vlan_id' => 100,
            'vlan_name' => 'Test-VLAN',
            'network_address' => '10.0.0.0',
            'cidr_suffix' => 24,
            'description' => 'Test',
        ]);

        // Create 150 IP records
        $ips = [];
        for ($i = 1; $i <= 150; $i++) {
            $ips[] = [
                'vlan_liste_id' => 1, // Old vlan_liste.id
                'ip_address' => "10.0.0.{$i}",
            ];
        }

        // Create old VLAN data for mapping
        $oldVlanData = [
            [1, 100, 'Test-VLAN', '10.0.0.0', 24], // [id, vlan_id, ...]
        ];

        // Mock validator
        $this->dataValidator
            ->method('validateIpAddress')
            ->willReturn(new ValidationResult(true));

        // Mock importer
        $this->ipAddressImporter
            ->method('import')
            ->willReturn(ImportResult::success(1));

        // Track progress callbacks
        $progressCalls = [];
        $progressCallback = function ($current, $total) use (&$progressCalls) {
            $progressCalls[] = ['current' => $current, 'total' => $total];
        };

        // Act
        $statistics = $this->processor->processIpAddresses($ips, $oldVlanData, $progressCallback);

        // Assert
        $this->assertEquals(150, $statistics->totalProcessed);
        $this->assertEquals(150, $statistics->successfullyImported);
        $this->assertEquals(0, $statistics->skippedDuplicates);
        $this->assertEquals(0, $statistics->validationErrors);

        // Verify progress callbacks were called at 100 and 150
        $this->assertCount(2, $progressCalls);
        $this->assertEquals(100, $progressCalls[0]['current']);
        $this->assertEquals(150, $progressCalls[1]['current']);
    }

    public function test_builds_vlan_mapping_correctly()
    {
        // Arrange: Create VLANs in database with unique vlan_ids
        $vlan1 = Vlan::create([
            'vlan_id' => 500,
            'vlan_name' => 'VLAN-500',
            'network_address' => '10.50.3.0',
            'cidr_suffix' => 24,
            'description' => 'Test',
        ]);

        $vlan2 = Vlan::create([
            'vlan_id' => 501,
            'vlan_name' => 'VLAN-501',
            'network_address' => '10.50.4.0',
            'cidr_suffix' => 24,
            'description' => 'Test',
        ]);

        $vlan3 = Vlan::create([
            'vlan_id' => 998,
            'vlan_name' => 'VLAN-998',
            'network_address' => '10.50.21.0',
            'cidr_suffix' => 24,
            'description' => 'Test VLAN-ID 998',
        ]);

        // Create old VLAN data (simulating SQL dump structure)
        $oldVlanData = [
            [1, 500, 'VLAN-500', '10.50.3.0', 24], // old id=1, vlan_id=500
            [2, 501, 'VLAN-501', '10.50.4.0', 24], // old id=2, vlan_id=501
            [18, 998, '', '10.50.21.0', 24], // old id=18, vlan_id=998
        ];

        // Create IP data that references old vlan_liste.id
        $ips = [
            ['vlan_liste_id' => 1, 'ip_address' => '10.50.3.1'], // Should map to vlan1->id
            ['vlan_liste_id' => 2, 'ip_address' => '10.50.4.1'], // Should map to vlan2->id
            ['vlan_liste_id' => 18, 'ip_address' => '10.50.21.1'], // Should map to vlan3->id
        ];

        // Mock validator
        $this->dataValidator
            ->method('validateIpAddress')
            ->willReturn(new ValidationResult(true));

        // Mock importer and capture the mapping it receives
        $capturedMappings = [];
        $this->ipAddressImporter
            ->method('import')
            ->willReturnCallback(function ($ipData, $mapping) use (&$capturedMappings) {
                $capturedMappings[] = $mapping;
                return ImportResult::success(1);
            });

        // Act
        $this->processor->processIpAddresses($ips, $oldVlanData);

        // Assert: Verify the mapping was correct
        $this->assertNotEmpty($capturedMappings);
        $mapping = $capturedMappings[0];
        
        // Old vlan_liste.id=1 should map to new vlan1->id
        $this->assertArrayHasKey(1, $mapping);
        $this->assertEquals($vlan1->id, $mapping[1]);
        
        // Old vlan_liste.id=2 should map to new vlan2->id
        $this->assertArrayHasKey(2, $mapping);
        $this->assertEquals($vlan2->id, $mapping[2]);
        
        // Old vlan_liste.id=18 should map to new vlan3->id (VLAN-ID 998)
        $this->assertArrayHasKey(18, $mapping);
        $this->assertEquals($vlan3->id, $mapping[18]);
    }
}
