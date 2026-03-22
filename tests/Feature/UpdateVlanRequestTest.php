<?php

use App\Models\User;
use App\Modules\Network\Http\Requests\UpdateVlanRequest;
use App\Modules\Network\Models\Vlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Run module migrations
    $this->artisan('migrate', ['--path' => 'app/Modules/Network/Database/Migrations']);
    
    // Create permissions
    Permission::create(['name' => 'module.network.view']);
    Permission::create(['name' => 'module.network.edit']);
    
    // Create a user with edit permission
    $this->user = User::factory()->create(['role' => 'user']);
    $this->user->givePermissionTo('module.network.edit');
    
    // Create a VLAN for update tests
    $this->vlan = Vlan::create([
        'vlan_id' => 100,
        'vlan_name' => 'Existing VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
    ]);
});

// Authorization Tests

test('authorize returns true when user has module.network.edit permission', function () {
    $request = new UpdateVlanRequest();
    $request->setUserResolver(fn() => $this->user);
    
    expect($request->authorize())->toBeTrue();
});

test('authorize returns false when user lacks module.network.edit permission', function () {
    $userWithoutPermission = User::factory()->create(['role' => 'user']);
    
    $request = new UpdateVlanRequest();
    $request->setUserResolver(fn() => $userWithoutPermission);
    
    expect($request->authorize())->toBeFalse();
});

test('authorize returns true for super admin without explicit permission', function () {
    $superAdmin = User::factory()->create(['role' => 'super-admin']);
    
    $request = new UpdateVlanRequest();
    $request->setUserResolver(fn() => $superAdmin);
    
    expect($request->authorize())->toBeTrue();
});

// Basic Validation Rules Tests

test('validation passes with valid VLAN data', function () {
    $data = [
        'vlan_id' => 100,
        'vlan_name' => 'Updated VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
        'gateway' => '192.168.1.1',
        'dhcp_from' => '192.168.1.100',
        'dhcp_to' => '192.168.1.200',
        'description' => 'Updated description',
        'internes_netz' => true,
        'ipscan' => true,
        'scan_interval_minutes' => 60,
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);
    
    expect($validator->passes())->toBeTrue();
});

test('validation passes when updating VLAN with its own vlan_id', function () {
    $data = [
        'vlan_id' => 100, // Same as existing VLAN
        'vlan_name' => 'Updated VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);
    
    expect($validator->passes())->toBeTrue();
});

test('validation fails when updating VLAN with another existing vlan_id', function () {
    // Create another VLAN
    Vlan::create([
        'vlan_id' => 200,
        'vlan_name' => 'Another VLAN',
        'network_address' => '192.168.2.0',
        'cidr_suffix' => 24,
    ]);
    
    $data = [
        'vlan_id' => 200, // Trying to use another VLAN's ID
        'vlan_name' => 'Updated VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('vlan_id'))->toBeTrue();
});

test('validation fails when vlan_id is missing', function () {
    $data = [
        'vlan_name' => 'Updated VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('vlan_id'))->toBeTrue();
});

test('validation fails when vlan_id is less than 1', function () {
    $data = [
        'vlan_id' => 0,
        'vlan_name' => 'Updated VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('vlan_id'))->toBeTrue();
});

test('validation fails when vlan_id is greater than 4094', function () {
    $data = [
        'vlan_id' => 4095,
        'vlan_name' => 'Updated VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('vlan_id'))->toBeTrue();
});

test('validation fails when vlan_name is missing', function () {
    $data = [
        'vlan_id' => 100,
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('vlan_name'))->toBeTrue();
});

test('validation fails when network_address is invalid', function () {
    $data = [
        'vlan_id' => 100,
        'vlan_name' => 'Updated VLAN',
        'network_address' => 'invalid-ip',
        'cidr_suffix' => 24,
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('network_address'))->toBeTrue();
});

test('validation fails when cidr_suffix is less than 0', function () {
    $data = [
        'vlan_id' => 100,
        'vlan_name' => 'Updated VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => -1,
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('cidr_suffix'))->toBeTrue();
});

test('validation fails when cidr_suffix is greater than 32', function () {
    $data = [
        'vlan_id' => 100,
        'vlan_name' => 'Updated VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 33,
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('cidr_suffix'))->toBeTrue();
});

test('validation fails when scan_interval_minutes is less than 1', function () {
    $data = [
        'vlan_id' => 100,
        'vlan_name' => 'Updated VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
        'scan_interval_minutes' => 0,
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('scan_interval_minutes'))->toBeTrue();
});

// Custom Validation Tests

test('validation fails when gateway is not in subnet', function () {
    $data = [
        'vlan_id' => 100,
        'vlan_name' => 'Updated VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
        'gateway' => '192.168.2.1', // Outside subnet
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('gateway'))->toBeTrue();
});

test('validation passes when gateway is in subnet', function () {
    $data = [
        'vlan_id' => 100,
        'vlan_name' => 'Updated VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
        'gateway' => '192.168.1.1', // Inside subnet
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);
    
    expect($validator->passes())->toBeTrue();
});

test('validation fails when dhcp_from is not in subnet', function () {
    $data = [
        'vlan_id' => 100,
        'vlan_name' => 'Updated VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
        'dhcp_from' => '192.168.2.100', // Outside subnet
        'dhcp_to' => '192.168.1.200',
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('dhcp_from'))->toBeTrue();
});

test('validation fails when dhcp_to is not in subnet', function () {
    $data = [
        'vlan_id' => 100,
        'vlan_name' => 'Updated VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
        'dhcp_from' => '192.168.1.100',
        'dhcp_to' => '192.168.2.200', // Outside subnet
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('dhcp_to'))->toBeTrue();
});

test('validation fails when dhcp_from is greater than dhcp_to', function () {
    $data = [
        'vlan_id' => 100,
        'vlan_name' => 'Updated VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
        'dhcp_from' => '192.168.1.200',
        'dhcp_to' => '192.168.1.100',
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('dhcp_from'))->toBeTrue();
});

test('validation passes when dhcp_from equals dhcp_to', function () {
    $data = [
        'vlan_id' => 100,
        'vlan_name' => 'Updated VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
        'dhcp_from' => '192.168.1.100',
        'dhcp_to' => '192.168.1.100',
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);
    
    expect($validator->passes())->toBeTrue();
});

test('validation passes when dhcp_from is less than dhcp_to', function () {
    $data = [
        'vlan_id' => 100,
        'vlan_name' => 'Updated VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
        'dhcp_from' => '192.168.1.100',
        'dhcp_to' => '192.168.1.200',
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);
    
    expect($validator->passes())->toBeTrue();
});

test('validation passes when only dhcp_from is provided', function () {
    $data = [
        'vlan_id' => 100,
        'vlan_name' => 'Updated VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
        'dhcp_from' => '192.168.1.100',
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);
    
    expect($validator->passes())->toBeTrue();
});

test('validation passes when only dhcp_to is provided', function () {
    $data = [
        'vlan_id' => 100,
        'vlan_name' => 'Updated VLAN',
        'network_address' => '192.168.1.0',
        'cidr_suffix' => 24,
        'dhcp_to' => '192.168.1.200',
    ];
    
    $request = new UpdateVlanRequest();
    $request->setRouteResolver(fn() => new class {
        public function parameter($name) {
            return Vlan::find(1);
        }
    });
    
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);
    
    expect($validator->passes())->toBeTrue();
});
