<?php

use App\Modules\Network\Models\Vlan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Vlan Model - Search Scope', function () {
    test('searchByTerm matches VLAN ID exactly when query is numeric', function () {
        $vlan1 = Vlan::create([
            'vlan_id' => 100,
            'vlan_name' => 'Production Network',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        Vlan::create([
            'vlan_id' => 200,
            'vlan_name' => 'Development Network',
            'network_address' => '192.168.2.0',
            'cidr_suffix' => 24,
        ]);

        $results = Vlan::searchByTerm('100')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->id)->toBe($vlan1->id);
    });

    test('searchByTerm matches VLAN name partially', function () {
        $vlan1 = Vlan::create([
            'vlan_id' => 101,
            'vlan_name' => 'Production Network',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        Vlan::create([
            'vlan_id' => 102,
            'vlan_name' => 'Development Network',
            'network_address' => '192.168.2.0',
            'cidr_suffix' => 24,
        ]);

        $results = Vlan::searchByTerm('Production')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->id)->toBe($vlan1->id);
    });

    test('searchByTerm matches VLAN name case-insensitively', function () {
        $vlan1 = Vlan::create([
            'vlan_id' => 103,
            'vlan_name' => 'Production Network',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        Vlan::create([
            'vlan_id' => 104,
            'vlan_name' => 'Development Network',
            'network_address' => '192.168.2.0',
            'cidr_suffix' => 24,
        ]);

        $results = Vlan::searchByTerm('production')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->id)->toBe($vlan1->id);
    });

    test('searchByTerm matches network address partially', function () {
        $vlan1 = Vlan::create([
            'vlan_id' => 105,
            'vlan_name' => 'Production Network',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        Vlan::create([
            'vlan_id' => 106,
            'vlan_name' => 'Development Network',
            'network_address' => '10.0.0.0',
            'cidr_suffix' => 24,
        ]);

        $results = Vlan::searchByTerm('192.168')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->id)->toBe($vlan1->id);
    });

    test('searchByTerm returns multiple matches when appropriate', function () {
        Vlan::create([
            'vlan_id' => 107,
            'vlan_name' => 'Production Network',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        Vlan::create([
            'vlan_id' => 108,
            'vlan_name' => 'Production Backup',
            'network_address' => '192.168.2.0',
            'cidr_suffix' => 24,
        ]);

        Vlan::create([
            'vlan_id' => 109,
            'vlan_name' => 'Development Network',
            'network_address' => '10.0.0.0',
            'cidr_suffix' => 24,
        ]);

        $results = Vlan::searchByTerm('Production')->get();

        expect($results)->toHaveCount(2);
    });

    test('searchByTerm returns empty collection when no matches', function () {
        Vlan::create([
            'vlan_id' => 110,
            'vlan_name' => 'Production Network',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        $results = Vlan::searchByTerm('NonExistent')->get();

        expect($results)->toHaveCount(0);
    });

    test('searchByTerm handles partial VLAN name matches', function () {
        $vlan1 = Vlan::create([
            'vlan_id' => 111,
            'vlan_name' => 'Guest WiFi Network',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        Vlan::create([
            'vlan_id' => 112,
            'vlan_name' => 'Production Network',
            'network_address' => '192.168.2.0',
            'cidr_suffix' => 24,
        ]);

        $results = Vlan::searchByTerm('WiFi')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->id)->toBe($vlan1->id);
    });

    test('searchByTerm matches numeric VLAN ID even when name contains numbers', function () {
        $vlan1 = Vlan::create([
            'vlan_id' => 50,
            'vlan_name' => 'Network 100',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        $vlan2 = Vlan::create([
            'vlan_id' => 100,
            'vlan_name' => 'Production Network',
            'network_address' => '192.168.2.0',
            'cidr_suffix' => 24,
        ]);

        // Should match both: vlan_id=100 and vlan_name containing '100'
        $results = Vlan::searchByTerm('100')->get();

        expect($results)->toHaveCount(2);
        expect($results->pluck('id'))->toContain($vlan1->id, $vlan2->id);
    });
});
