# Design Document: Network/VLAN Management Module

## Overview

The Network/VLAN Management Module is a Laravel 11 module that integrates with the IT Cockpit Core System to provide comprehensive VLAN and IP address management capabilities. The module follows Laravel's MVC architecture and leverages the existing module infrastructure, authentication system, and audit logging service.

The system consists of four main functional areas:

1. **VLAN Management**: CRUD operations for VLAN definitions with network parameters
2. **IP Address Inventory**: Automatic generation and tracking of all IP addresses within each VLAN
3. **Network Scanning**: Automated ping-based device discovery and MAC address resolution
4. **User Interface**: Blade-based views with Tailwind CSS for VLAN browsing, detail viewing, and dashboard widgets

Key design decisions:
- Use Laravel Eloquent models for all data access
- Implement scanning as an Artisan command scheduled via Laravel's task scheduler
- Use service classes (IpGeneratorService, ScannerService) to encapsulate business logic
- Leverage existing Core System infrastructure for authentication, permissions, and audit logging
- Use Tailwind CSS for consistent styling with the rest of IT Cockpit

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    IT Cockpit Core System                   │
│  ├─ Authentication & RBAC                                   │
│  ├─ Module Infrastructure                                   │
│  ├─ Audit Logging Service                                   │
│  └─ Dashboard & Sidebar Hooks                               │
└────────────────────────┬────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────┐
│              Network/VLAN Management Module                 │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │              Web Layer (Controllers)                  │  │
│  │  ├─ VlanController (CRUD operations)                 │  │
│  │  ├─ IpAddressController (inline updates)             │  │
│  │  └─ VlanCommentController (comment management)       │  │
│  └──────────────────────┬───────────────────────────────┘  │
│                         │                                   │
│  ┌──────────────────────▼───────────────────────────────┐  │
│  │              Service Layer                            │  │
│  │  ├─ IpGeneratorService (subnet calculation)          │  │
│  │  └─ ScannerService (ping & ARP resolution)           │  │
│  └──────────────────────┬───────────────────────────────┘  │
│                         │                                   │
│  ┌──────────────────────▼───────────────────────────────┐  │
│  │              Data Layer (Models)                      │  │
│  │  ├─ Vlan (Eloquent model)                            │  │
│  │  ├─ IpAddress (Eloquent model)                       │  │
│  │  └─ VlanComment (Eloquent model)                     │  │
│  └──────────────────────┬───────────────────────────────┘  │
│                         │                                   │
│  ┌──────────────────────▼───────────────────────────────┐  │
│  │              Background Tasks                         │  │
│  │  └─ NetworkScanCommand (Artisan command)             │  │
│  └───────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌───────────────────────────────────────────────────────┐  │
│  │              View Layer (Blade Templates)             │  │
│  │  ├─ vlans/index.blade.php (VLAN list)                │  │
│  │  ├─ vlans/show.blade.php (VLAN detail)               │  │
│  │  ├─ vlans/create.blade.php (VLAN form)               │  │
│  │  ├─ vlans/edit.blade.php (VLAN form)                 │  │
│  │  └─ components/dashboard-widget.blade.php            │  │
│  └───────────────────────────────────────────────────────┘  │
└────────────────────────┬────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────┐
│                    MySQL Database                           │
│  ├─ vlans                                                   │
│  ├─ ip_addresses                                            │
│  └─ vlan_comments                                           │
└─────────────────────────────────────────────────────────────┘
```

### Module Directory Structure

```
app/Modules/Network/
├── module.json                      # Module metadata
├── NetworkServiceProvider.php       # Module service provider
├── Http/
│   ├── Controllers/
│   │   ├── VlanController.php
│   │   ├── IpAddressController.php
│   │   └── VlanCommentController.php
│   └── Requests/
│       ├── StoreVlanRequest.php
│       └── UpdateVlanRequest.php
├── Models/
│   ├── Vlan.php
│   ├── IpAddress.php
│   └── VlanComment.php
├── Services/
│   ├── IpGeneratorService.php
│   └── ScannerService.php
├── Console/
│   └── Commands/
│       └── NetworkScanCommand.php
├── Database/
│   └── Migrations/
│       ├── 2024_01_01_000001_create_vlans_table.php
│       ├── 2024_01_01_000002_create_ip_addresses_table.php
│       └── 2024_01_01_000003_create_vlan_comments_table.php
├── Routes/
│   └── web.php
└── Views/
    ├── vlans/
    │   ├── index.blade.php
    │   ├── show.blade.php
    │   ├── create.blade.php
    │   └── edit.blade.php
    └── components/
        └── dashboard-widget.blade.php
```

### Integration with Core System

The module integrates with the IT Cockpit Core System through:

1. **Module Registration**: NetworkServiceProvider registers the module with the Core System's module infrastructure
2. **Permission System**: Uses Core System's RBAC with `module.network.view` and `module.network.edit` permissions
3. **Sidebar Hook**: Registers navigation item via Core System's hook system
4. **Dashboard Hook**: Registers widget via Core System's hook system
5. **Audit Logging**: Uses Core System's AuditLogger service for all significant actions

## Components and Interfaces

### 1. VlanController

Handles HTTP requests for VLAN management operations.

**Methods**:

```php
index(): View
  - Retrieves all VLANs ordered by vlan_id
  - Returns vlans/index view with VLAN collection
  - Requires: module.network.view permission

