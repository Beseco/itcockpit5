<?php

use App\Models\User;
use App\Modules\Network\Models\IpAddress;
use App\Modules\Network\Models\Vlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Run module migrations
    $this->artisan('migrate', ['--path' => 'app/Modules/Network/Database/Migrations']);
    
    // Create permissions
    Permission::create(['name' => 'module.network.view']);
    Permission::create(['name' => 'module.network.edit']);
    Permission::create(['name' => 'module.example.view']); // Required for sidebar rendering
    
    // Create a user with view permission
    $this->user = User::factory()->create(['role' => 'user']);
    $this->user->givePermissionTo('module.network.view');
    
    // Create test VLANs
    $this->vlan1 = Vlan::create([
        'vlan_id' => 100,
        'vlan_name' => 'Production Network',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
        'gateway' => '192.168.1.1',
    ]);
    
    $this->vlan2 = Vlan::create([
        'vlan_id' => 200,
        'vlan_name' => 'Development Network',
        'network_address' => '10.0.0.0',
        'cidr_suffix' => 24,
        'gateway' => '10.0.0.1',
    ]);
    
    // Create test IP addresses
    $this->ip1 = IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.50',
        'dns_name' => 'web-server.local',
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'is_online' => true,
    ]);
    
    $this->ip2 = IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.51',
        'dns_name' => 'db-server.local',
        'mac_address' => 'AA-BB-CC-DD-EE-11',
        'is_online' => false,
    ]);
    
    $this->ip3 = IpAddress::create([
        'vlan_id' => $this->vlan2->id,
        'ip_address' => '10.0.0.100',
        'dns_name' => 'dev-machine.local',
        'mac_address' => '11:22:33:44:55:66',
        'is_online' => true,
    ]);
});

// Permission Tests
test('index requires module.network.view permission', function () {
    $userWithoutPermission = User::factory()->create(['role' => 'user']);
    
    $response = $this->actingAs($userWithoutPermission)->get(route('network.search', ['q' => 'test']));
    
    $response->assertStatus(403);
    $response->assertSee('Insufficient permissions');
});

test('search ajax requires module.network.view permission', function () {
    $userWithoutPermission = User::factory()->create(['role' => 'user']);
    
    $response = $this->actingAs($userWithoutPermission)->get(route('network.search.ajax', ['q' => 'test']));
    
    $response->assertStatus(403);
});

// Query Validation Tests
test('index displays validation message for queries less than 3 characters', function () {
    $response = $this->actingAs($this->user)->get(route('network.search', ['q' => 'ab']));
    
    $response->assertStatus(200);
    $response->assertViewIs('network::search.index');
    $response->assertViewHas('validationMessage', 'Please enter at least 3 characters');
});

test('search ajax returns error for queries less than 3 characters', function () {
    $response = $this->actingAs($this->user)->get(route('network.search.ajax', ['q' => 'ab']));
    
    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'Please enter at least 3 characters',
    ]);
});

test('index treats whitespace-only queries as empty', function () {
    $response = $this->actingAs($this->user)->get(route('network.search', ['q' => '   ']));
    
    $response->assertStatus(200);
    $response->assertViewIs('network::search.index');
    $response->assertViewHas('query', '');
});

test('search ajax returns empty results for whitespace-only queries', function () {
    $response = $this->actingAs($this->user)->get(route('network.search.ajax', ['q' => '   ']));
    
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'vlans' => [],
        'ipAddresses' => [],
    ]);
});

test('index trims leading and trailing whitespace from queries', function () {
    $response = $this->actingAs($this->user)->get(route('network.search', ['q' => '  Production  ']));
    
    $response->assertStatus(200);
    $response->assertViewHas('query', 'Production');
});

// VLAN Search Tests
test('search finds VLANs by exact VLAN ID', function () {
    $response = $this->actingAs($this->user)->get(route('network.search', ['q' => '100']));
    
    $response->assertStatus(200);
    $vlans = $response->viewData('vlans');
    expect($vlans)->toHaveCount(1);
    expect($vlans->first()->vlan_id)->toBe(100);
});

