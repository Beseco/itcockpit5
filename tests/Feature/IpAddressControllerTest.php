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
    
    // Create test VLAN
    $this->vlan = Vlan::create([
        'vlan_id' => 100,
        'vlan_name' => 'Test VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
        'gateway' => '192.168.1.1',
        'dhcp_from' => '192.168.1.100',
        'dhcp_to' => '192.168.1.200',
    ]);
    
    // Create test IP addresses
    $this->ipAddress = IpAddress::create([
        'vlan_id' => $this->vlan->id,
        'ip_address' => '192.168.1.50',
        'dns_name' => 'test-server.local',
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'is_online' => true,
        'last_online_at' => now(),
        'last_scanned_at' => now(),
        'ping_ms' => 5.2,
        'comment' => 'Test comment',
    ]);
});

// Show Method Tests
test('show displays IP address detail page with all information', function () {
    $response = $this->actingAs($this->user)->get(route('network.ip-addresses.show', $this->ipAddress));
    
    $response->assertStatus(200);
    $response->assertViewIs('network::ip-addresses.show');
    $response->assertViewHas('ipAddress');
    $response->assertViewHas('previousIp');
    $response->assertViewHas('nextIp');
    $response->assertViewHas('isInDhcpRange');
    
    // Verify IP address data is present
    $response->assertSee($this->ipAddress->ip_address);
    $response->assertSee($this->ipAddress->dns_name);
    $response->assertSee($this->ipAddress->comment);
});

test('show loads IP address with VLAN relationship', function () {
    $response = $this->actingAs($this->user)->get(route('network.ip-addresses.show', $this->ipAddress));
    
    $ipAddress = $response->viewData('ipAddress');
    expect($ipAddress->relationLoaded('vlan'))->toBeTrue();
    expect($ipAddress->vlan->vlan_name)->toBe('Test VLAN');
});

test('show calculates previous and next IP addresses', function () {
    // Create additional IP addresses
    $previousIp = IpAddress::create([
        'vlan_id' => $this->vlan->id,
        'ip_address' => '192.168.1.40',
    ]);
    
    $nextIp = IpAddress::create([
        'vlan_id' => $this->vlan->id,
        'ip_address' => '192.168.1.60',
    ]);
    
    $response = $this->actingAs($this->user)->get(route('network.ip-addresses.show', $this->ipAddress));
    
    $viewPreviousIp = $response->viewData('previousIp');
    $viewNextIp = $response->viewData('nextIp');
    
    expect($viewPreviousIp->id)->toBe($previousIp->id);
    expect($viewNextIp->id)->toBe($nextIp->id);
});

test('show determines DHCP range membership correctly', function () {
    // Create IP in DHCP range
    $dhcpIp = IpAddress::create([
        'vlan_id' => $this->vlan->id,
        'ip_address' => '192.168.1.150', // Within 192.168.1.100-200
    ]);
    
    $response = $this->actingAs($this->user)->get(route('network.ip-addresses.show', $dhcpIp));
    
    $isInDhcpRange = $response->viewData('isInDhcpRange');
    expect($isInDhcpRange)->toBeTrue();
});

test('show requires authentication', function () {
    $response = $this->get(route('network.ip-addresses.show', $this->ipAddress));
    
    $response->assertRedirect(route('login'));
});

test('show requires module.network.view permission', function () {
    $userWithoutPermission = User::factory()->create(['role' => 'user']);
    
    $response = $this->actingAs($userWithoutPermission)->get(route('network.ip-addresses.show', $this->ipAddress));
    
    $response->assertStatus(403);
});

test('show allows super-admin without explicit permission', function () {
    $superAdmin = User::factory()->create(['role' => 'super-admin']);
    
    $response = $this->actingAs($superAdmin)->get(route('network.ip-addresses.show', $this->ipAddress));
    
    $response->assertStatus(200);
});

test('show returns 404 for non-existent IP address', function () {
    $response = $this->actingAs($this->user)->get(route('network.ip-addresses.show', 99999));
    
    $response->assertStatus(404);
});