show(Vlan $vlan): View
  - Loads VLAN with relationships (ipAddresses, comments.user)
  - Returns vlans/show view with VLAN details
  - Requires: module.network.view permission

create(): View
  - Returns vlans/create view with empty form
  - Requires: module.network.edit permission

store(StoreVlanRequest $request): RedirectResponse
  - Validates input via StoreVlanRequest
  - Creates new VLAN record
  - Calls IpGeneratorService to generate IP addresses
  - Logs action via AuditLogger
  - Redirects to vlans.show with success message
  - Requires: module.network.edit permission

edit(Vlan $vlan): View
  - Returns vlans/edit view with VLAN data
  - Requires: module.network.edit permission

update(UpdateVlanRequest $request, Vlan $vlan): RedirectResponse
  - Validates input via UpdateVlanRequest
  - Updates VLAN record
  - If network_address or cidr_suffix changed:
    - Deletes existing IP addresses
    - Calls IpGeneratorService to regenerate IP addresses
  - Logs action via AuditLogger
  - Redirects to vlans.show with success message
  - Requires: module.network.edit permission

destroy(Vlan $vlan): RedirectResponse
  - Deletes VLAN (cascade deletes IP addresses and comments)
  - Logs action via AuditLogger
  - Redirects to vlans.index with success message
  - Requires: module.network.edit permission
```

### 2. IpAddressController

Handles inline updates to IP address records.

**Methods**:

```php
update(Request $request, IpAddress $ipAddress): JsonResponse
  - Validates input (dns_name, comment)
  - Updates IP address record
  - Preserves scan-related fields (is_online, last_online_at, etc.)
  - Logs action via AuditLogger
  - Returns JSON response with updated data
  - Requires: module.network.edit permission
```

### 3. VlanCommentController

Handles VLAN comment operations.

**Methods**:

```php
store(Request $request, Vlan $vlan): RedirectResponse
  - Validates comment text (required, max 1000 chars)
  - Creates VlanComment associated with authenticated user
  - Logs action via AuditLogger
  - Redirects back with success message
  - Requires: module.network.view permission

destroy(VlanComment $comment): RedirectResponse
  - Checks authorization (author or super-admin)
  - Deletes comment
  - Logs action via AuditLogger
  - Redirects back with success message
  - Requires: module.network.view permission (ownership checked separately)
```

### 4. IpGeneratorService

Service class responsible for calculating and creating IP address records for a VLAN.

**Methods**:

```php
generateIpAddresses(Vlan $vlan): int
  - Calculates network parameters from VLAN's network_address and cidr_suffix
  - Generates all valid host IP addresses in the subnet
  - Creates IpAddress records for each host address
  - Returns count of generated addresses
  
  Algorithm:
  1. Convert network_address to integer using ip2long()
  2. Calculate netmask: (0xFFFFFFFF << (32 - cidr_suffix)) & 0xFFFFFFFF
  3. Calculate network: network_address & netmask
  4. Calculate broadcast: network | (~netmask & 0xFFFFFFFF)
  5. Calculate first_host: network + 1
  6. Calculate last_host: broadcast - 1
  7. For each address from first_host to last_host:
     - Convert to IP string using long2ip()
     - Create IpAddress record with vlan_id and ip_address
  8. Return count of created records

calculateSubnetInfo(string $networkAddress, int $cidrSuffix): array
  - Helper method that returns subnet information
  - Returns: ['network', 'broadcast', 'first_host', 'last_host', 'host_count']
  - Used for validation and display purposes
```

**Special Cases**:
- /32 subnet: Generates 1 address (the network address itself)
- /31 subnet: Generates 2 addresses (point-to-point link)
- /30 subnet: Generates 2 usable addresses (network+1 and broadcast-1)
- /24 subnet: Generates 254 addresses (standard class C)

### 5. ScannerService

Service class responsible for network scanning operations.

**Methods**:

```php
scanVlan(Vlan $vlan): array
  - Retrieves all IP addresses for the VLAN
  - For each IP address:
    - Calls pingIpAddress() to check availability
    - If online, calls resolveMacAddress() to get MAC
    - Updates IpAddress record with results
  - Returns summary: ['scanned' => count, 'online' => count, 'offline' => count]

pingIpAddress(string $ipAddress): array
  - Executes ping command with 1 second timeout and 1 packet
  - Parses ping output to determine success/failure
  - Extracts response time if successful
  - Returns: ['is_online' => bool, 'ping_ms' => float|null]
  
  Platform-specific ping commands:
  - Windows: ping -n 1 -w 1000 {ip}
  - Linux: ping -c 1 -W 1 {ip}

resolveMacAddress(string $ipAddress): ?string
  - Executes ARP command to query system ARP cache
  - Parses output to extract MAC address
  - Returns MAC address string or null if not found
  
  Platform-specific ARP commands:
  - Windows: arp -a {ip}
  - Linux: arp -n {ip}
  
  MAC address parsing:
  - Matches pattern: ([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})
  - Normalizes to uppercase with colon separators

updateIpAddressStatus(IpAddress $ipAddress, array $scanResult, ?string $macAddress): void
  - Updates IP address record with scan results
  - Sets is_online, last_scanned_at (always)
  - Sets last_online_at, ping_ms (if online)
  - Sets mac_address (if resolved)
  - Preserves user-entered data (dns_name, comment)

shouldScanVlan(Vlan $vlan): bool
  - Checks if ipscan is enabled
  - Checks if scan_interval_minutes have elapsed since last scan
  - Returns true if scan should be performed
