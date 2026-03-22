<?php

namespace Tests\Unit\Services\Network;

use App\Services\Network\DataValidator;
use App\Services\Network\ValidationResult;
use Tests\TestCase;

class DataValidatorTest extends TestCase
{
    private DataValidator $validator;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new DataValidator();
    }
    
    /** @test */
    public function it_validates_vlan_with_all_required_fields()
    {
        $vlanData = [
            'vlan_id' => 100,
            'network_address' => '192.168.1.0',
            'vlan_name' => 'Test VLAN',
            'cidr_suffix' => 24,
        ];
        
        $result = $this->validator->validateVlan($vlanData);
        
        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }
    
    /** @test */
    public function it_accepts_vlan_id_999()
    {
        $vlanData = [
            'vlan_id' => 999,
            'network_address' => '10.0.0.0',
            'vlan_name' => 'Unassigned VLAN',
            'cidr_suffix' => 24,
        ];
        
        $result = $this->validator->validateVlan($vlanData);
        
        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }
    
    /** @test */
    public function it_rejects_vlan_without_vlan_id()
    {
        $vlanData = [
            'network_address' => '192.168.1.0',
            'vlan_name' => 'Test VLAN',
        ];
        
        $result = $this->validator->validateVlan($vlanData);
        
        $this->assertFalse($result->isValid);
        $this->assertContains('Missing required field: vlan_id', $result->errors);
    }
    
    /** @test */
    public function it_rejects_vlan_with_null_vlan_id()
    {
        $vlanData = [
            'vlan_id' => null,
            'network_address' => '192.168.1.0',
        ];
        
        $result = $this->validator->validateVlan($vlanData);
        
        $this->assertFalse($result->isValid);
        $this->assertContains('Missing required field: vlan_id', $result->errors);
    }
    
    /** @test */
    public function it_rejects_vlan_without_network_address()
    {
        $vlanData = [
            'vlan_id' => 100,
            'vlan_name' => 'Test VLAN',
        ];
        
        $result = $this->validator->validateVlan($vlanData);
        
        $this->assertFalse($result->isValid);
        $this->assertContains('Missing required field: network_address', $result->errors);
    }
    
    /** @test */
    public function it_rejects_vlan_with_null_network_address()
    {
        $vlanData = [
            'vlan_id' => 100,
            'network_address' => null,
        ];
        
        $result = $this->validator->validateVlan($vlanData);
        
        $this->assertFalse($result->isValid);
        $this->assertContains('Missing required field: network_address', $result->errors);
    }
    
    /** @test */
    public function it_rejects_vlan_with_invalid_vlan_id()
    {
        $vlanData = [
            'vlan_id' => 5000, // Out of valid range (1-4094)
            'network_address' => '192.168.1.0',
        ];
        
        $result = $this->validator->validateVlan($vlanData);
        
        $this->assertFalse($result->isValid);
        $this->assertContains('Invalid VLAN ID: must be between 1 and 4094', $result->errors);
    }
    
    /** @test */
    public function it_rejects_vlan_with_zero_vlan_id()
    {
        $vlanData = [
            'vlan_id' => 0,
            'network_address' => '192.168.1.0',
        ];
        
        $result = $this->validator->validateVlan($vlanData);
        
        $this->assertFalse($result->isValid);
        $this->assertContains('Invalid VLAN ID: must be between 1 and 4094', $result->errors);
    }
    
    /** @test */
    public function it_rejects_vlan_with_invalid_network_address()
    {
        $vlanData = [
            'vlan_id' => 100,
            'network_address' => 'invalid-ip',
        ];
        
        $result = $this->validator->validateVlan($vlanData);
        
        $this->assertFalse($result->isValid);
        $this->assertContains('Invalid network address format', $result->errors);
    }
    
    /** @test */
    public function it_validates_vlan_with_optional_gateway()
    {
        $vlanData = [
            'vlan_id' => 100,
            'network_address' => '192.168.1.0',
            'gateway' => '192.168.1.1',
        ];
        
        $result = $this->validator->validateVlan($vlanData);
        
        $this->assertTrue($result->isValid);
    }
    
    /** @test */
    public function it_rejects_vlan_with_invalid_gateway()
    {
        $vlanData = [
            'vlan_id' => 100,
            'network_address' => '192.168.1.0',
            'gateway' => 'invalid-gateway',
        ];
        
        $result = $this->validator->validateVlan($vlanData);
        
        $this->assertFalse($result->isValid);
        $this->assertContains('Invalid gateway IP address format', $result->errors);
    }
    
    /** @test */
    public function it_validates_vlan_with_dhcp_range()
    {
        $vlanData = [
            'vlan_id' => 100,
            'network_address' => '192.168.1.0',
            'dhcp_from' => '192.168.1.10',
            'dhcp_to' => '192.168.1.100',
        ];
        
        $result = $this->validator->validateVlan($vlanData);
        
        $this->assertTrue($result->isValid);
    }
    
    /** @test */
    public function it_rejects_vlan_with_invalid_dhcp_from()
    {
        $vlanData = [
            'vlan_id' => 100,
            'network_address' => '192.168.1.0',
            'dhcp_from' => 'invalid-ip',
        ];
        
        $result = $this->validator->validateVlan($vlanData);
        
        $this->assertFalse($result->isValid);
        $this->assertContains('Invalid dhcp_from IP address format', $result->errors);
    }
    
    /** @test */
    public function it_validates_vlan_with_valid_cidr_suffix()
    {
        $vlanData = [
            'vlan_id' => 100,
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ];
        
        $result = $this->validator->validateVlan($vlanData);
        
        $this->assertTrue($result->isValid);
    }
    
    /** @test */
    public function it_rejects_vlan_with_invalid_cidr_suffix()
    {
        $vlanData = [
            'vlan_id' => 100,
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 33, // Out of valid range (0-32)
        ];
        
        $result = $this->validator->validateVlan($vlanData);
        
        $this->assertFalse($result->isValid);
        $this->assertContains('Invalid CIDR suffix: must be between 0 and 32', $result->errors);
    }
    
    /** @test */
    public function it_validates_ip_address_with_all_required_fields()
    {
        $ipData = [
            'ip_address' => '192.168.1.10',
            'vlan_liste_id' => 1,
        ];
        
        $result = $this->validator->validateIpAddress($ipData);
        
        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }
    
    /** @test */
    public function it_rejects_ip_address_without_ip_address_field()
    {
        $ipData = [
            'vlan_liste_id' => 1,
        ];
        
        $result = $this->validator->validateIpAddress($ipData);
        
        $this->assertFalse($result->isValid);
        $this->assertContains('Missing required field: ip_address', $result->errors);
    }
    
    /** @test */
    public function it_rejects_ip_address_with_null_ip_address()
    {
        $ipData = [
            'ip_address' => null,
            'vlan_liste_id' => 1,
        ];
        
        $result = $this->validator->validateIpAddress($ipData);
        
        $this->assertFalse($result->isValid);
        $this->assertContains('Missing required field: ip_address', $result->errors);
    }
    
    /** @test */
    public function it_rejects_ip_address_without_vlan_liste_id()
    {
        $ipData = [
            'ip_address' => '192.168.1.10',
        ];
        
        $result = $this->validator->validateIpAddress($ipData);
        
        $this->assertFalse($result->isValid);
        $this->assertContains('Missing required field: vlan_liste_id', $result->errors);
    }
    
    /** @test */
    public function it_rejects_ip_address_with_null_vlan_liste_id()
    {
        $ipData = [
            'ip_address' => '192.168.1.10',
            'vlan_liste_id' => null,
        ];
        
        $result = $this->validator->validateIpAddress($ipData);
        
        $this->assertFalse($result->isValid);
        $this->assertContains('Missing required field: vlan_liste_id', $result->errors);
    }
    
    /** @test */
    public function it_rejects_ip_address_with_invalid_ip_format()
    {
        $ipData = [
            'ip_address' => 'invalid-ip',
            'vlan_liste_id' => 1,
        ];
        
        $result = $this->validator->validateIpAddress($ipData);
        
        $this->assertFalse($result->isValid);
        $this->assertContains('Invalid IP address format', $result->errors);
    }
    
    /** @test */
    public function it_validates_ip_address_with_valid_mac_address()
    {
        $ipData = [
            'ip_address' => '192.168.1.10',
            'vlan_liste_id' => 1,
            'mac_address' => '00:1A:2B:3C:4D:5E',
        ];
        
        $result = $this->validator->validateIpAddress($ipData);
        
        $this->assertTrue($result->isValid);
    }
    
    /** @test */
    public function it_validates_ip_address_with_mac_address_dash_format()
    {
        $ipData = [
            'ip_address' => '192.168.1.10',
            'vlan_liste_id' => 1,
            'mac_address' => '00-1A-2B-3C-4D-5E',
        ];
        
        $result = $this->validator->validateIpAddress($ipData);
        
        $this->assertTrue($result->isValid);
    }
    
    /** @test */
    public function it_validates_ip_address_with_mac_address_cisco_format()
    {
        $ipData = [
            'ip_address' => '192.168.1.10',
            'vlan_liste_id' => 1,
            'mac_address' => '001A.2B3C.4D5E',
        ];
        
        $result = $this->validator->validateIpAddress($ipData);
        
        $this->assertTrue($result->isValid);
    }
    
    /** @test */
    public function it_rejects_ip_address_with_invalid_mac_address()
    {
        $ipData = [
            'ip_address' => '192.168.1.10',
            'vlan_liste_id' => 1,
            'mac_address' => 'invalid-mac',
        ];
        
        $result = $this->validator->validateIpAddress($ipData);
        
        $this->assertFalse($result->isValid);
        $this->assertContains('Invalid MAC address format', $result->errors);
    }
    
    /** @test */
    public function it_validates_ip_address_with_valid_ping_ms()
    {
        $ipData = [
            'ip_address' => '192.168.1.10',
            'vlan_liste_id' => 1,
            'ping_response_time_ms' => 15.5,
        ];
        
        $result = $this->validator->validateIpAddress($ipData);
        
        $this->assertTrue($result->isValid);
    }
    
    /** @test */
    public function it_rejects_ip_address_with_negative_ping_ms()
    {
        $ipData = [
            'ip_address' => '192.168.1.10',
            'vlan_liste_id' => 1,
            'ping_response_time_ms' => -5,
        ];
        
        $result = $this->validator->validateIpAddress($ipData);
        
        $this->assertFalse($result->isValid);
        $this->assertContains('Invalid ping_response_time_ms: must be non-negative', $result->errors);
    }
    
    /** @test */
    public function it_validates_ipv6_addresses()
    {
        $ipData = [
            'ip_address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'vlan_liste_id' => 1,
        ];
        
        $result = $this->validator->validateIpAddress($ipData);
        
        $this->assertTrue($result->isValid);
    }
    
    /** @test */
    public function it_collects_multiple_validation_errors()
    {
        $vlanData = [
            'vlan_id' => 5000, // Invalid
            'network_address' => 'invalid-ip', // Invalid
            'gateway' => 'invalid-gateway', // Invalid
        ];
        
        $result = $this->validator->validateVlan($vlanData);
        
        $this->assertFalse($result->isValid);
        $this->assertCount(3, $result->errors);
        $this->assertContains('Invalid VLAN ID: must be between 1 and 4094', $result->errors);
        $this->assertContains('Invalid network address format', $result->errors);
        $this->assertContains('Invalid gateway IP address format', $result->errors);
    }
    
    /** @test */
    public function validation_result_can_get_error_message_as_string()
    {
        $result = ValidationResult::failure(['Error 1', 'Error 2']);
        
        $this->assertEquals('Error 1, Error 2', $result->getErrorMessage());
    }
    
    /** @test */
    public function validation_result_can_add_errors_dynamically()
    {
        $result = ValidationResult::success();
        
        $this->assertTrue($result->isValid);
        
        $result->addError('New error');
        
        $this->assertFalse($result->isValid);
        $this->assertContains('New error', $result->errors);
    }
}