// Update Method Tests
test('update successfully updates DNS name and comment', function () {
    $this->user->givePermissionTo('module.network.edit');
    
    $response = $this->actingAs($this->user)
        ->putJson(route('network.ip-addresses.update', $this->ipAddress), [
            'dns_name' => 'updated-server.local',
            'comment' => 'Updated comment',
        ]);
    
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'IP address updated successfully.',
    ]);
    
    $this->ipAddress->refresh();
    expect($this->ipAddress->dns_name)->toBe('updated-server.local');
    expect($this->ipAddress->comment)->toBe('Updated comment');
});

test('update preserves scan-related fields', function () {
    $this->user->givePermissionTo('module.network.edit');
    
    $originalIsOnline = $this->ipAddress->is_online;
    $originalLastOnlineAt = $this->ipAddress->last_online_at;
    $originalLastScannedAt = $this->ipAddress->last_scanned_at;
    $originalPingMs = $this->ipAddress->ping_ms;
    $originalMacAddress = $this->ipAddress->mac_address;
    
    $response = $this->actingAs($this->user)
        ->putJson(route('network.ip-addresses.update', $this->ipAddress), [
            'dns_name' => 'updated-server.local',
        ]);
    
    $response->assertStatus(200);
    
    $this->ipAddress->refresh();
    expect($this->ipAddress->is_online)->toBe($originalIsOnline);
    expect($this->ipAddress->last_online_at->timestamp)->toBe($originalLastOnlineAt->timestamp);
    expect($this->ipAddress->last_scanned_at->timestamp)->toBe($originalLastScannedAt->timestamp);
    expect($this->ipAddress->ping_ms)->toBe($originalPingMs);
    expect($this->ipAddress->mac_address)->toBe($originalMacAddress);
});

test('update returns JSON for AJAX requests', function () {
    $this->user->givePermissionTo('module.network.edit');
    
    $response = $this->actingAs($this->user)
        ->putJson(route('network.ip-addresses.update', $this->ipAddress), [
            'dns_name' => 'updated-server.local',
        ]);
    
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => [
            'id' => $this->ipAddress->id,
            'dns_name' => 'updated-server.local',
        ],
    ]);
});

test('update returns redirect for form submissions', function () {
    $this->user->givePermissionTo('module.network.edit');
    
    $response = $this->actingAs($this->user)
        ->put(route('network.ip-addresses.update', $this->ipAddress), [
            'dns_name' => 'updated-server.local',
        ]);
    
    $response->assertRedirect(route('network.ip-addresses.show', $this->ipAddress));
    $response->assertSessionHas('success', 'IP address updated successfully.');
});

test('update validates DNS name format', function () {
    $this->user->givePermissionTo('module.network.edit');
    
    $response = $this->actingAs($this->user)
        ->putJson(route('network.ip-addresses.update', $this->ipAddress), [
            'dns_name' => 'invalid@server!.local',
        ]);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('dns_name');
});

test('update validates comment length', function () {
    $this->user->givePermissionTo('module.network.edit');
    
    $response = $this->actingAs($this->user)
        ->putJson(route('network.ip-addresses.update', $this->ipAddress), [
            'comment' => str_repeat('a', 1001), // Exceeds 1000 character limit
        ]);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('comment');
});

test('update requires module.network.edit permission', function () {
    $response = $this->actingAs($this->user)
        ->putJson(route('network.ip-addresses.update', $this->ipAddress), [
            'dns_name' => 'updated-server.local',
        ]);
    
    $response->assertStatus(403);
});

test('update logs action to audit log', function () {
    $this->user->givePermissionTo('module.network.edit');
    
    $response = $this->actingAs($this->user)
        ->putJson(route('network.ip-addresses.update', $this->ipAddress), [
            'dns_name' => 'updated-server.local',
            'comment' => 'Updated comment',
        ]);
    
    $response->assertStatus(200);
    
    // Verify audit log entry was created
    $this->assertDatabaseHas('audit_logs', [
        'module' => 'Network',
        'action' => 'IP address updated',
    ]);
});

test('update allows null values for DNS name and comment', function () {
    $this->user->givePermissionTo('module.network.edit');
    
    $response = $this->actingAs($this->user)
        ->putJson(route('network.ip-addresses.update', $this->ipAddress), [
            'dns_name' => null,
            'comment' => null,
        ]);
    
    $response->assertStatus(200);
    
    $this->ipAddress->refresh();
    expect($this->ipAddress->dns_name)->toBeNull();
    expect($this->ipAddress->comment)->toBeNull();
});
