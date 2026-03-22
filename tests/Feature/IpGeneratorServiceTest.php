<?php

use App\Modules\Network\Models\IpAddress;
use App\Modules\Network\Models\Vlan;
use App\Modules\Network\Services\IpGeneratorService;
use App\Modules\Network\Providers\NetworkServiceProvider;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    // Register the Network module provider to load migrations
    $provider = new NetworkServiceProvider($this->app);
    $provider->register();
    $provider->boot();
    
    // Run migrations
    Artisan::call('migrate');
    
    $this->service = new IpGeneratorService();
});

test('generates 254 IP addresses for /24 subnet', function () {
    $vlan = Vlan::create([
        'vlan_id' => 100,
        'vlan_name' => 'Test VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
    ]);

    $count = $this->service->generateIpAddresses($vlan);

    expect($count)->toBe(254);
    expect($vlan->ipAddresses()->count())->toBe(254);
    
    // Verify first and last IP addresses by converting to long for proper sorting
    $allIps = $vlan->ipAddresses()->get()->sortBy(function ($ip) {
        return ip2long($ip->ip_address);
    });
    
    $firstIp = $allIps->first();
    $lastIp = $allIps->last();
    
    expect($firstIp->ip_address)->toBe('192.168.1.1');
    expect($lastIp->ip_address)->toBe('192.168.1.254');
    
    // Verify network and broadcast addresses are excluded
    $allIpStrings = $vlan->ipAddresses()->pluck('ip_address')->toArray();
    expect($allIpStrings)->not->toContain('192.168.1.0');
    expect($allIpStrings)->not->toContain('192.168.1.255');
});

test('generates 2 IP addresses for /31 subnet', function () {
    $vlan = Vlan::create([
        'vlan_id' => 101,
        'vlan_name' => 'Point-to-Point',
        'network_address' => '10.0.0.0',
        'cidr_suffix' => 31,
    ]);

    $count = $this->service->generateIpAddresses($vlan);

    expect($count)->toBe(2);
    expect($vlan->ipAddresses()->count())->toBe(2);
    
    $ips = $vlan->ipAddresses()->pluck('ip_address')->toArray();
    expect($ips)->toContain('10.0.0.0');
    expect($ips)->toContain('10.0.0.1');
});

test('generates 1 IP address for /32 subnet', function () {
    $vlan = Vlan::create([
        'vlan_id' => 102,
        'vlan_name' => 'Single Host',
        'network_address' => '172.16.0.1',
        'cidr_suffix' => 32,
    ]);

    $count = $this->service->generateIpAddresses($vlan);

    expect($count)->toBe(1);
    expect($vlan->ipAddresses()->count())->toBe(1);
    
    $ip = $vlan->ipAddresses()->first();
    expect($ip->ip_address)->toBe('172.16.0.1');
});

test('generates 2 IP addresses for /30 subnet', function () {
    $vlan = Vlan::create([
        'vlan_id' => 103,
        'vlan_name' => 'Small Subnet',
        'network_address' => '192.168.100.0',
        'cidr_suffix' => 30,
    ]);

    $count = $this->service->generateIpAddresses($vlan);

    expect($count)->toBe(2);
    expect($vlan->ipAddresses()->count())->toBe(2);
    
    $ips = $vlan->ipAddresses()->pluck('ip_address')->toArray();
    expect($ips)->toContain('192.168.100.1');
    expect($ips)->toContain('192.168.100.2');
    expect($ips)->not->toContain('192.168.100.0'); // network
    expect($ips)->not->toContain('192.168.100.3'); // broadcast
});

test('calculateSubnetInfo returns correct information for /24 subnet', function () {
    $info = $this->service->calculateSubnetInfo('192.168.1.0', 24);

    expect($info['network'])->toBe('192.168.1.0');
    expect($info['broadcast'])->toBe('192.168.1.255');
    expect($info['first_host'])->toBe('192.168.1.1');
    expect($info['last_host'])->toBe('192.168.1.254');
    expect($info['host_count'])->toBe(254);
});

test('calculateSubnetInfo returns correct information for /31 subnet', function () {
    $info = $this->service->calculateSubnetInfo('10.0.0.0', 31);

    expect($info['network'])->toBe('10.0.0.0');
    expect($info['broadcast'])->toBe('10.0.0.1');
    expect($info['first_host'])->toBe('10.0.0.0');
    expect($info['last_host'])->toBe('10.0.0.1');
    expect($info['host_count'])->toBe(2);
});

test('calculateSubnetInfo returns correct information for /32 subnet', function () {
    $info = $this->service->calculateSubnetInfo('172.16.0.1', 32);

    expect($info['network'])->toBe('172.16.0.1');
    expect($info['broadcast'])->toBe('172.16.0.1');
    expect($info['first_host'])->toBe('172.16.0.1');
    expect($info['last_host'])->toBe('172.16.0.1');
    expect($info['host_count'])->toBe(1);
});

test('handles invalid IP address gracefully', function () {
    $vlan = Vlan::create([
        'vlan_id' => 104,
        'vlan_name' => 'Invalid IP',
        'network_address' => 'invalid.ip.address',
        'cidr_suffix' => 24,
    ]);

    $count = $this->service->generateIpAddresses($vlan);

    expect($count)->toBe(0);
    expect($vlan->ipAddresses()->count())->toBe(0);
});

test('calculateSubnetInfo handles invalid IP address', function () {
    $info = $this->service->calculateSubnetInfo('invalid.ip', 24);

    expect($info['network'])->toBeNull();
    expect($info['broadcast'])->toBeNull();
    expect($info['first_host'])->toBeNull();
    expect($info['last_host'])->toBeNull();
    expect($info['host_count'])->toBe(0);
});
