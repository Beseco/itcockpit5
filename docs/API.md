# IT Cockpit Core System - Service API Documentation

This document provides comprehensive API documentation for the core services in the IT Cockpit v5.0 system.

## Table of Contents

- [AuditLogger Service](#auditlogger-service)
- [ModuleScanner Service](#modulescanner-service)
- [ModuleRegistry Service](#moduleregistry-service)
- [HookManager Service](#hookmanager-service)

---

## AuditLogger Service

**Namespace:** `App\Services\AuditLogger`

**Purpose:** Provides centralized audit logging functionality for tracking all significant system actions.

**Requirements:** 7.1, 7.2, 8.1, 8.2, 8.3, 9.6, 9.7

### Overview

The AuditLogger service creates immutable audit log entries that track user actions throughout the system. All audit logs include the user who performed the action, the module context, a description of the action, and a JSON payload with detailed information.

### Methods

#### `log()`

Log a generic action to the audit log.

**Signature:**
```php
public function log(
    string $module, 
    string $action, 
    array $payload = [], 
    ?int $userId = null
): AuditLog
```

**Parameters:**
- `$module` (string, required): The module context (e.g., 'Core', 'User', 'Announcement')
- `$action` (string, required): The action description (e.g., 'User created', 'Login successful')
- `$payload` (array, optional): Additional data to store (before/after values, etc.)
- `$userId` (int|null, optional): User ID performing the action (defaults to current authenticated user)

**Returns:** `AuditLog` - The created audit log entry

**Example:**
```php
use App\Services\AuditLogger;

$auditLogger = app(AuditLogger::class);

$auditLogger->log(
    'Core',
    'System configuration updated',
    ['setting' => 'smtp_host', 'old_value' => 'old.smtp.com', 'new_value' => 'new.smtp.com']
);
```

---

#### `logUserAction()`

Log a user-related action with standardized payload structure.

**Signature:**
```php
public function logUserAction(
    string $action, 
    User $user, 
    array $changes = []
): AuditLog
```

**Parameters:**
- `$action` (string, required): The action description (e.g., 'created', 'updated', 'deleted')
- `$user` (User, required): The user being acted upon
- `$changes` (array, optional): Array of changes (before/after values)

**Returns:** `AuditLog` - The created audit log entry

**Payload Structure:**
```php
[
    'user_id' => 123,
    'user_email' => 'user@example.com',
    'user_name' => 'John Doe',
    'changes' => [
        'role' => ['old' => 'user', 'new' => 'admin'],
        'is_active' => ['old' => false, 'new' => true]
    ]
]
```

**Example:**
```php
use App\Services\AuditLogger;
use App\Models\User;

$auditLogger = app(AuditLogger::class);
$user = User::find(1);

$auditLogger->logUserAction(
    'updated',
    $user,
    [
        'role' => ['old' => 'user', 'new' => 'admin'],
        'is_active' => ['old' => false, 'new' => true]
    ]
);
```

---

#### `logAnnouncementAction()`

Log an announcement-related action with standardized payload structure.

**Signature:**
```php
public function logAnnouncementAction(
    string $action, 
    Announcement $announcement
): AuditLog
```

**Parameters:**
- `$action` (string, required): The action description (e.g., 'created', 'updated', 'deleted', 'marked as fixed')
- `$announcement` (Announcement, required): The announcement being acted upon

**Returns:** `AuditLog` - The created audit log entry

**Payload Structure:**
```php
[
    'announcement_id' => 456,
    'type' => 'critical',
    'message' => 'Server maintenance scheduled'
]
```

**Example:**
```php
use App\Services\AuditLogger;
use App\Models\Announcement;

$auditLogger = app(AuditLogger::class);
$announcement = Announcement::find(1);

$auditLogger->logAnnouncementAction('marked as fixed', $announcement);
```

---

#### `logModuleAction()`

Log a module-related action.

**Signature:**
```php
public function logModuleAction(
    string $module, 
    string $action, 
    array $data = []
): AuditLog
```

**Parameters:**
- `$module` (string, required): The module name
- `$action` (string, required): The action description (e.g., 'enabled', 'disabled', 'configured')
- `$data` (array, optional): Additional data about the action

**Returns:** `AuditLog` - The created audit log entry

**Example:**
```php
use App\Services\AuditLogger;

$auditLogger = app(AuditLogger::class);

$auditLogger->logModuleAction(
    'Inventory',
    'enabled',
    ['version' => '1.0.0', 'auto_enabled' => true]
);
```

---

## ModuleScanner Service

**Namespace:** `App\Services\ModuleScanner`

**Purpose:** Discovers and validates modules in the `/app/Modules/` directory.

**Requirements:** 7.1, 7.2

### Overview

The ModuleScanner service scans the modules directory, validates module structure and metadata, and returns an array of valid modules ready for registration. It performs comprehensive validation including checking for required files, valid JSON, and required metadata fields.

### Methods

#### `scan()`

Scan the `/app/Modules/` directory for valid modules.

**Signature:**
```php
public function scan(): array
```

**Parameters:** None

**Returns:** `array` - Array of valid module metadata

**Return Structure:**
```php
[
    [
        'name' => 'Inventory Management',
        'slug' => 'inventory',
        'version' => '1.0.0',
        'description' => 'Manage IT inventory',
        'author' => 'IT Team'
    ],
    // ... more modules
]
```

**Behavior:**
- Returns empty array if `/app/Modules/` directory doesn't exist
- Logs warning if modules directory is missing
- Validates each module using `validateModule()`
- Loads metadata for valid modules
- Logs success for each loaded module
- Continues scanning even if individual modules fail

**Example:**
```php
use App\Services\ModuleScanner;

$scanner = app(ModuleScanner::class);
$modules = $scanner->scan();

foreach ($modules as $module) {
    echo "Found module: {$module['name']} (v{$module['version']})\n";
}
```

---

#### `validateModule()`

Validate that a module has all required files and structure.

**Signature:**
```php
public function validateModule(string $path): bool
```

**Parameters:**
- `$path` (string, required): Path to the module directory

**Returns:** `bool` - True if module is valid, false otherwise

**Validation Checks:**
1. Checks for `module.json` file existence
2. Validates JSON syntax in `module.json`
3. Checks for required fields: `name`, `slug`, `version`
4. Checks for ServiceProvider class at `Providers/{ModuleName}ServiceProvider.php`

**Logs Errors For:**
- Missing `module.json`
- Invalid JSON syntax
- Missing required fields
- Missing ServiceProvider class

**Example:**
```php
use App\Services\ModuleScanner;

$scanner = app(ModuleScanner::class);
$isValid = $scanner->validateModule(app_path('Modules/Inventory'));

if ($isValid) {
    echo "Module is valid and ready for registration\n";
}
```

---

## ModuleRegistry Service

**Namespace:** `App\Services\ModuleRegistry`

**Purpose:** Manages the collection of registered modules and provides lookup functionality.

**Requirements:** 8.1, 8.2, 8.3

### Overview

The ModuleRegistry service maintains an in-memory collection of all registered modules. It provides methods to register new modules, retrieve module information, and check registration status. The registry prevents duplicate registrations and provides fast lookup by module slug.

### Methods

#### `register()`

Register a module with the system.

**Signature:**
```php
public function register(array $moduleMetadata): void
```

**Parameters:**
- `$moduleMetadata` (array, required): Module metadata from `module.json`

**Required Metadata Fields:**
- `slug` (string): Unique module identifier
- `name` (string): Human-readable module name
- `version` (string): Module version

**Optional Metadata Fields:**
- `description` (string): Module description
- `author` (string): Module author

**Behavior:**
- Validates that `slug` field exists
- Checks for duplicate registration (logs warning if already registered)
- Stores module metadata in internal collection
- Logs successful registration

**Example:**
```php
use App\Services\ModuleRegistry;

$registry = app(ModuleRegistry::class);

$registry->register([
    'name' => 'Inventory Management',
    'slug' => 'inventory',
    'version' => '1.0.0',
    'description' => 'Manage IT inventory',
    'author' => 'IT Team'
]);
```

---

#### `getRegisteredModules()`

Get all registered modules.

**Signature:**
```php
public function getRegisteredModules(): Collection
```

**Parameters:** None

**Returns:** `Collection` - Collection of module metadata indexed by slug

**Example:**
```php
use App\Services\ModuleRegistry;

$registry = app(ModuleRegistry::class);
$modules = $registry->getRegisteredModules();

foreach ($modules as $slug => $metadata) {
    echo "{$metadata['name']} ({$slug}): v{$metadata['version']}\n";
}
```

---

#### `getModuleBySlug()`

Find a module by its slug.

**Signature:**
```php
public function getModuleBySlug(string $slug): ?array
```

**Parameters:**
- `$slug` (string, required): Module slug to search for

**Returns:** `array|null` - Module metadata or null if not found

**Example:**
```php
use App\Services\ModuleRegistry;

$registry = app(ModuleRegistry::class);
$module = $registry->getModuleBySlug('inventory');

if ($module) {
    echo "Module: {$module['name']}\n";
    echo "Version: {$module['version']}\n";
} else {
    echo "Module not found\n";
}
```

---

#### `isModuleRegistered()`

Check if a module is registered.

**Signature:**
```php
public function isModuleRegistered(string $slug): bool
```

**Parameters:**
- `$slug` (string, required): Module slug to check

**Returns:** `bool` - True if module is registered, false otherwise

**Example:**
```php
use App\Services\ModuleRegistry;

$registry = app(ModuleRegistry::class);

if ($registry->isModuleRegistered('inventory')) {
    echo "Inventory module is registered\n";
} else {
    echo "Inventory module is not registered\n";
}
```

---

## HookManager Service

**Namespace:** `App\Services\HookManager`

**Purpose:** Manages the hook system that allows modules to integrate with the core system.

**Requirements:** 8.1, 8.2, 8.3, 9.6, 9.7

### Overview

The HookManager service provides three types of hooks for module integration:
1. **Sidebar Hooks**: Register navigation menu items
2. **Dashboard Hooks**: Register dashboard widgets
3. **Permission Hooks**: Register custom module permissions

All hooks respect user permissions and automatically filter content based on the authenticated user's access level.

### Methods

#### `registerSidebarItem()`

Register a sidebar navigation item for a module.

**Signature:**
```php
public function registerSidebarItem(string $module, array $item): void
```

**Parameters:**
- `$module` (string, required): Module slug
- `$item` (array, required): Sidebar item configuration

**Item Structure:**
```php
[
    'label' => 'Inventory',              // Required: Display text
    'route' => 'inventory.index',        // Required: Laravel route name
    'icon' => 'heroicon-o-cube',         // Optional: Icon class
    'permission' => 'module.inventory.view' // Optional: Custom permission
]
```

**Behavior:**
- Validates required fields (`label`, `route`)
- Adds module context to item
- Logs error if required fields missing
- Logs successful registration

**Example:**
```php
use App\Services\HookManager;

$hookManager = app(HookManager::class);

$hookManager->registerSidebarItem('inventory', [
    'label' => 'Inventory',
    'route' => 'inventory.index',
    'icon' => 'heroicon-o-cube',
    'permission' => 'module.inventory.view'
]);
```

---

#### `getSidebarItems()`

Get sidebar items filtered by user permissions.

**Signature:**
```php
public function getSidebarItems(User $user): Collection
```

**Parameters:**
- `$user` (User, required): User to filter items for

**Returns:** `Collection` - Collection of sidebar items the user can access

**Filtering Logic:**
- Super admins see all items
- If item has custom `permission`, checks that permission
- Otherwise checks default `module.{slug}.view` permission
- Items without determinable permissions are hidden

**Example:**
```php
use App\Services\HookManager;
use Illuminate\Support\Facades\Auth;

$hookManager = app(HookManager::class);
$user = Auth::user();

$sidebarItems = $hookManager->getSidebarItems($user);

foreach ($sidebarItems as $item) {
    echo "<a href='" . route($item['route']) . "'>{$item['label']}</a>\n";
}
```

---

#### `registerDashboardWidget()`

Register a dashboard widget for a module.

**Signature:**
```php
public function registerDashboardWidget(string $module, string $viewPath): void
```

**Parameters:**
- `$module` (string, required): Module slug
- `$viewPath` (string, required): Blade view path for the widget (e.g., 'inventory::widget')

**Behavior:**
- Validates that view path is not empty
- Stores widget with module context
- Logs error if view path missing
- Logs successful registration

**Example:**
```php
use App\Services\HookManager;

$hookManager = app(HookManager::class);

$hookManager->registerDashboardWidget('inventory', 'inventory::dashboard.widget');
```

---

#### `getDashboardWidgets()`

Get dashboard widgets filtered by user permissions.

**Signature:**
```php
public function getDashboardWidgets(User $user): Collection
```

**Parameters:**
- `$user` (User, required): User to filter widgets for

**Returns:** `Collection` - Collection of widget configurations the user can access

**Widget Structure:**
```php
[
    'module' => 'inventory',
    'viewPath' => 'inventory::dashboard.widget'
]
```

**Filtering Logic:**
- Super admins see all widgets
- Checks `module.{slug}.view` permission for each widget
- Widgets without determinable permissions are hidden

**Example:**
```php
use App\Services\HookManager;
use Illuminate\Support\Facades\Auth;

$hookManager = app(HookManager::class);
$user = Auth::user();

$widgets = $hookManager->getDashboardWidgets($user);

foreach ($widgets as $widget) {
    echo view($widget['viewPath'])->render();
}
```

---

#### `registerPermission()`

Register a custom permission for a module.

**Signature:**
```php
public function registerPermission(
    string $module, 
    string $permission, 
    string $description
): void
```

**Parameters:**
- `$module` (string, required): Module slug
- `$permission` (string, required): Permission name (e.g., 'view', 'edit', 'delete')
- `$description` (string, required): Human-readable description

**Permission Format:** `module.{slug}.{permission}`

**Behavior:**
- Validates required fields
- Formats permission as `module.{slug}.{permission}`
- Stores permission metadata
- Logs error if required fields missing
- Logs successful registration

**Example:**
```php
use App\Services\HookManager;

$hookManager = app(HookManager::class);

$hookManager->registerPermission(
    'inventory',
    'view',
    'View inventory module and dashboard widget'
);

$hookManager->registerPermission(
    'inventory',
    'edit',
    'Edit inventory items and configuration'
);

$hookManager->registerPermission(
    'inventory',
    'delete',
    'Delete inventory items'
);
```

---

#### `getRegisteredPermissions()`

Get all registered permissions.

**Signature:**
```php
public function getRegisteredPermissions(): Collection
```

**Parameters:** None

**Returns:** `Collection` - Collection of registered permissions

**Permission Structure:**
```php
[
    'name' => 'module.inventory.view',
    'module' => 'inventory',
    'permission' => 'view',
    'description' => 'View inventory module and dashboard widget'
]
```

**Example:**
```php
use App\Services\HookManager;

$hookManager = app(HookManager::class);
$permissions = $hookManager->getRegisteredPermissions();

foreach ($permissions as $permission) {
    echo "{$permission['name']}: {$permission['description']}\n";
}
```

---

## Service Integration Example

Here's a complete example showing how a module would use all services together:

```php
<?php

namespace App\Modules\Inventory\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\HookManager;
use App\Services\AuditLogger;

class InventoryServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $hookManager = app(HookManager::class);
        
        // Register sidebar navigation
        $hookManager->registerSidebarItem('inventory', [
            'label' => 'Inventory',
            'route' => 'inventory.index',
            'icon' => 'heroicon-o-cube',
            'permission' => 'module.inventory.view'
        ]);
        
        // Register dashboard widget
        $hookManager->registerDashboardWidget(
            'inventory',
            'inventory::dashboard.widget'
        );
        
        // Register permissions
        $hookManager->registerPermission(
            'inventory',
            'view',
            'View inventory module'
        );
        
        $hookManager->registerPermission(
            'inventory',
            'edit',
            'Edit inventory items'
        );
        
        // Log module registration
        $auditLogger = app(AuditLogger::class);
        $auditLogger->logModuleAction(
            'Inventory',
            'registered',
            ['version' => '1.0.0']
        );
    }
    
    public function register()
    {
        // Register module services
    }
}
```

---

## Error Handling

All services implement comprehensive error handling:

### AuditLogger
- Automatically uses current authenticated user if `$userId` not provided
- Creates timestamp automatically
- Never throws exceptions (audit logging should not break application flow)

### ModuleScanner
- Logs warnings for missing modules directory
- Logs errors for invalid modules but continues scanning
- Returns empty array if no valid modules found
- Validates JSON syntax and required fields

### ModuleRegistry
- Logs warnings for duplicate registrations
- Logs errors for missing slug field
- Prevents duplicate module registration
- Returns null for non-existent modules

### HookManager
- Logs errors for missing required fields
- Validates all input before registration
- Filters items/widgets based on permissions
- Returns empty collections if no accessible items
- Never throws exceptions for permission checks

---

## Best Practices

### AuditLogger
1. Always log significant actions (create, update, delete)
2. Include relevant context in payload
3. Use specialized methods (`logUserAction`, `logAnnouncementAction`) when available
4. Include before/after values for updates

### ModuleScanner
1. Run scan during application bootstrap
2. Handle empty results gracefully
3. Check logs for module loading errors
4. Validate module structure before deployment

### ModuleRegistry
1. Check if module is registered before accessing
2. Use `getModuleBySlug()` for safe lookups
3. Don't assume modules are always available
4. Handle null returns from `getModuleBySlug()`

### HookManager
1. Always specify required fields when registering
2. Use descriptive labels and permission descriptions
3. Follow permission naming convention: `module.{slug}.{action}`
4. Test widget views before registration
5. Always filter items/widgets by user permissions

---

## Testing

All services include comprehensive test coverage. See the test files for examples:

- `tests/Feature/AuditLoggerTest.php`
- `tests/Feature/ModuleScannerTest.php`
- `tests/Feature/ModuleRegistryTest.php`
- `tests/Feature/HookManagerTest.php`

---

## Version History

- **v1.0.0** (2024-01-01): Initial API documentation
