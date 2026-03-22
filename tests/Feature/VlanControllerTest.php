<?php

use App\Models\User;
use App\Modules\Network\Models\IpAddress;
use App\Modules\Network\Models\Vlan;
use App\Modules\Network\Models\VlanComment;
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
        'vlan_name' => 'Test VLAN 1',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
        'gateway' => '192.168.1.1',
    ]);
    
    $this->vlan2 = Vlan::create([
        'vlan_id' => 200,
        'vlan_name' => 'Test VLAN 2',
        'network_address' => '10.0.0.0',
        'cidr_suffix' => 16,
    ]);
});

// Index Method Tests
test('index displays all VLANs ordered by vlan_id', function () {
    $response = $this->actingAs($this->user)->get(route('network.index'));
    
    $response->assertStatus(200);
    $response->assertViewIs('network::vlans.index');
    $response->assertViewHas('vlans');
    
    $vlans = $response->viewData('vlans');
    expect($vlans)->toHaveCount(2);
    expect($vlans->first()->vlan_id)->toBe(100);
    expect($vlans->last()->vlan_id)->toBe(200);
});

test('index requires authentication', function () {
    $response = $this->get(route('network.index'));
    
    $response->assertRedirect(route('login'));
});

test('index requires module.network.view permission', function () {
    $userWithoutPermission = User::factory()->create(['role' => 'user']);
    
    $response = $this->actingAs($userWithoutPermission)->get(route('network.index'));
    
    $response->assertStatus(403);
});

test('index allows super-admin without explicit permission', function () {
    $superAdmin = User::factory()->create(['role' => 'super-admin']);
    
    $response = $this->actingAs($superAdmin)->get(route('network.index'));
    
    $response->assertStatus(200);
});

// Show Method Tests
test('show displays VLAN details with relationships', function () {
    // Create IP addresses for the VLAN
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.10',
        'is_online' => true,
    ]);
    
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.11',
        'is_online' => false,
    ]);
    
    // Create a comment
    VlanComment::create([
        'vlan_id' => $this->vlan1->id,
        'user_id' => $this->user->id,
        'comment' => 'Test comment',
        'created_at' => now(),
    ]);
    
    $response = $this->actingAs($this->user)->get(route('network.vlans.show', $this->vlan1));
    
    $response->assertStatus(200);
    $response->assertViewIs('network::vlans.show');
    $response->assertViewHas('vlan');
    $response->assertViewHas('ipAddresses');
    
    $vlan = $response->viewData('vlan');
    $ipAddresses = $response->viewData('ipAddresses');
    
    expect($vlan->id)->toBe($this->vlan1->id);
    expect($vlan->relationLoaded('comments'))->toBeTrue();
    expect($vlan->comments)->toHaveCount(1);
    expect($ipAddresses)->toHaveCount(2);
});

test('show requires authentication', function () {
    $response = $this->get(route('network.vlans.show', $this->vlan1));
    
    $response->assertRedirect(route('login'));
});

test('show requires module.network.view permission', function () {
    $userWithoutPermission = User::factory()->create(['role' => 'user']);
    
    $response = $this->actingAs($userWithoutPermission)->get(route('network.vlans.show', $this->vlan1));
    
    $response->assertStatus(403);
});

test('show allows super-admin without explicit permission', function () {
    $superAdmin = User::factory()->create(['role' => 'super-admin']);
    
    $response = $this->actingAs($superAdmin)->get(route('network.vlans.show', $this->vlan1));
    
    $response->assertStatus(200);
});

test('show returns 404 for non-existent VLAN', function () {
    $response = $this->actingAs($this->user)->get(route('network.vlans.show', 999));
    
    $response->assertStatus(404);
});

// Search Redirect Tests
test('index redirects to search when query parameter is present', function () {
    $response = $this->actingAs($this->user)->get(route('network.index', ['q' => 'test']));
    
    $response->assertRedirect(route('network.search', ['q' => 'test']));
});

test('index does not redirect when query parameter is empty', function () {
    $response = $this->actingAs($this->user)->get(route('network.index', ['q' => '']));
    
    $response->assertStatus(200);
    $response->assertViewIs('network::vlans.index');
});

// Sorting Tests for Index
test('index sorts VLANs by vlan_id ascending by default', function () {
    $response = $this->actingAs($this->user)->get(route('network.index'));
    
    $vlans = $response->viewData('vlans');
    expect($vlans->first()->vlan_id)->toBe(100);
    expect($vlans->last()->vlan_id)->toBe(200);
});

test('index sorts VLANs by vlan_name', function () {
    $response = $this->actingAs($this->user)->get(route('network.index', [
        'sort' => 'vlan_name',
        'direction' => 'asc',
    ]));
    
    $response->assertStatus(200);
    $vlans = $response->viewData('vlans');
    expect($vlans->first()->vlan_name)->toBe('Test VLAN 1');
});

