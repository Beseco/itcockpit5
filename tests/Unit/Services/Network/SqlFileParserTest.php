<?php

namespace Tests\Unit\Services\Network;

use App\Services\Network\SqlFileParser;
use App\Exceptions\CriticalImportException;
use Tests\TestCase;

class SqlFileParserTest extends TestCase
{
    private SqlFileParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new SqlFileParser();
    }

    /** @test */
    public function it_throws_exception_for_missing_file()
    {
        $this->expectException(CriticalImportException::class);
        $this->expectExceptionMessage('SQL file not found');
        
        $this->parser->parse('nonexistent.sql');
    }

    /** @test */
    public function it_parses_vlan_liste_sql_file()
    {
        $filePath = base_path('docs/olddb/vlan_liste.sql');
        
        if (!file_exists($filePath)) {
            $this->markTestSkipped('vlan_liste.sql file not found');
        }
        
        $records = $this->parser->parse($filePath);
        
        // Should have 169 VLANs
        $this->assertCount(169, $records);
        
        // Check first record structure
        $firstRecord = $records[0];
        $this->assertIsArray($firstRecord);
        $this->assertCount(13, $firstRecord); // 13 columns
        
        // Verify first record values
        $this->assertEquals('1', $firstRecord[0]); // id
        $this->assertEquals('130', $firstRecord[1]); // vlan_id
        $this->assertEquals('LRA-130-AppSrv', $firstRecord[2]); // vlan_name
        $this->assertEquals('10.22.3.0', $firstRecord[3]); // network_address
        $this->assertEquals('24', $firstRecord[4]); // cidr_suffix
    }

    /** @test */
    public function it_parses_vlan_ip_sql_file()
    {
        $filePath = base_path('docs/olddb/vlan_ip.sql');
        
        if (!file_exists($filePath)) {
            $this->markTestSkipped('vlan_ip.sql file not found');
        }
        
        $records = $this->parser->parse($filePath);
        
        // Should have many IP records
        $this->assertGreaterThan(0, $records);
        
        // Check first record structure
        $firstRecord = $records[0];
        $this->assertIsArray($firstRecord);
        $this->assertCount(13, $firstRecord); // 13 columns
        
        // Verify first record values
        $this->assertEquals('1', $firstRecord[0]); // id
        $this->assertEquals('1', $firstRecord[1]); // vlan_liste_id
        $this->assertEquals('10.22.3.1', $firstRecord[3]); // ip_address
    }

    /** @test */
    public function it_handles_null_values_correctly()
    {
        $filePath = base_path('docs/olddb/vlan_liste.sql');
        
        if (!file_exists($filePath)) {
            $this->markTestSkipped('vlan_liste.sql file not found');
        }
        
        $records = $this->parser->parse($filePath);
        
        // Find a record with NULL dhcp_from and dhcp_to (record id 5)
        $recordWithNulls = $records[4]; // 0-indexed, so record 5 is at index 4
        
        // dhcp_from and dhcp_to should be NULL
        $this->assertNull($recordWithNulls[6]); // dhcp_from
        $this->assertNull($recordWithNulls[7]); // dhcp_to
    }

    /** @test */
    public function it_handles_vlan_id_999_correctly()
    {
        $filePath = base_path('docs/olddb/vlan_liste.sql');
        
        if (!file_exists($filePath)) {
            $this->markTestSkipped('vlan_liste.sql file not found');
        }
        
        $records = $this->parser->parse($filePath);
        
        // Find records with vlan_id 999
        $vlan999Records = array_filter($records, function($record) {
            return $record[1] === '999'; // vlan_id is at index 1
        });
        
        // Should have multiple VLAN 999 entries
        $this->assertGreaterThan(0, count($vlan999Records));
        
        // Verify one of them (record 18)
        $record18 = $records[17]; // 0-indexed
        $this->assertEquals('999', $record18[1]); // vlan_id
        $this->assertEquals('10.22.21.0', $record18[3]); // network_address
    }

    /** @test */
    public function it_handles_empty_strings_correctly()
    {
        $filePath = base_path('docs/olddb/vlan_liste.sql');
        
        if (!file_exists($filePath)) {
            $this->markTestSkipped('vlan_liste.sql file not found');
        }
        
        $records = $this->parser->parse($filePath);
        
        // Record 18 has empty vlan_name
        $record18 = $records[17]; // 0-indexed
        $this->assertEquals('', $record18[2]); // vlan_name should be empty string
    }

    /** @test */
    public function it_handles_special_characters_in_strings()
    {
        $filePath = base_path('docs/olddb/vlan_liste.sql');
        
        if (!file_exists($filePath)) {
            $this->markTestSkipped('vlan_liste.sql file not found');
        }
        
        $records = $this->parser->parse($filePath);
        
        // Find record with special characters in description
        // Record 22 has "div. Netze u.a Transfer (BYBN; )!!!"
        $record22 = $records[21]; // 0-indexed
        $this->assertStringContainsString('Transfer', $record22[8]); // description
        $this->assertStringContainsString('(', $record22[8]);
        $this->assertStringContainsString(')', $record22[8]);
    }

    /** @test */
    public function it_returns_empty_array_for_file_without_inserts()
    {
        // Create a temporary SQL file without INSERT statements
        $tempFile = tempnam(sys_get_temp_dir(), 'test_sql_');
        file_put_contents($tempFile, "-- Comment\nCREATE TABLE test (id INT);");
        
        $records = $this->parser->parse($tempFile);
        
        $this->assertIsArray($records);
        $this->assertEmpty($records);
        
        unlink($tempFile);
    }
}