test('search finds VLANs by partial VLAN name', function () {
    $response = $this->actingAs($this->user)->get(route('network.search', ['q' => 'Production']));
    
    $response->assertStatus(200);
    $vlans = $response->viewData('vlans');
    expect($vlans)->toHaveCount(1);
    expect($vlans->first()->vlan_name)->toBe('Production Network');
});

test('search finds VLANs by partial network address', function () {
    $response = $this->actingAs($this->user)->get(route('network.search', ['q' => '192.168']));
    
    $response->assertStatus(200);
    $vlans = $response->viewData('vlans');
    expect($vlans)->toHaveCount(1);
    expect($vlans->first()->network_address)->toBe('192.168.1.0');
});

test('search is case-insensitive for VLAN names', function () {
    $response = $this->actingAs($this->user)->get(route('network.search', ['q' => 'production']));
    
    $response->assertStatus(200);
    $vlans = $response->viewData('vlans');
    expect($vlans)->toHaveCount(1);
    expect($vlans->first()->vlan_name)->toBe('Production Network');
});

// IP Address Search Tests
test('search finds IP addresses by partial IP', function () {
    $response = $this->actingAs($this->user)->get(route('network.search', ['q' => '192.168.1.5']));
    
    $response->assertStatus(200);
    $ipAddresses = $response->viewData('ipAddresses');
    expect($ipAddresses)->toHaveCount(2); // Both 192.168.1.50 and 192.168.1.51
});

test('search finds IP addresses by DNS name', function () {
    $response = $this->actingAs($this->user)->get(route('network.search', ['q' => 'web-server']));
    
    $response->assertStatus(200);
    $ipAddresses = $response->viewData('ipAddresses');
    expect($ipAddresses)->toHaveCount(1);
    expect($ipAddresses->first()->dns_name)->toBe('web-server.local');
});

test('search is case-insensitive for DNS names', function () {
    $response = $this->actingAs($this->user)->get(route('network.search', ['q' => 'WEB-SERVER']));
    
    $response->assertStatus(200);
    $ipAddresses = $response->viewData('ipAddresses');
    expect($ipAddresses)->toHaveCount(1);
    expect($ipAddresses->first()->dns_name)->toBe('web-server.local');
});

// MAC Address Search Tests
test('search finds IP addresses by MAC address with colons', function () {
    $response = $this->actingAs($this->user)->get(route('network.search', ['q' => 'AA:BB:CC']));
    
    $response->assertStatus(200);
    $ipAddresses = $response->viewData('ipAddresses');
    expect($ipAddresses->count())->toBeGreaterThanOrEqual(1);
    expect($ipAddresses->pluck('mac_address')->contains('AA:BB:CC:DD:EE:FF'))->toBeTrue();
});

test('search finds IP addresses by MAC address with hyphens', function () {
    $response = $this->actingAs($this->user)->get(route('network.search', ['q' => 'AA-BB-CC']));
    
    $response->assertStatus(200);
    $ipAddresses = $response->viewData('ipAddresses');
    expect($ipAddresses->count())->toBeGreaterThanOrEqual(1);
});

test('search normalizes MAC address format for matching', function () {
    // Search with colons should match MAC stored with hyphens
    $response = $this->actingAs($this->user)->get(route('network.search', ['q' => 'AA:BB:CC:DD:EE:11']));
    
    $response->assertStatus(200);
    $ipAddresses = $response->viewData('ipAddresses');
    expect($ipAddresses)->toHaveCount(1);
    expect($ipAddresses->first()->mac_address)->toBe('AA-BB-CC-DD-EE-11');
});

// Result Limit Tests
test('search limits VLAN results to 50', function () {
    // Create 60 VLANs
    for ($i = 1; $i <= 60; $i++) {
        Vlan::create([
            'vlan_id' => 300 + $i,
            'vlan_name' => "Test VLAN {$i}",
            'network_address' => "172.16.{$i}.0",
            'cidr_suffix' => 24,
        ]);
    }
    
    $response = $this->actingAs($this->user)->get(route('network.search', ['q' => 'Test']));
    
    $response->assertStatus(200);
    $vlans = $response->viewData('vlans');
    expect($vlans)->toHaveCount(50);
});