```

### 6. NetworkScanCommand

Artisan command that triggers network scanning.

**Signature**: `network:scan`

**Options**:
- `--vlan={id}`: Scan specific VLAN only (optional)
- `--force`: Force scan even if interval hasn't elapsed (optional)

**Execution Flow**:

```php
handle(): int
  - If --vlan option provided:
    - Load specific VLAN
    - Scan that VLAN only
  - Else:
    - Load all VLANs where ipscan = true
    - For each VLAN:
      - Check if scan should run (unless --force)
      - Call ScannerService->scanVlan()
      - Output progress to console
      - Log scan results
  - Return exit code (0 = success)
```

**Scheduler Registration**:

In NetworkServiceProvider:
```php
protected function scheduleCommands()
{
    $this->app->booted(function () {
        $schedule = $this->app->make(Schedule::class);
        $schedule->command('network:scan')->everyMinute();
    });
}
```

### 7. NetworkServiceProvider

Module service provider that registers the module with the Core System.

**Methods**:

```php
register(): void
  - Binds IpGeneratorService to container
  - Binds ScannerService to container

boot(): void
  - Loads migrations from Database/Migrations
  - Loads routes from Routes/web.php
  - Loads views from Views/ with 'network' namespace
  - Registers sidebar hook
  - Registers dashboard widget hook
  - Registers permissions
  - Schedules network:scan command

registerSidebarHook(): void
  - Calls HookManager->registerSidebarItem() with:
    - label: 'Network'
    - route: 'network.index'
    - icon: 'heroicon-o-signal'
    - permission: 'module.network.view'

registerDashboardWidget(): void
  - Calls HookManager->registerDashboardWidget() with:
    - module: 'network'
    - view: 'network::components.dashboard-widget'

registerPermissions(): void
  - Calls HookManager->registerPermission() for:
    - module.network.view: 'View network module'
    - module.network.edit: 'Edit network configuration'
```

### 8. Form Request Classes

**StoreVlanRequest**:

```php
authorize(): bool
  - Returns auth()->user()->hasModulePermission('network', 'edit')

rules(): array
  - vlan_id: required|integer|min:1|max:4094|unique:vlans
  - vlan_name: required|string|max:255
  - network_address: required|ip
  - cidr_suffix: required|integer|min:0|max:32
  - gateway: nullable|ip
  - dhcp_from: nullable|ip
  - dhcp_to: nullable|ip
  - description: nullable|string|max:1000
  - internes_netz: boolean
  - ipscan: boolean
  - scan_interval_minutes: integer|min:1

withValidator(Validator $validator): void
  - Adds custom validation:
    - If gateway provided, must be within subnet
    - If dhcp_from and dhcp_to provided:
      - Both must be within subnet
      - dhcp_from must be <= dhcp_to
```

**UpdateVlanRequest**: Same as StoreVlanRequest except vlan_id unique rule ignores current VLAN

## Data Models

### Vlan Model

```php
class Vlan extends Model
{
    protected $fillable = [
        'vlan_id',
        'vlan_name',
        'network_address',
        'cidr_suffix',
        'gateway',
        'dhcp_from',
        'dhcp_to',
        'description',
        'internes_netz',
        'ipscan',
        'scan_interval_minutes',
    ];

    protected $casts = [
        'vlan_id' => 'integer',
        'cidr_suffix' => 'integer',
        'internes_netz' => 'boolean',
        'ipscan' => 'boolean',
        'scan_interval_minutes' => 'integer',
        'last_scanned_at' => 'datetime',
    ];

    protected $attributes = [
        'internes_netz' => false,
        'ipscan' => false,
        'scan_interval_minutes' => 60,
    ];

    // Relationships
    public function ipAddresses()
    {
        return $this->hasMany(IpAddress::class);
    }

    public function comments()
    {
        return $this->hasMany(VlanComment::class);
    }

    // Scopes
    public function scopeScanEnabled($query)
    {
        return $query->where('ipscan', true);
    }

    public function scopeNeedsScan($query)
    {
        return $query->where('ipscan', true)
            ->where(function ($q) {
                $q->whereNull('last_scanned_at')
                  ->orWhereRaw('last_scanned_at < DATE_SUB(NOW(), INTERVAL scan_interval_minutes MINUTE)');
            });
    }

    // Accessors & Mutators
    public function getSubnetAttribute(): string
    {
        return "{$this->network_address}/{$this->cidr_suffix}";
    }

    public function getOnlineCountAttribute(): int
    {
        return $this->ipAddresses()->where('is_online', true)->count();
    }

    public function getTotalIpCountAttribute(): int
    {
        return $this->ipAddresses()->count();
    }

    // Methods
    public function shouldScan(): bool
    {
        if (!$this->ipscan) {
            return false;
        }

        if ($this->last_scanned_at === null) {
            return true;
        }

        $intervalMinutes = $this->scan_interval_minutes ?? 60;
        $nextScanTime = $this->last_scanned_at->addMinutes($intervalMinutes);

        return now()->gte($nextScanTime);
    }
}
```

### IpAddress Model

```php
class IpAddress extends Model
{
    protected $fillable = [
        'vlan_id',
        'ip_address',
        'dns_name',
        'mac_address',
        'is_online',
        'last_online_at',
        'last_scanned_at',
        'ping_ms',
        'comment',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'last_online_at' => 'datetime',
        'last_scanned_at' => 'datetime',
        'ping_ms' => 'float',
    ];

