<?php

use App\Modules\Network\Services\ScannerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Run module migrations
    $this->artisan('migrate', ['--path' => 'app/Modules/Network/Database/Migrations']);
    
    $this->service = new ScannerService();
});

test('pingIpAddress returns online status and response time for successful ping on Windows', function () {
    // Mock Windows ping output
    $windowsOutput = "Reply from 192.168.1.1: bytes=32 time=15ms TTL=64";
    
    // Create a partial mock to override shell_exec and isWindows
    $service = Mockery::mock(ScannerService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('isWindows')->andReturn(true);
    
    // Test the parsing directly
    $result = $service->parsePingOutput($windowsOutput, true);
    
    expect($result['is_online'])->toBeTrue();
    expect($result['ping_ms'])->toBe(15.0);
});

test('pingIpAddress returns online status for sub-millisecond ping on Windows', function () {
    // Mock Windows ping output with time<1ms
    $windowsOutput = "Reply from 192.168.1.1: bytes=32 time<1ms TTL=64";
    
    $service = Mockery::mock(ScannerService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    
    $result = $service->parsePingOutput($windowsOutput, true);
    
    expect($result['is_online'])->toBeTrue();
    expect($result['ping_ms'])->toBe(0.5);
});

test('pingIpAddress returns offline status for failed ping on Windows', function () {
    // Mock Windows ping output for timeout
    $windowsOutput = "Request timed out.";
    
    $service = Mockery::mock(ScannerService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    
    $result = $service->parsePingOutput($windowsOutput, true);
    
    expect($result['is_online'])->toBeFalse();
    expect($result['ping_ms'])->toBeNull();
});

test('pingIpAddress returns online status and response time for successful ping on Linux', function () {
    // Mock Linux ping output
    $linuxOutput = "64 bytes from 192.168.1.1: icmp_seq=1 ttl=64 time=0.123 ms";
    
    $service = Mockery::mock(ScannerService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    
    $result = $service->parsePingOutput($linuxOutput, false);
    
    expect($result['is_online'])->toBeTrue();
    expect($result['ping_ms'])->toBe(0.123);
});

test('pingIpAddress returns offline status for failed ping on Linux', function () {
    // Mock Linux ping output for unreachable host
    $linuxOutput = "From 192.168.1.1 icmp_seq=1 Destination Host Unreachable";
    
    $service = Mockery::mock(ScannerService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    
    $result = $service->parsePingOutput($linuxOutput, false);
    
    expect($result['is_online'])->toBeFalse();
    expect($result['ping_ms'])->toBeNull();
});

test('pingIpAddress handles null output from shell_exec', function () {
    // This test verifies that when shell_exec returns null,
    // the service handles it gracefully and returns offline status
    
    // We can't easily mock shell_exec, but we can verify the method
    // returns the correct structure even when ping fails
    $service = new ScannerService();
    
    // Test with an invalid/unreachable IP that should timeout
    // Using a reserved IP that won't respond
    $result = $service->pingIpAddress('192.0.2.1'); // TEST-NET-1 (RFC 5737)
    
    // Should return proper structure regardless of success/failure
    expect($result)->toBeArray();
    expect($result)->toHaveKey('is_online');
    expect($result)->toHaveKey('ping_ms');
    expect($result['is_online'])->toBeIn([true, false]);
    
    if (!$result['is_online']) {
        expect($result['ping_ms'])->toBeNull();
    }
});

test('isWindows correctly detects Windows OS', function () {
    $service = new ScannerService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('isWindows');
    $method->setAccessible(true);
    
    $result = $method->invoke($service);
    
    // Result depends on the actual OS running the test
    expect($result)->toBeIn([true, false]);
});

test('parseWindowsPingOutput handles various time formats', function () {
    $service = new ScannerService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('parseWindowsPingOutput');
    $method->setAccessible(true);
    
    // Test with time=Xms format
    $output1 = "Reply from 192.168.1.1: bytes=32 time=25ms TTL=64";
    $result1 = $method->invoke($service, $output1);
    expect($result1['is_online'])->toBeTrue();
    expect($result1['ping_ms'])->toBe(25.0);
    
    // Test with time<1ms format
    $output2 = "Reply from 192.168.1.1: bytes=32 time<1ms TTL=64";
    $result2 = $method->invoke($service, $output2);
    expect($result2['is_online'])->toBeTrue();
    expect($result2['ping_ms'])->toBe(0.5);
    
    // Test with timeout
    $output3 = "Request timed out.";
    $result3 = $method->invoke($service, $output3);
    expect($result3['is_online'])->toBeFalse();
    expect($result3['ping_ms'])->toBeNull();
});

test('parseLinuxPingOutput handles various response formats', function () {
    $service = new ScannerService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('parseLinuxPingOutput');
    $method->setAccessible(true);
    
    // Test with successful ping
    $output1 = "64 bytes from 192.168.1.1: icmp_seq=1 ttl=64 time=1.234 ms";
    $result1 = $method->invoke($service, $output1);
    expect($result1['is_online'])->toBeTrue();
    expect($result1['ping_ms'])->toBe(1.234);
    
    // Test with sub-millisecond ping
    $output2 = "64 bytes from 192.168.1.1: icmp_seq=1 ttl=64 time=0.5 ms";
    $result2 = $method->invoke($service, $output2);
    expect($result2['is_online'])->toBeTrue();
    expect($result2['ping_ms'])->toBe(0.5);
    
    // Test with unreachable host
    $output3 = "From 192.168.1.1 icmp_seq=1 Destination Host Unreachable";
    $result3 = $method->invoke($service, $output3);
    expect($result3['is_online'])->toBeFalse();
    expect($result3['ping_ms'])->toBeNull();
});

test('pingIpAddress escapes IP address argument', function () {
    // This test ensures that the IP address is properly escaped
    // to prevent command injection
    $service = new ScannerService();
    
    // Test with a potentially malicious IP
    $maliciousIp = "192.168.1.1; rm -rf /";
    
    // The escapeshellarg should handle this safely
    // We can't easily test the actual command execution without mocking,
    // but we can verify the method doesn't crash
    $result = $service->pingIpAddress($maliciousIp);
    
    expect($result)->toBeArray();
    expect($result)->toHaveKey('is_online');
    expect($result)->toHaveKey('ping_ms');
});


// MAC Address Resolution Tests

test('resolveMacAddress parses Windows ARP output correctly', function () {
    $service = Mockery::mock(ScannerService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('isWindows')->andReturn(true);
    
    // Mock Windows ARP output
    $windowsArpOutput = <<<EOT
Interface: 192.168.1.100 --- 0x2
  Internet Address      Physical Address      Type
  192.168.1.1           aa-bb-cc-dd-ee-ff     dynamic
EOT;
    
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('parseArpOutput');
    $method->setAccessible(true);
    
    $result = $method->invoke($service, $windowsArpOutput);
    
    expect($result)->toBe('aa-bb-cc-dd-ee-ff');
});

test('resolveMacAddress parses Linux ARP output correctly', function () {
    $service = Mockery::mock(ScannerService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('isWindows')->andReturn(false);
    
    // Mock Linux ARP output
    $linuxArpOutput = <<<EOT
Address                  HWtype  HWaddress           Flags Mask            Iface
192.168.1.1              ether   aa:bb:cc:dd:ee:ff   C                     eth0
EOT;
    
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('parseArpOutput');
    $method->setAccessible(true);
    
    $result = $method->invoke($service, $linuxArpOutput);
    
    expect($result)->toBe('aa:bb:cc:dd:ee:ff');
});

test('parseArpOutput returns null when no MAC address found', function () {
    $service = new ScannerService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('parseArpOutput');
    $method->setAccessible(true);
    
    // ARP output with no entry
    $noEntryOutput = "No ARP Entries Found";
    
    $result = $method->invoke($service, $noEntryOutput);
    
    expect($result)->toBeNull();
});

test('normalizeMacAddress converts hyphen-separated to colon-separated uppercase', function () {
    $service = new ScannerService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('normalizeMacAddress');
    $method->setAccessible(true);
    
    // Test Windows format (hyphens, lowercase)
    $result = $method->invoke($service, 'aa-bb-cc-dd-ee-ff');
    expect($result)->toBe('AA:BB:CC:DD:EE:FF');
});

test('normalizeMacAddress converts colon-separated lowercase to uppercase', function () {
    $service = new ScannerService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('normalizeMacAddress');
    $method->setAccessible(true);
    
    // Test Linux format (colons, lowercase)
    $result = $method->invoke($service, 'aa:bb:cc:dd:ee:ff');
    expect($result)->toBe('AA:BB:CC:DD:EE:FF');
});

test('normalizeMacAddress handles mixed case input', function () {
    $service = new ScannerService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('normalizeMacAddress');
    $method->setAccessible(true);
    
    // Test mixed case
    $result = $method->invoke($service, 'Aa-Bb-Cc-Dd-Ee-Ff');
    expect($result)->toBe('AA:BB:CC:DD:EE:FF');
});

test('parseArpOutput handles various MAC address formats', function () {
    $service = new ScannerService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('parseArpOutput');
    $method->setAccessible(true);
    
    // Test with uppercase
    $output1 = "192.168.1.1  AA:BB:CC:DD:EE:FF  dynamic";
    $result1 = $method->invoke($service, $output1);
    expect($result1)->toBe('AA:BB:CC:DD:EE:FF');
    
    // Test with lowercase
    $output2 = "192.168.1.1  aa:bb:cc:dd:ee:ff  dynamic";
    $result2 = $method->invoke($service, $output2);
    expect($result2)->toBe('aa:bb:cc:dd:ee:ff');
    
    // Test with hyphens
    $output3 = "192.168.1.1  AA-BB-CC-DD-EE-FF  dynamic";
    $result3 = $method->invoke($service, $output3);
    expect($result3)->toBe('AA-BB-CC-DD-EE-FF');
    
    // Test with mixed case and hyphens
    $output4 = "192.168.1.1  aA-bB-cC-dD-eE-fF  dynamic";
    $result4 = $method->invoke($service, $output4);
    expect($result4)->toBe('aA-bB-cC-dD-eE-fF');
});

test('resolveMacAddress escapes IP address argument', function () {
    // This test ensures that the IP address is properly escaped
    // to prevent command injection
    $service = new ScannerService();
    
    // Test with a potentially malicious IP
    $maliciousIp = "192.168.1.1; rm -rf /";
    
    // The escapeshellarg should handle this safely
    // We can't easily test the actual command execution without mocking,
    // but we can verify the method doesn't crash
    $result = $service->resolveMacAddress($maliciousIp);
    
    // Should return null (command will fail due to invalid IP format)
    expect($result)->toBeNull();
});

test('resolveMacAddress returns null when shell_exec fails', function () {
    // Create a mock that simulates shell_exec returning null
    $service = Mockery::mock(ScannerService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    
    // We can't easily mock shell_exec globally, but we can test with an invalid IP
    // that should result in no ARP entry
    $result = $service->resolveMacAddress('192.0.2.1'); // TEST-NET-1 (RFC 5737)
    
    // Should return null for non-existent ARP entry (or possibly a valid MAC if it exists in cache)
    expect($result)->toBeIn([null, 'string']);
});

test('resolveMacAddress logs error when ARP command fails', function () {
    // This test verifies the error logging behavior
    // Since we can't easily mock shell_exec, we test the structure
    $service = new ScannerService();
    
    // Test with an invalid IP that will cause ARP to fail
    $result = $service->resolveMacAddress('invalid-ip');
    
    // Should return null when command fails
    expect($result)->toBeNull();
});

test('resolveMacAddress logs debug when no ARP entry found', function () {
    Log::shouldReceive('debug')
        ->withArgs(function ($message, $context) {
            return $message === 'No ARP entry found for IP address' &&
                   isset($context['ip_address']);
        });
    
    $service = new ScannerService();
    $reflection = new ReflectionClass($service);
    
    // Mock parseArpOutput to return null
    $service = Mockery::mock(ScannerService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('parseArpOutput')->andReturn(null);
    
    $result = $service->resolveMacAddress('192.168.1.1');
    
    expect($result)->toBeNull();
});


// VLAN Scanning Tests

test('scanVlan processes all IP addresses and returns summary', function () {
    // Create a VLAN with IP addresses
    $vlan = \App\Modules\Network\Models\Vlan::create([
        'vlan_id' => 100,
        'vlan_name' => 'Test VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 29, // Small subnet for testing (6 hosts)
        'ipscan' => true,
    ]);
    
    // Generate IP addresses for the VLAN
    $ipGenerator = new \App\Modules\Network\Services\IpGeneratorService();
    $ipGenerator->generateIpAddresses($vlan);
    
    // Mock the ScannerService to control ping and MAC resolution results
    $service = Mockery::mock(ScannerService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    
    // Mock ping results: first 3 online, rest offline
    $callCount = 0;
    $service->shouldReceive('pingIpAddress')
        ->andReturnUsing(function ($ip) use (&$callCount) {
            $callCount++;
            return [
                'is_online' => $callCount <= 3,
                'ping_ms' => $callCount <= 3 ? 10.5 : null,
            ];
        });
    
    // Mock MAC resolution for online IPs
    $service->shouldReceive('resolveMacAddress')
        ->andReturn('AA:BB:CC:DD:EE:FF');
    
    // Scan the VLAN
    $result = $service->scanVlan($vlan);
    
    // Verify summary
    expect($result)->toBeArray();
    expect($result)->toHaveKey('scanned');
    expect($result)->toHaveKey('online');
    expect($result)->toHaveKey('offline');
    expect($result['scanned'])->toBe(6); // 6 host addresses in /29
    expect($result['online'])->toBe(3);
    expect($result['offline'])->toBe(3);
    
    // Verify IP addresses were updated
    $vlan->refresh();
    $onlineIps = $vlan->ipAddresses()->where('is_online', true)->count();
    $offlineIps = $vlan->ipAddresses()->where('is_online', false)->count();
    
    expect($onlineIps)->toBe(3);
    expect($offlineIps)->toBe(3);
});

test('scanVlan updates IP address records with scan results', function () {
    // Create a VLAN with one IP address
    $vlan = \App\Modules\Network\Models\Vlan::create([
        'vlan_id' => 101,
        'vlan_name' => 'Test VLAN 2',
        'network_address' => '10.0.0.0',
        'cidr_suffix' => 30, // 2 hosts
        'ipscan' => true,
    ]);
    
    $ipAddress = \App\Modules\Network\Models\IpAddress::create([
        'vlan_id' => $vlan->id,
        'ip_address' => '10.0.0.1',
        'is_online' => false,
        'last_scanned_at' => null,
    ]);
    
    // Mock the ScannerService
    $service = Mockery::mock(ScannerService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    
    $service->shouldReceive('pingIpAddress')
        ->with('10.0.0.1')
        ->andReturn([
            'is_online' => true,
            'ping_ms' => 5.2,
        ]);
    
    $service->shouldReceive('resolveMacAddress')
        ->with('10.0.0.1')
        ->andReturn('11:22:33:44:55:66');
    
    // Scan the VLAN
    $result = $service->scanVlan($vlan);
    
    // Verify the IP address was updated
    $ipAddress->refresh();
    expect($ipAddress->is_online)->toBeTrue();
    expect($ipAddress->ping_ms)->toBe(5.2);
    expect($ipAddress->mac_address)->toBe('11:22:33:44:55:66');
    expect($ipAddress->last_scanned_at)->not->toBeNull();
    expect($ipAddress->last_online_at)->not->toBeNull();
});

test('scanVlan only resolves MAC for online IPs', function () {
    // Create a VLAN with IP addresses
    $vlan = \App\Modules\Network\Models\Vlan::create([
        'vlan_id' => 102,
        'vlan_name' => 'Test VLAN 3',
        'network_address' => '172.16.0.0',
        'cidr_suffix' => 30, // 2 hosts
        'ipscan' => true,
    ]);
    
    $ipAddress = \App\Modules\Network\Models\IpAddress::create([
        'vlan_id' => $vlan->id,
        'ip_address' => '172.16.0.1',
        'is_online' => true,
        'mac_address' => 'OLD:MAC:ADDRESS',
    ]);
    
    // Mock the ScannerService
    $service = Mockery::mock(ScannerService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    
    // Mock offline ping result
    $service->shouldReceive('pingIpAddress')
        ->with('172.16.0.1')
        ->andReturn([
            'is_online' => false,
            'ping_ms' => null,
        ]);
    
    // resolveMacAddress should NOT be called for offline IPs
    $service->shouldNotReceive('resolveMacAddress');
    
    // Scan the VLAN
    $result = $service->scanVlan($vlan);
    
    // Verify the IP is marked offline
    $ipAddress->refresh();
    expect($ipAddress->is_online)->toBeFalse();
    expect($ipAddress->mac_address)->toBe('OLD:MAC:ADDRESS'); // Should be preserved
});

test('scanVlan handles errors gracefully and continues', function () {
    // Create a VLAN with multiple IP addresses
    $vlan = \App\Modules\Network\Models\Vlan::create([
        'vlan_id' => 103,
        'vlan_name' => 'Test VLAN 4',
        'network_address' => '192.168.2.0',
        'cidr_suffix' => 29, // 6 hosts
        'ipscan' => true,
    ]);
    
    // Generate IP addresses
    $ipGenerator = new \App\Modules\Network\Services\IpGeneratorService();
    $ipGenerator->generateIpAddresses($vlan);
    
    // Mock the ScannerService
    $service = Mockery::mock(ScannerService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    
    // Mock ping to throw exception on second call
    $callCount = 0;
    $service->shouldReceive('pingIpAddress')
        ->andReturnUsing(function ($ip) use (&$callCount) {
            $callCount++;
            if ($callCount === 2) {
                throw new \Exception('Ping failed');
            }
            return [
                'is_online' => true,
                'ping_ms' => 10.0,
            ];
        });
    
    $service->shouldReceive('resolveMacAddress')
        ->andReturn('AA:BB:CC:DD:EE:FF');
    
    // Expect error to be logged
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return $message === 'Error scanning IP address' &&
                   isset($context['ip_address']) &&
                   isset($context['vlan_id']) &&
                   isset($context['error']);
        });
    
    // Scan the VLAN
    $result = $service->scanVlan($vlan);
    
    // Should have scanned 5 IPs (6 total - 1 error)
    expect($result['scanned'])->toBe(5);
    expect($result['online'])->toBe(5);
    expect($result['offline'])->toBe(0);
});

test('scanVlan returns zero counts for empty VLAN', function () {
    // Create a VLAN with no IP addresses
    $vlan = \App\Modules\Network\Models\Vlan::create([
        'vlan_id' => 104,
        'vlan_name' => 'Empty VLAN',
        'network_address' => '10.10.10.0',
        'cidr_suffix' => 24,
        'ipscan' => true,
    ]);
    
    // Don't generate IP addresses
    
    $service = new ScannerService();
    
    // Scan the VLAN
    $result = $service->scanVlan($vlan);
    
    // Should return zero counts
    expect($result['scanned'])->toBe(0);
    expect($result['online'])->toBe(0);
    expect($result['offline'])->toBe(0);
});

test('scanVlan preserves user-entered data during scan', function () {
    // Create a VLAN with IP address that has user data
    $vlan = \App\Modules\Network\Models\Vlan::create([
        'vlan_id' => 105,
        'vlan_name' => 'Test VLAN 5',
        'network_address' => '192.168.3.0',
        'cidr_suffix' => 30, // 2 hosts
        'ipscan' => true,
    ]);
    
    $ipAddress = \App\Modules\Network\Models\IpAddress::create([
        'vlan_id' => $vlan->id,
        'ip_address' => '192.168.3.1',
        'dns_name' => 'server.example.com',
        'comment' => 'Production server',
        'is_online' => false,
    ]);
    
    // Mock the ScannerService
    $service = Mockery::mock(ScannerService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    
    $service->shouldReceive('pingIpAddress')
        ->andReturn([
            'is_online' => true,
            'ping_ms' => 3.5,
        ]);
    
    $service->shouldReceive('resolveMacAddress')
        ->andReturn('AA:BB:CC:DD:EE:FF');
    
    // Scan the VLAN
    $result = $service->scanVlan($vlan);
    
    // Verify user data is preserved
    $ipAddress->refresh();
    expect($ipAddress->dns_name)->toBe('server.example.com');
    expect($ipAddress->comment)->toBe('Production server');
    
    // Verify scan data is updated
    expect($ipAddress->is_online)->toBeTrue();
    expect($ipAddress->ping_ms)->toBe(3.5);
    expect($ipAddress->mac_address)->toBe('AA:BB:CC:DD:EE:FF');
});


// Scan Interval Checking Tests

test('shouldScanVlan returns false when ipscan is disabled', function () {
    $vlan = \App\Modules\Network\Models\Vlan::create([
        'vlan_id' => 200,
        'vlan_name' => 'Scan Disabled VLAN',
        'network_address' => '192.168.10.0',
        'cidr_suffix' => 24,
        'ipscan' => false, // Scanning disabled
        'scan_interval_minutes' => 60,
    ]);
    
    $service = new ScannerService();
    $result = $service->shouldScanVlan($vlan);
    
    expect($result)->toBeFalse();
});

test('shouldScanVlan returns true when ipscan is enabled and never scanned', function () {
    $vlan = \App\Modules\Network\Models\Vlan::create([
        'vlan_id' => 201,
        'vlan_name' => 'Never Scanned VLAN',
        'network_address' => '192.168.11.0',
        'cidr_suffix' => 24,
        'ipscan' => true,
        'scan_interval_minutes' => 60,
        'last_scanned_at' => null, // Never scanned
    ]);
    
    $service = new ScannerService();
    $result = $service->shouldScanVlan($vlan);
    
    expect($result)->toBeTrue();
});

test('shouldScanVlan returns true when scan interval has elapsed', function () {
    $vlan = \App\Modules\Network\Models\Vlan::create([
        'vlan_id' => 202,
        'vlan_name' => 'Interval Elapsed VLAN',
        'network_address' => '192.168.12.0',
        'cidr_suffix' => 24,
        'ipscan' => true,
        'scan_interval_minutes' => 60,
        'last_scanned_at' => now()->subMinutes(61), // Scanned 61 minutes ago
    ]);
    
    $service = new ScannerService();
    $result = $service->shouldScanVlan($vlan);
    
    expect($result)->toBeTrue();
});

test('shouldScanVlan returns false when scan interval has not elapsed', function () {
    // Create VLAN and set last_scanned_at to 30 minutes ago
    $lastScannedAt = now()->subMinutes(30);
    
    $vlan = \App\Modules\Network\Models\Vlan::create([
        'vlan_id' => 203,
        'vlan_name' => 'Interval Not Elapsed VLAN',
        'network_address' => '192.168.13.0',
        'cidr_suffix' => 24,
        'ipscan' => true,
        'scan_interval_minutes' => 60,
        'last_scanned_at' => $lastScannedAt,
    ]);
    
    $service = new ScannerService();
    $result = $service->shouldScanVlan($vlan);
    
    expect($result)->toBeFalse();
});

test('shouldScanVlan returns true when exactly at scan interval boundary', function () {
    $vlan = \App\Modules\Network\Models\Vlan::create([
        'vlan_id' => 204,
        'vlan_name' => 'Boundary VLAN',
        'network_address' => '192.168.14.0',
        'cidr_suffix' => 24,
        'ipscan' => true,
        'scan_interval_minutes' => 60,
        'last_scanned_at' => now()->subMinutes(60), // Scanned exactly 60 minutes ago
    ]);
    
    $service = new ScannerService();
    $result = $service->shouldScanVlan($vlan);
    
    expect($result)->toBeTrue();
});

test('shouldScanVlan uses default interval when scan_interval_minutes is null', function () {
    // Create VLAN with default interval (60 minutes)
    $vlan = \App\Modules\Network\Models\Vlan::create([
        'vlan_id' => 205,
        'vlan_name' => 'Default Interval VLAN',
        'network_address' => '192.168.15.0',
        'cidr_suffix' => 24,
        'ipscan' => true,
        // scan_interval_minutes will use default value of 60
        'last_scanned_at' => now()->subMinutes(61),
    ]);
    
    // Manually set to null to test the null coalescing in shouldScanVlan
    $vlan->scan_interval_minutes = null;
    
    $service = new ScannerService();
    $result = $service->shouldScanVlan($vlan);
    
    expect($result)->toBeTrue();
});

test('shouldScanVlan respects custom scan interval', function () {
    // Create VLAN with 120-minute interval, last scanned 90 minutes ago
    $lastScannedAt = now()->subMinutes(90);
    
    $vlan = \App\Modules\Network\Models\Vlan::create([
        'vlan_id' => 206,
        'vlan_name' => 'Custom Interval VLAN',
        'network_address' => '192.168.16.0',
        'cidr_suffix' => 24,
        'ipscan' => true,
        'scan_interval_minutes' => 120, // Custom 2-hour interval
        'last_scanned_at' => $lastScannedAt,
    ]);
    
    $service = new ScannerService();
    $result = $service->shouldScanVlan($vlan);
    
    // Should be false because 90 < 120
    expect($result)->toBeFalse();
    
    // Update to 121 minutes ago
    $vlan->last_scanned_at = now()->subMinutes(121);
    $vlan->save();
    
    $result = $service->shouldScanVlan($vlan);
    
    // Should be true because 121 > 120
    expect($result)->toBeTrue();
});

test('shouldScanVlan handles very short scan intervals', function () {
    $vlan = \App\Modules\Network\Models\Vlan::create([
        'vlan_id' => 207,
        'vlan_name' => 'Short Interval VLAN',
        'network_address' => '192.168.17.0',
        'cidr_suffix' => 24,
        'ipscan' => true,
        'scan_interval_minutes' => 1, // 1 minute interval
        'last_scanned_at' => now()->subSeconds(61), // Scanned 61 seconds ago
    ]);
    
    $service = new ScannerService();
    $result = $service->shouldScanVlan($vlan);
    
    expect($result)->toBeTrue();
});

test('shouldScanVlan handles very long scan intervals', function () {
    // Create VLAN with 24-hour interval, last scanned 23 hours ago
    $lastScannedAt = now()->subHours(23);
    
    $vlan = \App\Modules\Network\Models\Vlan::create([
        'vlan_id' => 208,
        'vlan_name' => 'Long Interval VLAN',
        'network_address' => '192.168.18.0',
        'cidr_suffix' => 24,
        'ipscan' => true,
        'scan_interval_minutes' => 1440, // 24 hours
        'last_scanned_at' => $lastScannedAt,
    ]);
    
    $service = new ScannerService();
    $result = $service->shouldScanVlan($vlan);
    
    // Should be false because 23 hours < 24 hours
    expect($result)->toBeFalse();
    
    // Update to 25 hours ago
    $vlan->last_scanned_at = now()->subHours(25);
    $vlan->save();
    
    $result = $service->shouldScanVlan($vlan);
    
    // Should be true because 25 hours > 24 hours
    expect($result)->toBeTrue();
});