test('search limits IP address results to 50', function () {
    // Create 60 IP addresses
    for ($i = 1; $i <= 60; $i++) {
        IpAddress::create([
            'vlan_id' => $this->vlan1->id,
            'ip_address' => "192.168.2.{$i}",
            'dns_name' => "test-host-{$i}.local",
        ]);
    }
    
    $response = $this->actingAs($this->user)->get(route('network.search', ['q' => 'test-host']));
    
    $response->assertStatus(200);
    $ipAddresses = $response->viewData('ipAddresses');
    expect($ipAddresses)->toHaveCount(50);
});

// AJAX Endpoint Tests
test('search ajax returns JSON with VLAN results', function () {
    $response = $this->actingAs($this->user)->get(route('network.search.ajax', ['q' => 'Production']));
    
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);
    
    $data = $response->json();
    expect($data['vlans'])->toHaveCount(1);
    expect($data['vlans'][0])->toHaveKeys(['id', 'vlan_id', 'vlan_name', 'network_address', 'online_count', 'total_ip_count', 'url']);
});

test('search ajax returns JSON with IP address results including VLAN context', function () {
    $response = $this->actingAs($this->user)->get(route('network.search.ajax', ['q' => 'web-server']));
    
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);
    
    $data = $response->json();
    expect($data['ipAddresses'])->toHaveCount(1);
    expect($data['ipAddresses'][0])->toHaveKeys([
        'id', 'ip_address', 'dns_name', 'mac_address', 'is_online', 'status_text',
        'vlan_id', 'vlan_name', 'url', 'vlan_url'
    ]);
    expect($data['ipAddresses'][0]['vlan_name'])->toBe('Production Network');
});

test('search ajax eager loads VLAN relationships for IP addresses', function () {
    $response = $this->actingAs($this->user)->get(route('network.search.ajax', ['q' => 'web-server']));
    
    $response->assertStatus(200);
    $data = $response->json();
    
    // Verify VLAN context is included
    expect($data['ipAddresses'][0]['vlan_id'])->toBe(100);
    expect($data['ipAddresses'][0]['vlan_name'])->toBe('Production Network');
});

test('search ajax formats MAC addresses correctly', function () {
    $response = $this->actingAs($this->user)->get(route('network.search.ajax', ['q' => 'web-server']));
    
    $response->assertStatus(200);
    $data = $response->json();
    
    // MAC address should be formatted with colons and uppercase
    expect($data['ipAddresses'][0]['mac_address'])->toBe('AA:BB:CC:DD:EE:FF');
});

// Empty Results Tests
test('search returns empty collections when no matches found', function () {
    $response = $this->actingAs($this->user)->get(route('network.search', ['q' => 'nonexistent']));
    
    $response->assertStatus(200);
    $vlans = $response->viewData('vlans');
    $ipAddresses = $response->viewData('ipAddresses');
    expect($vlans)->toHaveCount(0);
    expect($ipAddresses)->toHaveCount(0);
});

// XSS Prevention Tests
test('search sanitizes query to prevent XSS', function () {
    $maliciousQuery = '<script>alert("xss")</script>';
    
    $response = $this->actingAs($this->user)->get(route('network.search', ['q' => $maliciousQuery]));
    
    $response->assertStatus(200);
    $query = $response->viewData('query');
    
    // Query should be HTML-escaped
    expect($query)->not->toContain('<script>');
    expect($query)->toContain('&lt;script&gt;');
});

// Query Length Tests
test('search truncates queries longer than 255 characters', function () {
    $longQuery = str_repeat('a', 300);
    
    $response = $this->actingAs($this->user)->get(route('network.search', ['q' => $longQuery]));
    
    $response->assertStatus(200);
    $query = $response->viewData('query');
    expect(strlen($query))->toBeLessThanOrEqual(255);
});
