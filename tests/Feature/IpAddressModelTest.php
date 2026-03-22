<?php

use App\Modules\Network\Models\IpAddress;
use App\Modules\Network\Models\Vlan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('IpAddress Model - DHCP Range Methods', function () {
    test('isInDhcpRange returns true when IP is within DHCP range', function () {
        $vlan = Vlan::create([
            'vlan_id' => 100,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
            'dhcp_from' => '192.168.1.100',
            'dhcp_to' => '192.168.1.200',
        ]);

        $ipAddress = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.150',
        ]);

        expect($ipAddress->isInDhcpRange())->toBeTrue();
    });

    test('isInDhcpRange returns true when IP equals dhcp_from', function () {
        $vlan = Vlan::create([
            'vlan_id' => 101,
            'vlan_name' => 'Test VLAN 2',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
            'dhcp_from' => '192.168.1.100',
            'dhcp_to' => '192.168.1.200',
        ]);

        $ipAddress = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.100',
        ]);

        expect($ipAddress->isInDhcpRange())->toBeTrue();
    });

    test('isInDhcpRange returns true when IP equals dhcp_to', function () {
        $vlan = Vlan::create([
            'vlan_id' => 102,
            'vlan_name' => 'Test VLAN 3',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
            'dhcp_from' => '192.168.1.100',
            'dhcp_to' => '192.168.1.200',
        ]);

        $ipAddress = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.200',
        ]);

        expect($ipAddress->isInDhcpRange())->toBeTrue();
    });

    test('isInDhcpRange returns false when IP is below DHCP range', function () {
        $vlan = Vlan::create([
            'vlan_id' => 103,
            'vlan_name' => 'Test VLAN 4',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
            'dhcp_from' => '192.168.1.100',
            'dhcp_to' => '192.168.1.200',
        ]);

        $ipAddress = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.99',
        ]);

        expect($ipAddress->isInDhcpRange())->toBeFalse();
    });

    test('isInDhcpRange returns false when IP is above DHCP range', function () {
        $vlan = Vlan::create([
            'vlan_id' => 104,
            'vlan_name' => 'Test VLAN 5',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
            'dhcp_from' => '192.168.1.100',
            'dhcp_to' => '192.168.1.200',
        ]);

        $ipAddress = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.201',
        ]);

        expect($ipAddress->isInDhcpRange())->toBeFalse();
    });

    test('isInDhcpRange returns false when dhcp_from is null', function () {
        $vlan = Vlan::create([
            'vlan_id' => 105,
            'vlan_name' => 'Test VLAN 6',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
            'dhcp_from' => null,
            'dhcp_to' => '192.168.1.200',
        ]);

        $ipAddress = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.150',
        ]);

        expect($ipAddress->isInDhcpRange())->toBeFalse();
    });

    test('isInDhcpRange returns false when dhcp_to is null', function () {
        $vlan = Vlan::create([
            'vlan_id' => 106,
            'vlan_name' => 'Test VLAN 7',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
            'dhcp_from' => '192.168.1.100',
            'dhcp_to' => null,
        ]);

        $ipAddress = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.150',
        ]);

        expect($ipAddress->isInDhcpRange())->toBeFalse();
    });

    test('isInDhcpRange returns false when both dhcp_from and dhcp_to are null', function () {
        $vlan = Vlan::create([
            'vlan_id' => 107,
            'vlan_name' => 'Test VLAN 8',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
            'dhcp_from' => null,
            'dhcp_to' => null,
        ]);

        $ipAddress = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.150',
        ]);

        expect($ipAddress->isInDhcpRange())->toBeFalse();
    });
});

describe('IpAddress Model - MAC Address Formatting', function () {
    test('getFormattedMacAddress returns uppercase with colons', function () {
        $vlan = Vlan::create([
            'vlan_id' => 110,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        $ipAddress = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.10',
            'mac_address' => 'aa:bb:cc:dd:ee:ff',
        ]);

        expect($ipAddress->getFormattedMacAddress())->toBe('AA:BB:CC:DD:EE:FF');
    });

    test('getFormattedMacAddress converts hyphens to colons', function () {
        $vlan = Vlan::create([
            'vlan_id' => 111,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        $ipAddress = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.11',
            'mac_address' => 'aa-bb-cc-dd-ee-ff',
        ]);

        expect($ipAddress->getFormattedMacAddress())->toBe('AA:BB:CC:DD:EE:FF');
    });

    test('getFormattedMacAddress handles mixed case', function () {
        $vlan = Vlan::create([
            'vlan_id' => 112,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        $ipAddress = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.12',
            'mac_address' => 'Aa:Bb:Cc:Dd:Ee:Ff',
        ]);

        expect($ipAddress->getFormattedMacAddress())->toBe('AA:BB:CC:DD:EE:FF');
    });

    test('getFormattedMacAddress returns null when mac_address is null', function () {
        $vlan = Vlan::create([
            'vlan_id' => 113,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        $ipAddress = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.13',
            'mac_address' => null,
        ]);

        expect($ipAddress->getFormattedMacAddress())->toBeNull();
    });
});

