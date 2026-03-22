<?php

use App\Models\User;
use App\Modules\Network\Models\Vlan;
use App\Modules\Network\Models\IpAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard widget displays online device count and total IP count', function () {
    $user = User::factory()->create(['role' => 'super-admin', 'is_active' => true]);

    // Create a VLAN
    $vlan = Vlan::create([
        'vlan_id' => 10,
        'vlan_name' => 'Test VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
        'ipscan' => true,
    ]);

    // Create some IP addresses
    IpAddress::create([
        'vlan_id' => $vlan->id,
        'ip_address' => '192.168.1.1',
        'is_online' => true,
        'last_scanned_at' => now(),
    ]);

    IpAddress::create([
        'vlan_id' => $vlan->id,
        'ip_address' => '192.168.1.2',
        'is_online' => true,
        'last_scanned_at' => now(),
    ]);

    IpAddress::create([
        'vlan_id' => $vlan->id,
        'ip_address' => '192.168.1.3',
        'is_online' => false,
        'last_scanned_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Network Status');
    $response->assertSee('Online Devices');
    $response->assertSee('2'); // 2 online devices
    $response->assertSee('Monitored IPs');
    $response->assertSee('3'); // 3 total IPs
    $response->assertSee('View Networks');
});

test('dashboard widget displays no network data message when no VLANs exist', function () {
    $user = User::factory()->create(['role' => 'super-admin', 'is_active' => true]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Network Status');
    $response->assertSee('No network data');
});

test('dashboard widget shows create VLAN button for users with edit permission', function () {
    $user = User::factory()->create(['role' => 'super-admin', 'is_active' => true]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Create VLAN');
});

test('dashboard widget counts only online devices', function () {
    $user = User::factory()->create(['role' => 'super-admin', 'is_active' => true]);

    $vlan = Vlan::create([
        'vlan_id' => 10,
        'vlan_name' => 'Test VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
    ]);

    // Create 5 IPs, only 2 online
    for ($i = 1; $i <= 5; $i++) {
        IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => "192.168.1.$i",
            'is_online' => $i <= 2, // Only first 2 are online
            'last_scanned_at' => now(),
        ]);
    }

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('2'); // 2 online devices
    $response->assertSee('5'); // 5 total IPs
});

test('dashboard widget counts IPs across multiple VLANs', function () {
    $user = User::factory()->create(['role' => 'super-admin', 'is_active' => true]);

    // Create first VLAN with 3 IPs (2 online)
    $vlan1 = Vlan::create([
        'vlan_id' => 10,
        'vlan_name' => 'VLAN 10',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
    ]);

    IpAddress::create(['vlan_id' => $vlan1->id, 'ip_address' => '192.168.1.1', 'is_online' => true]);
    IpAddress::create(['vlan_id' => $vlan1->id, 'ip_address' => '192.168.1.2', 'is_online' => true]);
    IpAddress::create(['vlan_id' => $vlan1->id, 'ip_address' => '192.168.1.3', 'is_online' => false]);

    // Create second VLAN with 2 IPs (1 online)
    $vlan2 = Vlan::create([
        'vlan_id' => 20,
        'vlan_name' => 'VLAN 20',
        'network_address' => '192.168.2.0',
        'cidr_suffix' => 24,
    ]);

    IpAddress::create(['vlan_id' => $vlan2->id, 'ip_address' => '192.168.2.1', 'is_online' => true]);
    IpAddress::create(['vlan_id' => $vlan2->id, 'ip_address' => '192.168.2.2', 'is_online' => false]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('3'); // 3 online devices total (2 + 1)
    $response->assertSee('5'); // 5 total IPs (3 + 2)
});