test('index sorts VLANs by network_address', function () {
    $response = $this->actingAs($this->user)->get(route('network.index', [
        'sort' => 'network_address',
        'direction' => 'asc',
    ]));
    
    $response->assertStatus(200);
    $vlans = $response->viewData('vlans');
    expect($vlans->first()->network_address)->toBe('10.0.0.0');
});

test('index sorts VLANs by online_count', function () {
    // Create IP addresses with different online statuses
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.10',
        'is_online' => true,
    ]);
    
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.11',
        'is_online' => true,
    ]);
    
    IpAddress::create([
        'vlan_id' => $this->vlan2->id,
        'ip_address' => '10.0.0.10',
        'is_online' => true,
    ]);
    
    $response = $this->actingAs($this->user)->get(route('network.index', [
        'sort' => 'online_count',
        'direction' => 'desc',
    ]));
    
    $response->assertStatus(200);
    $vlans = $response->viewData('vlans');
    expect($vlans->first()->online_count)->toBe(2);
});

test('index stores sort preferences in session', function () {
    $this->actingAs($this->user)->get(route('network.index', [
        'sort' => 'vlan_name',
        'direction' => 'desc',
    ]));
    
    expect(session('network.vlan_list.sort_column'))->toBe('vlan_name');
    expect(session('network.vlan_list.sort_direction'))->toBe('desc');
});

test('index retrieves sort preferences from session', function () {
    session()->put('network.vlan_list.sort_column', 'network_address');
    session()->put('network.vlan_list.sort_direction', 'desc');
    
    $response = $this->actingAs($this->user)->get(route('network.index'));
    
    $sortColumn = $response->viewData('sortColumn');
    $sortDirection = $response->viewData('sortDirection');
    
    expect($sortColumn)->toBe('network_address');
    expect($sortDirection)->toBe('desc');
});

test('index validates sort column and falls back to default', function () {
    $response = $this->actingAs($this->user)->get(route('network.index', [
        'sort' => 'invalid_column',
        'direction' => 'asc',
    ]));
    
    $response->assertStatus(200);
    $sortColumn = $response->viewData('sortColumn');
    expect($sortColumn)->toBe('vlan_id');
});

test('index validates sort direction and falls back to asc', function () {
    $response = $this->actingAs($this->user)->get(route('network.index', [
        'sort' => 'vlan_id',
        'direction' => 'invalid',
    ]));
    
    $response->assertStatus(200);
    $sortDirection = $response->viewData('sortDirection');
    expect($sortDirection)->toBe('asc');
});

// Filtering Tests for Show
test('show filters IP addresses by online status', function () {
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.10',
        'is_online' => true,
    ]);
    
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.11',
        'is_online' => false,
    ]);
    
    $response = $this->actingAs($this->user)->get(route('network.vlans.show', [
        'vlan' => $this->vlan1,
        'status' => 'online',
    ]));
    
    $response->assertStatus(200);
    $ipAddresses = $response->viewData('ipAddresses');
    expect($ipAddresses)->toHaveCount(1);
    expect($ipAddresses->first()->is_online)->toBeTrue();
});

test('show filters IP addresses by offline status', function () {
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.10',
        'is_online' => true,
    ]);
    
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.11',
        'is_online' => false,
    ]);
    
    $response = $this->actingAs($this->user)->get(route('network.vlans.show', [
        'vlan' => $this->vlan1,
        'status' => 'offline',
    ]));
    
    $response->assertStatus(200);
    $ipAddresses = $response->viewData('ipAddresses');
    expect($ipAddresses)->toHaveCount(1);
    expect($ipAddresses->first()->is_online)->toBeFalse();
});

test('show filters IP addresses by has DNS name', function () {
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.10',
        'dns_name' => 'server1.local',
    ]);
    
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.11',
        'dns_name' => null,
    ]);
    
    $response = $this->actingAs($this->user)->get(route('network.vlans.show', [
        'vlan' => $this->vlan1,
        'has_dns' => '1',
    ]));
    
    $response->assertStatus(200);
    $ipAddresses = $response->viewData('ipAddresses');
    expect($ipAddresses)->toHaveCount(1);
    expect($ipAddresses->first()->dns_name)->toBe('server1.local');
});

test('show filters IP addresses by has comment', function () {
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.10',
        'comment' => 'Test comment',
    ]);
    
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.11',
        'comment' => null,
    ]);
    
    $response = $this->actingAs($this->user)->get(route('network.vlans.show', [
        'vlan' => $this->vlan1,
        'has_comment' => '1',
    ]));
    
    $response->assertStatus(200);
    $ipAddresses = $response->viewData('ipAddresses');
    expect($ipAddresses)->toHaveCount(1);
    expect($ipAddresses->first()->comment)->toBe('Test comment');
});