describe('IpAddress Model - Navigation Methods', function () {
    test('getPreviousIpAddress returns previous IP in numeric order', function () {
        $vlan = Vlan::create([
            'vlan_id' => 120,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        $ip1 = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.10',
        ]);

        $ip2 = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.20',
        ]);

        $ip3 = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.30',
        ]);

        expect($ip3->getPreviousIpAddress()->id)->toBe($ip2->id);
        expect($ip2->getPreviousIpAddress()->id)->toBe($ip1->id);
    });

    test('getPreviousIpAddress returns null for first IP', function () {
        $vlan = Vlan::create([
            'vlan_id' => 121,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        $ip1 = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.10',
        ]);

        IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.20',
        ]);

        expect($ip1->getPreviousIpAddress())->toBeNull();
    });

    test('getNextIpAddress returns next IP in numeric order', function () {
        $vlan = Vlan::create([
            'vlan_id' => 122,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        $ip1 = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.10',
        ]);

        $ip2 = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.20',
        ]);

        $ip3 = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.30',
        ]);

        expect($ip1->getNextIpAddress()->id)->toBe($ip2->id);
        expect($ip2->getNextIpAddress()->id)->toBe($ip3->id);
    });

    test('getNextIpAddress returns null for last IP', function () {
        $vlan = Vlan::create([
            'vlan_id' => 123,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.10',
        ]);

        $ip2 = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.20',
        ]);

        expect($ip2->getNextIpAddress())->toBeNull();
    });

    test('navigation methods only consider IPs in same VLAN', function () {
        $vlan1 = Vlan::create([
            'vlan_id' => 124,
            'vlan_name' => 'Test VLAN 1',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        $vlan2 = Vlan::create([
            'vlan_id' => 125,
            'vlan_name' => 'Test VLAN 2',
            'network_address' => '192.168.2.0',
            'cidr_suffix' => 24,
        ]);

        $ip1 = IpAddress::create([
            'vlan_id' => $vlan1->id,
            'ip_address' => '192.168.1.10',
        ]);

        IpAddress::create([
            'vlan_id' => $vlan2->id,
            'ip_address' => '192.168.1.20',
        ]);

        expect($ip1->getNextIpAddress())->toBeNull();
        expect($ip1->getPreviousIpAddress())->toBeNull();
    });
});

describe('IpAddress Model - Query Scopes', function () {
    test('hasDnsName scope filters IPs with DNS names', function () {
        $vlan = Vlan::create([
            'vlan_id' => 130,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        $ipWithDns = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.10',
            'dns_name' => 'server.example.com',
        ]);

        IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.11',
            'dns_name' => null,
        ]);

        IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.12',
            'dns_name' => '',
        ]);

        $results = IpAddress::hasDnsName()->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->id)->toBe($ipWithDns->id);
    });

    test('hasComment scope filters IPs with comments', function () {
        $vlan = Vlan::create([
            'vlan_id' => 131,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        $ipWithComment = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.10',
            'comment' => 'Test comment',
        ]);

        IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.11',
            'comment' => null,
        ]);

        IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.12',
            'comment' => '',
        ]);

        $results = IpAddress::hasComment()->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->id)->toBe($ipWithComment->id);
    });

    test('filterByStatus scope filters online IPs', function () {
        $vlan = Vlan::create([
            'vlan_id' => 132,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        $onlineIp = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.10',
            'is_online' => true,
        ]);

        IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.11',
            'is_online' => false,
        ]);

        $results = IpAddress::filterByStatus('online')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->id)->toBe($onlineIp->id);
    });

    test('filterByStatus scope filters offline IPs', function () {
        $vlan = Vlan::create([
            'vlan_id' => 133,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.10',
            'is_online' => true,
        ]);

        $offlineIp = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.11',
            'is_online' => false,
        ]);

        $results = IpAddress::filterByStatus('offline')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->id)->toBe($offlineIp->id);
    });

    test('filterByStatus scope returns all IPs when status is null', function () {
        $vlan = Vlan::create([
            'vlan_id' => 134,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.10',
            'is_online' => true,
        ]);

        IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.11',
            'is_online' => false,
        ]);

        $results = IpAddress::filterByStatus(null)->get();

        expect($results)->toHaveCount(2);
    });

    test('searchByTerm scope searches IP addresses', function () {
        $vlan = Vlan::create([
            'vlan_id' => 135,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        $matchingIp = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.100',
        ]);

        IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '10.0.0.1',
        ]);

        $results = IpAddress::searchByTerm('192.168')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->id)->toBe($matchingIp->id);
    });

    test('searchByTerm scope searches DNS names', function () {
        $vlan = Vlan::create([
            'vlan_id' => 136,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        $matchingIp = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.10',
            'dns_name' => 'server.example.com',
        ]);

        IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.11',
            'dns_name' => 'router.test.com',
        ]);

        $results = IpAddress::searchByTerm('example')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->id)->toBe($matchingIp->id);
    });

    test('searchByTerm scope searches MAC addresses with colon format', function () {
        $vlan = Vlan::create([
            'vlan_id' => 137,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        $matchingIp = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.10',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
        ]);

        IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.11',
            'mac_address' => '11:22:33:44:55:66',
        ]);

        $results = IpAddress::searchByTerm('AA:BB:CC')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->id)->toBe($matchingIp->id);
    });

    test('searchByTerm scope searches MAC addresses with hyphen format', function () {
        $vlan = Vlan::create([
            'vlan_id' => 138,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        $matchingIp = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.10',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
        ]);

        IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.11',
            'mac_address' => '11:22:33:44:55:66',
        ]);

        $results = IpAddress::searchByTerm('AA-BB-CC')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->id)->toBe($matchingIp->id);
    });

    test('searchByTerm scope searches MAC addresses without separators', function () {
        $vlan = Vlan::create([
            'vlan_id' => 139,
            'vlan_name' => 'Test VLAN',
            'network_address' => '192.168.1.0',
            'cidr_suffix' => 24,
        ]);

        $matchingIp = IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.10',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
        ]);

        IpAddress::create([
            'vlan_id' => $vlan->id,
            'ip_address' => '192.168.1.11',
            'mac_address' => '11:22:33:44:55:66',
        ]);

        $results = IpAddress::searchByTerm('AABBCC')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->id)->toBe($matchingIp->id);
    });
});