    // Relationships
    public function vlan()
    {
        return $this->belongsTo(Vlan::class);
    }

    // Scopes
    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function scopeOffline($query)
    {
        return $query->where('is_online', false);
    }

    public function scopeNeverScanned($query)
    {
        return $query->whereNull('last_scanned_at');
    }

    // Accessors
    public function getStatusBadgeClassAttribute(): string
    {
        if ($this->last_scanned_at === null) {
            return 'bg-gray-200 text-gray-700';
        }

        return $this->is_online 
            ? 'bg-green-100 text-green-800' 
            : 'bg-gray-300 text-gray-700';
    }

    public function getStatusTextAttribute(): string
    {
        if ($this->last_scanned_at === null) {
            return 'Not Scanned';
        }

        return $this->is_online ? 'Online' : 'Offline';
    }

    // Methods
    public function updateFromScan(bool $isOnline, ?float $pingMs = null, ?string $macAddress = null): void
    {
        $this->is_online = $isOnline;
        $this->last_scanned_at = now();

        if ($isOnline) {
            $this->last_online_at = now();
            $this->ping_ms = $pingMs;
        }

        if ($macAddress !== null) {
            $this->mac_address = $macAddress;
        }

        $this->save();
    }
}
```

### VlanComment Model

```php
class VlanComment extends Model
{
    protected $fillable = [
        'vlan_id',
        'user_id',
        'comment',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($comment) {
            $comment->created_at = now();
        });
    }

    // Relationships
    public function vlan()
    {
        return $this->belongsTo(Vlan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Authorization
    public function canDelete(User $user): bool
    {
        return $user->isSuperAdmin() || $user->id === $this->user_id;
    }
}
```

### Database Migrations

**create_vlans_table**:

```php
Schema::create('vlans', function (Blueprint $table) {
    $table->id();
    $table->integer('vlan_id')->unique();
    $table->string('vlan_name');
    $table->string('network_address', 15);
    $table->tinyInteger('cidr_suffix');
    $table->string('gateway', 15)->nullable();
    $table->string('dhcp_from', 15)->nullable();
    $table->string('dhcp_to', 15)->nullable();
    $table->text('description')->nullable();
    $table->boolean('internes_netz')->default(false);
    $table->boolean('ipscan')->default(false);
    $table->integer('scan_interval_minutes')->default(60);
    $table->timestamp('last_scanned_at')->nullable();
    $table->timestamps();

    $table->index('vlan_id');
    $table->index('ipscan');
});
```

**create_ip_addresses_table**:

```php
Schema::create('ip_addresses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('vlan_id')->constrained()->onDelete('cascade');
    $table->string('ip_address', 15);
    $table->string('dns_name')->nullable();
    $table->string('mac_address', 17)->nullable();
    $table->boolean('is_online')->default(false);
    $table->timestamp('last_online_at')->nullable();
    $table->timestamp('last_scanned_at')->nullable();
    $table->float('ping_ms', 8, 2)->nullable();
    $table->text('comment')->nullable();
    $table->timestamps();

    $table->index('vlan_id');
    $table->index('ip_address');
    $table->index('is_online');
    $table->unique(['vlan_id', 'ip_address']);
});
```

**create_vlan_comments_table**:

```php
Schema::create('vlan_comments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('vlan_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->text('comment');
    $table->timestamp('created_at');

    $table->index('vlan_id');
    $table->index('created_at');
});
```


## Correctness Properties

A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.

### Property 1: VLAN ID Range Validation

*For any* VLAN creation or update attempt, if the vlan_id is outside the range 1-4094, the operation should be rejected with a validation error.

**Validates: Requirements 1.2**

### Property 2: IPv4 Address Validation

*For any* VLAN creation or update attempt, if the network_address is not a valid IPv4 address format, the operation should be rejected with a validation error.

**Validates: Requirements 1.3**

### Property 3: CIDR Suffix Range Validation

*For any* VLAN creation or update attempt, if the cidr_suffix is outside the range 0-32, the operation should be rejected with a validation error.

**Validates: Requirements 1.4**

### Property 4: DHCP Range Subnet Validation

*For any* VLAN with dhcp_from and dhcp_to values, both addresses should fall within the VLAN's subnet as defined by network_address and cidr_suffix, otherwise validation should fail.

**Validates: Requirements 1.5**

### Property 5: DHCP Range Order Validation

*For any* VLAN with dhcp_from and dhcp_to values, dhcp_from should be less than or equal to dhcp_to when converted to integers, otherwise validation should fail.

**Validates: Requirements 1.6**

### Property 6: Default Scan Interval

*For any* VLAN created without an explicit scan_interval_minutes value, the stored value should be 60.

**Validates: Requirements 1.7**

### Property 7: Cascade Delete Integrity

*For any* VLAN that is deleted, all associated IP addresses and comments should also be deleted from the database.

**Validates: Requirements 1.10**

### Property 8: Complete IP Address Generation

*For any* VLAN with valid network_address and cidr_suffix, the IP_Generator should create IP address records for all valid host addresses in the subnet (excluding network and broadcast addresses).

**Validates: Requirements 2.2, 2.4, 2.5**

### Property 9: Manual Update Preservation

*For any* IP address record that is manually updated (dns_name or comment), the automatically collected scan data (is_online, last_online_at, last_scanned_at, ping_ms, mac_address) should remain unchanged.

**Validates: Requirements 2.10**

### Property 10: Comment User Association

*For any* VLAN comment created, the user_id should match the authenticated user's ID.

**Validates: Requirements 3.2**

### Property 11: Comment Timestamp Automation

*For any* VLAN comment created, the created_at field should be automatically set to the current timestamp.

**Validates: Requirements 3.3**

### Property 12: Comment Chronological Ordering

*For any* set of VLAN comments retrieved for display, they should be ordered by created_at in descending order (newest first).

**Validates: Requirements 3.4**

### Property 13: Comment Author Deletion Authorization

*For any* VLAN comment and any user, the user should be able to delete the comment if and only if they are the author or have super-admin role.

**Validates: Requirements 3.6, 3.7**

### Property 14: Successful Ping Updates Online Status

*For any* IP address where a ping succeeds, the is_online field should be set to true.

**Validates: Requirements 4.3**

### Property 15: Successful Ping Updates Timestamp

*For any* IP address where a ping succeeds, the last_online_at field should be updated to the current timestamp.

**Validates: Requirements 4.4**

### Property 16: Successful Ping Records Response Time

*For any* IP address where a ping succeeds, the ping_ms field should be updated with the measured response time.

**Validates: Requirements 4.5**

### Property 17: Failed Ping Updates Online Status

*For any* IP address where a ping fails, the is_online field should be set to false.

**Validates: Requirements 4.6**

### Property 18: Failed Ping Preserves Last Online Timestamp

*For any* IP address where a ping fails, the last_online_at field should remain unchanged from its previous value.

**Validates: Requirements 4.7**

### Property 19: All Scans Update Scan Timestamp

*For any* IP address that is scanned (regardless of ping success or failure), the last_scanned_at field should be updated to the current timestamp.

**Validates: Requirements 4.8**

### Property 20: Scan Error Isolation

*For any* IP address scan that fails with an error, the error should be logged and scanning should continue with the remaining IP addresses in the VLAN.

**Validates: Requirements 4.10**

### Property 21: MAC Address Update on Resolution

*For any* IP address where MAC address resolution succeeds, the mac_address field should be updated with the resolved MAC address.

**Validates: Requirements 5.5**

### Property 22: MAC Address Preservation on Resolution Failure

*For any* IP address where MAC address resolution fails or returns no result, the existing mac_address field value should remain unchanged.

**Validates: Requirements 5.6**

### Property 23: ARP Error Logging and Continuation

*For any* ARP command execution that fails, the error should be logged and processing should continue without interruption.

**Validates: Requirements 5.7**

### Property 24: Scan-Enabled VLAN Selection

*For any* execution of the network:scan command, only VLANs where ipscan is true should be selected for scanning.

**Validates: Requirements 6.2**

### Property 25: Scan Interval Enforcement

*For any* VLAN where ipscan is true, a scan should only be performed if scan_interval_minutes have elapsed since last_scanned_at (or if never scanned).

**Validates: Requirements 6.4**

### Property 26: VLAN Scan Completion Timestamp

*For any* VLAN scan that completes, the VLAN's last_scanned_at timestamp should be updated to the current timestamp.

**Validates: Requirements 6.8**

### Property 27: Scheduler Error Isolation

*For any* scheduler execution failure, the error should be logged without affecting other scheduled tasks.

**Validates: Requirements 6.10**

### Property 28: VLAN List Display Completeness

*For any* VLAN in the system, when rendering the VLAN list page, the output should include vlan_id, vlan_name, network_address, cidr_suffix, and gateway.

**Validates: Requirements 7.2**

### Property 29: VLAN Detail Link Presence

*For any* VLAN displayed in the list, a link to the VLAN detail page should be present in the rendered output.

**Validates: Requirements 7.4**

### Property 30: VLAN Scan Status Indicator

*For any* VLAN displayed in the list, an indicator showing the ipscan status should be present in the rendered output.

**Validates: Requirements 7.5**

### Property 31: Permission-Based Edit Controls

*For any* user with module.network.edit permission viewing the VLAN list, edit and delete buttons should be displayed for each VLAN.

**Validates: Requirements 7.7**

### Property 32: VLAN List Ordering

*For any* set of VLANs displayed in the list, they should be ordered by vlan_id in ascending order.

**Validates: Requirements 7.10**

### Property 33: VLAN Detail Attribute Completeness

*For any* VLAN detail page rendered, all VLAN attributes (including description and DHCP range) should be displayed.

**Validates: Requirements 8.2**

### Property 34: IP Address Display Completeness

*For any* IP address within a VLAN, when rendering the detail page, the output should include ip_address, dns_name, mac_address, is_online status, and comment.

**Validates: Requirements 8.4**

### Property 35: Online Status Badge Styling

*For any* IP address that is online, the status badge should have green styling (bg-green-100 text-green-800).

**Validates: Requirements 8.5**

### Property 36: Offline Status Badge Styling

*For any* IP address that is offline, the status badge should have gray styling (bg-gray-300 text-gray-700).

**Validates: Requirements 8.6**

### Property 37: Unscanned Status Indicator

*For any* IP address that has never been scanned (last_scanned_at is null), a neutral status indicator should be displayed.

**Validates: Requirements 8.7**

### Property 38: Conditional Last Online Display

*For any* IP address that has been online at least once, the last_online_at timestamp should be displayed.

**Validates: Requirements 8.8**

### Property 39: Conditional Ping Time Display

*For any* IP address that is currently online, the ping_ms response time should be displayed.

**Validates: Requirements 8.9**

### Property 40: Permission-Based Inline Editing

*For any* user with module.network.edit permission viewing the VLAN detail page, inline editing should be enabled for dns_name and comment fields.

**Validates: Requirements 8.11**

### Property 41: Dashboard Widget Online Count Accuracy

*For any* dashboard widget render, the displayed online device count should equal the sum of all IP addresses across all VLANs where is_online is true.

**Validates: Requirements 9.2**

### Property 42: Dashboard Widget Total Count Accuracy

*For any* dashboard widget render, the displayed total monitored IP count should equal the sum of all IP address records across all VLANs.

**Validates: Requirements 9.3**

### Property 43: Dashboard Widget Permission Visibility

*For any* user without module.network.view permission, the dashboard widget should not be displayed.

**Validates: Requirements 9.8**

### Property 44: Sidebar Permission Visibility

*For any* user with module.network.view permission, the Network sidebar navigation item should be visible.

**Validates: Requirements 10.6**

### Property 45: VLAN Operation Audit Logging

*For any* VLAN create, update, or delete operation, an audit log entry should be created with the operation details.

**Validates: Requirements 10.9**

### Property 46: Scan Execution Audit Logging

*For any* network scan execution, an audit log entry should be created with scan results summary.

**Validates: Requirements 10.10**

### Property 47: View Permission Route Protection

*For any* user without module.network.view permission attempting to access VLAN list or detail pages, the request should be denied with a 403 Forbidden response.

**Validates: Requirements 11.2**

### Property 48: Edit Permission Route Protection

*For any* user without module.network.edit permission attempting to create, update, or delete VLANs, the request should be denied with a 403 Forbidden response.

**Validates: Requirements 11.3**

### Property 49: IP Update Permission Protection

*For any* user without module.network.edit permission attempting to modify IP address records, the request should be denied with a 403 Forbidden response.

**Validates: Requirements 11.4**

### Property 50: Comment Permission Protection

*For any* user without module.network.view permission attempting to add VLAN comments, the request should be denied with a 403 Forbidden response.

**Validates: Requirements 11.5**

### Property 51: Super Admin Universal Access

*For any* Network module function and any user with super-admin role, access should be granted regardless of specific module permissions.

**Validates: Requirements 11.6**

### Property 52: Unauthorized Access Audit Logging

*For any* unauthorized access attempt to a protected route, an audit log entry should be created.

**Validates: Requirements 11.10**

### Property 53: VLAN Name Required Validation

*For any* VLAN creation or update attempt with an empty vlan_name, the operation should be rejected with a validation error.

**Validates: Requirements 12.1**

### Property 54: Valid Subnet Validation

*For any* VLAN creation or update attempt, the combination of network_address and cidr_suffix should form a valid subnet, otherwise validation should fail.

**Validates: Requirements 12.2**

### Property 55: Gateway Subnet Validation

*For any* VLAN with a gateway address, the gateway should fall within the subnet defined by network_address and cidr_suffix, otherwise validation should fail.

**Validates: Requirements 12.3**

### Property 56: Positive Scan Interval Validation

*For any* VLAN creation or update attempt, scan_interval_minutes should be a positive integer, otherwise validation should fail.

**Validates: Requirements 12.4**

### Property 57: DNS Name Character Validation

*For any* IP address update with a dns_name value, the dns_name should contain only valid DNS characters (alphanumeric, hyphens, dots), otherwise validation should fail.

**Validates: Requirements 12.5**

### Property 58: Comment Non-Empty Validation

*For any* VLAN comment creation attempt with empty comment text, the operation should be rejected with a validation error.

**Validates: Requirements 12.6**

### Property 59: Comment Length Validation

*For any* VLAN comment creation attempt with comment text exceeding 1000 characters, the operation should be rejected with a validation error.

**Validates: Requirements 12.7**

### Property 60: First Host Address Calculation

*For any* VLAN, the first generated IP address should equal network_address + 1 (in integer representation).

**Validates: Requirements 13.1**

### Property 61: Last Host Address Calculation

*For any* VLAN, the last generated IP address should equal broadcast_address - 1 (in integer representation).

**Validates: Requirements 13.2**

### Property 62: Generated Address Count Accuracy

*For any* VLAN after IP address generation, the count returned by IP_Generator should equal the number of IP address records created in the database.

**Validates: Requirements 13.10**

### Property 63: Concurrent Scan Prevention

*For any* VLAN, if a scan is already in progress, attempts to start another scan for the same VLAN should be prevented.

**Validates: Requirements 14.6**

### Property 64: Scan Logging Completeness

*For any* completed scan, the log entry should contain scan start time, end time, and total IP addresses processed.

**Validates: Requirements 14.7**

### Property 65: Scan Duration Logging

*For any* completed scan, the log entry should contain the calculated scan duration.

**Validates: Requirements 14.8**

### Property 66: Long Scan Warning

*For any* scan that exceeds 5 minutes for a single VLAN, a warning should be logged.

**Validates: Requirements 14.9**

### Property 67: Database Error Logging

*For any* database operation failure, an error should be logged with full context including the operation details.

**Validates: Requirements 15.1**

### Property 68: Ping Error Logging

*For any* ping command execution failure, an error should be logged with the IP address and error message.

**Validates: Requirements 15.2**

### Property 69: ARP Error Logging

*For any* ARP command execution failure, an error should be logged with the IP address and error message.

**Validates: Requirements 15.3**

### Property 70: IP Generation Error Logging

*For any* IP address generation failure, an error should be logged with VLAN details.

**Validates: Requirements 15.4**

### Property 71: Validation Error Logging

*For any* validation failure, an error should be logged with the validation failure details and input data.

**Validates: Requirements 15.5**

### Property 72: Log Entry Contextual Information

*For any* log entry created by the Network module, contextual information (user_id, vlan_id, ip_address as applicable) should be included.

**Validates: Requirements 15.7**

### Property 73: Critical Error Log Level

*For any* critical error occurrence, the log entry should be created at ERROR level.

**Validates: Requirements 15.8**

### Property 74: Successful Scan Log Level

*For any* successfully completed scan, the log entry should be created at INFO level and include summary statistics.

**Validates: Requirements 15.9**

### Property 75: Sensitive Information Exclusion

*For any* log entry created by the Network module, sensitive information (passwords, tokens) should not be present in the log content.

**Validates: Requirements 15.10**

### Property 76: Edit Form Data Pre-population

*For any* VLAN edit form rendered, all form fields should be pre-populated with the current VLAN data.

**Validates: Requirements 17.2**

### Property 77: Validation Error Display

*For any* form submission that fails validation, error messages should be displayed adjacent to the relevant form fields.

**Validates: Requirements 17.7**

### Property 78: Validation Input Preservation

*For any* form submission that fails validation, the user's input should be preserved and re-displayed in the form for correction.

**Validates: Requirements 17.8**

### Property 79: Successful Operation Redirect

*For any* successful VLAN create or update operation, the user should be redirected to the VLAN detail page.

**Validates: Requirements 17.11**

### Property 80: Successful Operation Message

*For any* successful VLAN create or update operation, a success message should be displayed to the user.

**Validates: Requirements 17.12**

## Error Handling

### Validation Errors

**VLAN Validation**:
- Invalid vlan_id (out of range 1-4094): Return validation error "VLAN ID must be between 1 and 4094"
- Invalid network_address: Return validation error "Network address must be a valid IPv4 address"
- Invalid cidr_suffix (out of range 0-32): Return validation error "CIDR suffix must be between 0 and 32"
- Gateway not in subnet: Return validation error "Gateway address must be within the VLAN subnet"
- DHCP range not in subnet: Return validation error "DHCP range must be within the VLAN subnet"
- dhcp_from > dhcp_to: Return validation error "DHCP start address must be less than or equal to end address"
- Empty vlan_name: Return validation error "VLAN name is required"
- Invalid scan_interval_minutes: Return validation error "Scan interval must be a positive integer"

**IP Address Validation**:
- Invalid dns_name characters: Return validation error "DNS name contains invalid characters"

**Comment Validation**:
- Empty comment: Return validation error "Comment text is required"
- Comment too long: Return validation error "Comment must not exceed 1000 characters"

### Authorization Errors

**Permission Denied**:
- User lacks module.network.view: Return 403 Forbidden with message "You do not have permission to view the network module"
- User lacks module.network.edit: Return 403 Forbidden with message "You do not have permission to edit network configuration"
- User attempts to delete another user's comment (non-admin): Return 403 Forbidden with message "You can only delete your own comments"
- Log all unauthorized access attempts to audit log

### Scanning Errors

**Ping Failures**:
- Ping command execution fails: Log error with IP address and error message, continue with next IP
- Ping timeout: Mark IP as offline, log at INFO level
- Invalid ping output: Log warning, skip MAC resolution, continue with next IP

**ARP Resolution Failures**:
- ARP command execution fails: Log error with IP address and error message, preserve existing MAC address
- ARP output parsing fails: Log warning, preserve existing MAC address
- No ARP entry found: Log at DEBUG level, preserve existing MAC address

**Scan Execution Errors**:
- VLAN not found: Log error and skip
- Database connection lost during scan: Log error, rollback transaction, retry after delay
- Scan exceeds 5 minutes: Log warning with VLAN details and duration
- Concurrent scan attempt: Log warning "Scan already in progress for VLAN {id}", skip scan

### Database Errors

**Constraint Violations**:
- Duplicate vlan_id: Return validation error "VLAN ID already exists"
- Foreign key violation on VLAN delete: Should not occur due to CASCADE, but log error if it does
- Unique constraint on (vlan_id, ip_address): Log error "Duplicate IP address in VLAN", skip creation

**Connection Errors**:
- Database connection lost: Log error, display generic error message to user, attempt reconnection
- Query timeout: Log error with query details, display "Operation timed out" to user
- Transaction deadlock: Log warning, retry operation up to 3 times

### IP Generation Errors

**Calculation Errors**:
- Invalid network address format: Log error with VLAN details, do not create IP addresses
- CIDR suffix out of range: Log error, do not create IP addresses
- ip2long returns false: Log error "Invalid IP address format", skip that address
- Subnet too large (e.g., /8): Log warning "Large subnet detected, generation may take time", proceed with generation

**Resource Errors**:
- Memory limit exceeded during generation: Log error, rollback transaction, suggest smaller subnet
- Database insert batch fails: Log error, rollback transaction, retry with smaller batch size

### Module Integration Errors

**Registration Errors**:
- module.json missing: Log error "Network module configuration missing", module will not load
- Invalid module.json format: Log error with parse details, module will not load
- ServiceProvider class not found: Log error, module will not load
- Hook registration fails: Log error, continue with module load (graceful degradation)

**Runtime Errors**:
- View file not found: Log error, display "Module view error" to user
- Route registration fails: Log error, module routes will not be accessible
- Permission registration fails: Log error, default to deny access

## Testing Strategy

### Dual Testing Approach

The Network/VLAN Management Module will use both unit testing and property-based testing to ensure comprehensive coverage:

**Unit Tests**: Focus on specific examples, edge cases, and integration points
- Specific subnet sizes (/24, /31, /32) for IP generation
- Edge cases (empty VLAN list, no online devices, never-scanned IPs)
- Error conditions (invalid IP formats, failed ping commands, ARP resolution failures)
- Integration with Core System (authentication, permissions, audit logging)
- UI rendering (dashboard widget, VLAN list, detail pages)

**Property-Based Tests**: Verify universal properties across all inputs
- Generate random VLANs with various network addresses and CIDR suffixes
- Generate random IP addresses and scan results
- Generate random users with various permission combinations
- Generate random VLAN comments
- Verify properties hold for all generated inputs (minimum 100 iterations per test)

### Property-Based Testing Configuration

**Framework**: Use [Pest PHP](https://pestphp.com/) with [pest-plugin-faker](https://github.com/pestphp/pest-plugin-faker) for property-based testing in Laravel.

**Test Configuration**:
- Minimum 100 iterations per property test
- Each test tagged with: `Feature: network-vlan-management, Property {number}: {property_text}`
- Use database transactions to isolate tests
- Seed database with minimal required data (test user, Core System tables)

**Example Property Test Structure**:
```php
test('Property 8: Complete IP Address Generation', function () {
    // Generate 100 random VLANs with various CIDR suffixes
    $testCases = collect(range(1, 100))->map(function () {
        return [
            'network' => fake()->ipv4(),
            'cidr' => fake()->numberBetween(24, 30), // Reasonable range for testing
        ];
    });
    
    foreach ($testCases as $case) {
        $vlan = Vlan::factory()->create([
            'network_address' => $case['network'],
            'cidr_suffix' => $case['cidr'],
        ]);
        
        // Calculate expected host count
        $expectedCount = pow(2, 32 - $case['cidr']) - 2; // Exclude network and broadcast
        
        // Verify IP addresses were generated
        expect($vlan->ipAddresses()->count())->toBe($expectedCount);
        
        // Verify network and broadcast addresses are excluded
        $ipAddresses = $vlan->ipAddresses()->pluck('ip_address')->toArray();
        $networkLong = ip2long($case['network']);
        $netmask = (0xFFFFFFFF << (32 - $case['cidr'])) & 0xFFFFFFFF;
        $network = $networkLong & $netmask;
        $broadcast = $network | (~$netmask & 0xFFFFFFFF);
        
        expect($ipAddresses)->not->toContain(long2ip($network));
        expect($ipAddresses)->not->toContain(long2ip($broadcast));
    }
})->group('property-based', 'ip-generation');
```

### Unit Testing Strategy

**Model Tests**:
- Test Vlan model relationships (ipAddresses, comments)
- Test IpAddress model relationships (vlan)
- Test VlanComment model relationships (vlan, user)
- Test model scopes (scanEnabled, needsScan, online, offline)
- Test model accessors (subnet, onlineCount, statusBadgeClass)
- Test model methods (shouldScan, updateFromScan, canDelete)

**Service Tests**:
- Test IpGeneratorService with specific subnet sizes (/24, /31, /32, /16)
- Test IpGeneratorService edge cases (invalid IP, invalid CIDR)
- Test ScannerService ping execution (mock shell_exec)
- Test ScannerService ARP resolution (mock shell_exec)
- Test ScannerService error handling (command failures)
- Test ScannerService scan interval logic

**Controller Tests**:
- Test VlanController CRUD operations
- Test VlanController permission checks
- Test IpAddressController inline updates
- Test VlanCommentController creation and deletion
- Test authorization for comment deletion

**Command Tests**:
- Test NetworkScanCommand execution
- Test NetworkScanCommand with --vlan option
- Test NetworkScanCommand with --force option
- Test NetworkScanCommand error handling

**Validation Tests**:
- Test StoreVlanRequest validation rules
- Test UpdateVlanRequest validation rules
- Test custom validation (gateway in subnet, DHCP range validation)

**Integration Tests**:
- Test complete VLAN creation flow (create VLAN → generate IPs → verify in database)
- Test complete scan flow (create VLAN → enable scan → run command → verify updates)
- Test permission integration (user without permission → access denied)
- Test audit logging integration (perform action → verify audit log entry)

**View Tests** (Laravel Dusk):
- Test VLAN list rendering
- Test VLAN detail rendering with IP addresses
- Test dashboard widget rendering
- Test inline editing functionality
- Test form validation error display
- Test permission-based UI element visibility

### Test Coverage Goals

- Minimum 80% code coverage for all module code
- 100% coverage for validation logic
- 100% coverage for permission checks
- All 80 correctness properties implemented as property-based tests
- All edge cases covered by unit tests
- Integration tests for all major workflows

### Continuous Integration

- Run all tests on every commit
- Run property-based tests with 100 iterations in CI
- Run property-based tests with 1000 iterations nightly
- Generate coverage reports
- Fail build if coverage drops below 80%
- Run static analysis (PHPStan level 8)
- Run code style checks (Laravel Pint)