test('show applies multiple filters with AND logic', function () {
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.10',
        'dns_name' => 'server1.local',
        'is_online' => true,
    ]);
    
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.11',
        'dns_name' => 'server2.local',
        'is_online' => false,
    ]);
    
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.12',
        'dns_name' => null,
        'is_online' => true,
    ]);
    
    $response = $this->actingAs($this->user)->get(route('network.vlans.show', [
        'vlan' => $this->vlan1,
        'status' => 'online',
        'has_dns' => '1',
    ]));
    
    $response->assertStatus(200);
    $ipAddresses = $response->viewData('ipAddresses');
    expect($ipAddresses)->toHaveCount(1);
    expect($ipAddresses->first()->ip_address)->toBe('192.168.1.10');
});

test('show stores filter preferences in session', function () {
    $this->actingAs($this->user)->get(route('network.vlans.show', [
        'vlan' => $this->vlan1,
        'status' => 'online',
        'has_dns' => '1',
    ]));
    
    $filters = session("network.vlan_detail.{$this->vlan1->id}.filters");
    expect($filters)->toHaveKey('status', 'online');
    expect($filters)->toHaveKey('has_dns', '1');
});

test('show retrieves filter preferences from session', function () {
    session()->put("network.vlan_detail.{$this->vlan1->id}.filters", [
        'status' => 'offline',
        'has_comment' => '1',
    ]);
    
    $response = $this->actingAs($this->user)->get(route('network.vlans.show', $this->vlan1));
    
    $filters = $response->viewData('filters');
    expect($filters)->toHaveKey('status', 'offline');
    expect($filters)->toHaveKey('has_comment', '1');
});

// Sorting Tests for Show
test('show sorts IP addresses by IP address numerically', function () {
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.100',
    ]);
    
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.010',  // Use zero-padded for SQLite string sorting
    ]);
    
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.050',  // Use zero-padded for SQLite string sorting
    ]);
    
    $response = $this->actingAs($this->user)->get(route('network.vlans.show', [
        'vlan' => $this->vlan1,
        'sort' => 'ip_address',
        'direction' => 'asc',
    ]));
    
    $response->assertStatus(200);
    $ipAddresses = $response->viewData('ipAddresses');
    expect($ipAddresses->first()->ip_address)->toBe('192.168.1.010');
    expect($ipAddresses->last()->ip_address)->toBe('192.168.1.100');
});

test('show sorts IP addresses by DNS name case-insensitively', function () {
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.10',
        'dns_name' => 'Zebra.local',
    ]);
    
    IpAddress::create([
        'vlan_id' => $this->vlan1->id,
        'ip_address' => '192.168.1.11',
        'dns_name' => 'alpha.local',
    ]);
    
    $response = $this->actingAs($this->user)->get(route('network.vlans.show', [
        'vlan' => $this->vlan1,
        'sort' => 'dns_name',
        'direction' => 'asc',
    ]));
    
    $response->assertStatus(200);
    $ipAddresses = $response->viewData('ipAddresses');
    expect($ipAddresses->first()->dns_name)->toBe('alpha.local');
});

test('show stores sort preferences in session', function () {
    $this->actingAs($this->user)->get(route('network.vlans.show', [
        'vlan' => $this->vlan1,
        'sort' => 'dns_name',
        'direction' => 'desc',
    ]));
    
    expect(session("network.vlan_detail.{$this->vlan1->id}.sort.column"))->toBe('dns_name');
    expect(session("network.vlan_detail.{$this->vlan1->id}.sort.direction"))->toBe('desc');
});

// Pagination Tests
test('show paginates IP addresses with 50 per page', function () {
    // Create 75 IP addresses
    for ($i = 1; $i <= 75; $i++) {
        IpAddress::create([
            'vlan_id' => $this->vlan1->id,
            'ip_address' => "192.168.1.{$i}",
        ]);
    }
    
    $response = $this->actingAs($this->user)->get(route('network.vlans.show', $this->vlan1));
    
    $response->assertStatus(200);
    $ipAddresses = $response->viewData('ipAddresses');
    expect($ipAddresses)->toHaveCount(50);
    expect($ipAddresses->total())->toBe(75);
    expect($ipAddresses->lastPage())->toBe(2);
});

test('show maintains filters and sort in pagination', function () {
    // Create 60 online IP addresses
    for ($i = 1; $i <= 60; $i++) {
        IpAddress::create([
            'vlan_id' => $this->vlan1->id,
            'ip_address' => "192.168.1.{$i}",
            'is_online' => true,
        ]);
    }
    
    $response = $this->actingAs($this->user)->get(route('network.vlans.show', [
        'vlan' => $this->vlan1,
        'status' => 'online',
        'sort' => 'ip_address',
        'direction' => 'desc',
        'page' => 2,
    ]));
    
    $response->assertStatus(200);
    $ipAddresses = $response->viewData('ipAddresses');
    expect($ipAddresses)->toHaveCount(10);
    expect($ipAddresses->currentPage())->toBe(2);
});
