# Module Development Guide

## Introduction

This guide explains how to create custom modules for the IT Cockpit v5.0 Core System. Modules are self-contained extensions that integrate seamlessly with the core system through a hook-based architecture, allowing you to add functionality without modifying core code.

## What is a Module?

A module is a self-contained functional extension located in the `/app/Modules/` directory. Each module can:

- Register navigation items in the sidebar
- Display widgets on the dashboard
- Define custom routes and controllers
- Create custom views and components
- Register module-specific permissions
- Integrate with the audit logging system

## Module Directory Structure

A complete module follows this directory structure:

```
/app/Modules/YourModule/
├── Http/
│   └── Controllers/
│       └── YourModuleController.php
├── Providers/
│   └── YourModuleServiceProvider.php
├── Routes/
│   └── web.php
├── Views/
│   ├── index.blade.php
│   └── widget.blade.php
└── module.json
```

### Directory Breakdown

- **Http/Controllers/**: Contains your module's controllers
- **Providers/**: Contains the ServiceProvider that registers your module
- **Routes/**: Contains route definitions for your module
- **Views/**: Contains Blade templates for your module's pages and widgets
- **module.json**: Module metadata file (required)

## Step-by-Step Module Creation

### Step 1: Create Module Directory Structure

Create your module directory under `/app/Modules/`:

```bash
mkdir -p app/Modules/YourModule/{Http/Controllers,Providers,Routes,Views}
```

### Step 2: Create module.json

The `module.json` file contains metadata about your module. This file is **required** for the module to be discovered.

**Location**: `/app/Modules/YourModule/module.json`

**Required Fields**:
- `name`: Display name of your module
- `slug`: Unique identifier (lowercase, no spaces, used for permissions)
- `version`: Semantic version number

**Optional Fields**:
- `description`: Brief description of module functionality
- `author`: Module author name

**Example**:

```json
{
    "name": "Inventory Management",
    "slug": "inventory",
    "version": "1.0.0",
    "description": "Manage IT inventory and asset tracking",
    "author": "Your Name"
}
```

**Important Notes**:
- The `slug` field is used for permission namespacing (`module.{slug}.view`)
- Use lowercase letters, numbers, and hyphens only in the slug
- The slug must be unique across all modules

### Step 3: Create ServiceProvider

The ServiceProvider is the heart of your module. It registers hooks with the core system.

**Location**: `/app/Modules/YourModule/Providers/YourModuleServiceProvider.php`

**Template**:

```php
<?php

namespace App\Modules\YourModule\Providers;

use App\Services\HookManager;
use Illuminate\Support\ServiceProvider;

class YourModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services to the container
     */
    public function register(): void
    {
        // Register any bindings or singletons here
    }

    /**
     * Bootstrap services - register hooks with the system
     */
    public function boot(): void
    {
        $hookManager = app(HookManager::class);

        // Register sidebar navigation item
        $hookManager->registerSidebarItem('yourmodule', [
            'label' => 'Your Module',
            'route' => 'yourmodule.index',
            'icon' => 'heroicon-o-cube',
            'permission' => 'module.yourmodule.view'
        ]);

        // Register dashboard widget
        $hookManager->registerDashboardWidget('yourmodule', 'yourmodule::widget');

        // Register permissions
        $hookManager->registerPermission('yourmodule', 'view', 'View your module');
        $hookManager->registerPermission('yourmodule', 'edit', 'Edit your module');
    }
}
```

**Key Points**:
- Namespace must match your module path
- Class name must end with `ServiceProvider`
- Use the `boot()` method to register hooks
- The slug in hook registrations must match your `module.json` slug

### Step 4: Register Sidebar Navigation

Use the `HookManager` to add your module to the sidebar navigation.

**Method**: `registerSidebarItem(string $module, array $item)`

**Parameters**:
- `$module`: Your module slug (from module.json)
- `$item`: Array with the following keys:
  - `label` (required): Display text for the menu item
  - `route` (required): Laravel route name
  - `icon` (optional): Heroicon class name
  - `permission` (optional): Permission required to view this item

**Example**:

```php
$hookManager->registerSidebarItem('inventory', [
    'label' => 'Inventory',
    'route' => 'inventory.index',
    'icon' => 'heroicon-o-cube',
    'permission' => 'module.inventory.view'
]);
```

**Available Icons**:
Use Heroicons (outline style): `heroicon-o-{icon-name}`
- `heroicon-o-cube` - Box/package icon
- `heroicon-o-users` - Users icon
- `heroicon-o-cog` - Settings icon
- `heroicon-o-chart-bar` - Chart icon
- See [Heroicons](https://heroicons.com/) for more options

### Step 5: Register Dashboard Widget

Dashboard widgets appear on the main dashboard when users have the appropriate permissions.

**Method**: `registerDashboardWidget(string $module, string $viewPath)`

**Parameters**:
- `$module`: Your module slug
- `$viewPath`: Blade view path (using `::` notation)

**Example**:

```php
$hookManager->registerDashboardWidget('inventory', 'inventory::widget');
```

**Widget View Location**: `/app/Modules/YourModule/Views/widget.blade.php`

**Widget Template**:

```blade
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Your Module</h3>
            <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
        </div>
        
        <p class="text-gray-600 text-sm mb-4">
            Brief description of your module's current status or key metrics.
        </p>

        <div class="space-y-3">
            <!-- Add your widget content here -->
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                <span class="text-sm text-gray-700">Status</span>
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                    Active
                </span>
            </div>
        </div>

        <div class="mt-4">
            <a href="{{ route('yourmodule.index') }}" 
               class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded transition-colors">
                View Module
            </a>
        </div>
    </div>
</div>
```

### Step 6: Register Permissions

Permissions control access to your module's functionality.

**Method**: `registerPermission(string $module, string $permission, string $description)`

**Parameters**:
- `$module`: Your module slug
- `$permission`: Permission type (e.g., 'view', 'edit', 'delete')
- `$description`: Human-readable description

**Standard Permissions**:
- `view`: Read-only access to the module
- `edit`: Ability to modify module data

**Example**:

```php
$hookManager->registerPermission('inventory', 'view', 'View inventory module');
$hookManager->registerPermission('inventory', 'edit', 'Edit inventory items');
$hookManager->registerPermission('inventory', 'delete', 'Delete inventory items');
```

**Permission Format**: Permissions are automatically formatted as `module.{slug}.{permission}`

For example:
- `module.inventory.view`
- `module.inventory.edit`
- `module.inventory.delete`

### Step 7: Define Routes

Create route definitions for your module.

**Location**: `/app/Modules/YourModule/Routes/web.php`

**Template**:

```php
<?php

use App\Modules\YourModule\Http\Controllers\YourModuleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Your Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'module.permission:yourmodule,view'])
    ->group(function () {
        Route::get('/', [YourModuleController::class, 'index'])->name('index');
    });

// Routes requiring edit permission
Route::middleware(['auth', 'module.permission:yourmodule,edit'])
    ->group(function () {
        Route::post('/', [YourModuleController::class, 'store'])->name('store');
        Route::put('/{id}', [YourModuleController::class, 'update'])->name('update');
        Route::delete('/{id}', [YourModuleController::class, 'destroy'])->name('destroy');
    });
```

**Important Notes**:
- Always use the `auth` middleware to require authentication
- Use `module.permission:{slug},{permission}` middleware to check permissions
- Route names are automatically prefixed with your module slug
- Full route names will be: `yourmodule.index`, `yourmodule.store`, etc.

### Step 8: Create Controllers

Controllers handle your module's business logic.

**Location**: `/app/Modules/YourModule/Http/Controllers/YourModuleController.php`

**Template**:

```php
<?php

namespace App\Modules\YourModule\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class YourModuleController extends Controller
{
    /**
     * Display the module main page
     */
    public function index(): View
    {
        // Fetch your data here
        
        return view('yourmodule::index');
    }

    /**
     * Store a new resource
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate and store data
        
        return redirect()->route('yourmodule.index')
            ->with('success', 'Item created successfully');
    }

    /**
     * Update an existing resource
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        // Validate and update data
        
        return redirect()->route('yourmodule.index')
            ->with('success', 'Item updated successfully');
    }

    /**
     * Delete a resource
     */
    public function destroy(int $id): RedirectResponse
    {
        // Delete data
        
        return redirect()->route('yourmodule.index')
            ->with('success', 'Item deleted successfully');
    }
}
```

**Key Points**:
- Extend `App\Http\Controllers\Controller`
- Use proper type hints for parameters and return types
- Return views using the `modulename::viewname` notation
- Use Laravel's redirect helpers for form submissions

### Step 9: Create Views

Create Blade templates for your module's pages.

**Main Page Location**: `/app/Modules/YourModule/Views/index.blade.php`

**Template**:

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Your Module') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Welcome to Your Module
                    </h3>
                    
                    <!-- Your module content here -->
                    
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

**View Naming Convention**:
- Use the `modulename::viewname` format when referencing views
- Views are automatically loaded from your module's `Views/` directory
- Example: `view('inventory::index')` loads `/app/Modules/Inventory/Views/index.blade.php`

## Permission System

### How Permissions Work

The IT Cockpit uses a permission-based access control system:

1. **Super Admins**: Have access to everything (bypass all permission checks)
2. **Admins**: Have access to user management and modules they're granted
3. **Standard Users**: Only have access to modules they're explicitly granted

### Checking Permissions in Code

**In Controllers**:

```php
// Check if user has permission
if (!auth()->user()->hasModulePermission('inventory', 'edit')) {
    abort(403, 'Unauthorized');
}
```

**In Blade Templates**:

```blade
@if(auth()->user()->hasModulePermission('inventory', 'edit'))
    <button>Edit Item</button>
@endif
```

**In Routes** (recommended):

```php
Route::middleware(['auth', 'module.permission:inventory,edit'])
    ->group(function () {
        // Protected routes here
    });
```

### Permission Best Practices

1. **Always use the middleware**: Protect routes with `module.permission` middleware
2. **Register all permissions**: Use `registerPermission()` in your ServiceProvider
3. **Use standard names**: Stick to `view`, `edit`, `delete` for consistency
4. **Check in views**: Hide UI elements users can't access
5. **Fail securely**: Default to denying access if permission is unclear

## Hook System Reference

### Available Hooks

The IT Cockpit provides three main hooks for module integration:

#### 1. Sidebar Hook

Adds navigation items to the main sidebar.

```php
$hookManager->registerSidebarItem(string $module, array $item)
```

**Item Structure**:
```php
[
    'label' => 'Display Text',      // Required
    'route' => 'route.name',        // Required
    'icon' => 'heroicon-o-icon',    // Optional
    'permission' => 'module.slug.view' // Optional
]
```

#### 2. Dashboard Widget Hook

Adds widgets to the dashboard grid.

```php
$hookManager->registerDashboardWidget(string $module, string $viewPath)
```

**Parameters**:
- `$module`: Module slug
- `$viewPath`: Blade view path (e.g., `'inventory::widget'`)

#### 3. Permission Hook

Registers custom permissions for your module.

```php
$hookManager->registerPermission(string $module, string $permission, string $description)
```

**Parameters**:
- `$module`: Module slug
- `$permission`: Permission name (e.g., 'view', 'edit')
- `$description`: Human-readable description

### Hook Execution Order

1. Module scanner discovers modules in `/app/Modules/`
2. Module metadata is loaded from `module.json`
3. ServiceProvider is instantiated and `register()` is called
4. ServiceProvider's `boot()` method is called
5. Hooks are registered with the HookManager
6. Routes and views are registered with Laravel

## Complete Example Module

Here's a complete, working example of a simple module:

### Directory Structure

```
/app/Modules/TaskManager/
├── Http/
│   └── Controllers/
│       └── TaskController.php
├── Providers/
│   └── TaskManagerServiceProvider.php
├── Routes/
│   └── web.php
├── Views/
│   ├── index.blade.php
│   └── widget.blade.php
└── module.json
```

### module.json

```json
{
    "name": "Task Manager",
    "slug": "taskmanager",
    "version": "1.0.0",
    "description": "Simple task management module",
    "author": "IT Cockpit Team"
}
```

### TaskManagerServiceProvider.php

```php
<?php

namespace App\Modules\TaskManager\Providers;

use App\Services\HookManager;
use Illuminate\Support\ServiceProvider;

class TaskManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $hookManager = app(HookManager::class);

        $hookManager->registerSidebarItem('taskmanager', [
            'label' => 'Tasks',
            'route' => 'taskmanager.index',
            'icon' => 'heroicon-o-clipboard-list',
            'permission' => 'module.taskmanager.view'
        ]);

        $hookManager->registerDashboardWidget('taskmanager', 'taskmanager::widget');

        $hookManager->registerPermission('taskmanager', 'view', 'View tasks');
        $hookManager->registerPermission('taskmanager', 'edit', 'Edit tasks');
    }
}
```

### web.php

```php
<?php

use App\Modules\TaskManager\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'module.permission:taskmanager,view'])
    ->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
    });
```

### TaskController.php

```php
<?php

namespace App\Modules\TaskManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(): View
    {
        return view('taskmanager::index');
    }
}
```

### widget.blade.php

```blade
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Tasks</h3>
            <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
        </div>
        
        <p class="text-gray-600 text-sm mb-4">
            You have 5 pending tasks
        </p>

        <div class="mt-4">
            <a href="{{ route('taskmanager.index') }}" 
               class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded transition-colors">
                View All Tasks
            </a>
        </div>
    </div>
</div>
```

### index.blade.php

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Task Manager') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Your Tasks
                    </h3>
                    
                    <p class="text-gray-700">
                        Task list would go here...
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

## Best Practices

### Module Design

1. **Keep modules focused**: Each module should have a single, clear purpose
2. **Use descriptive names**: Choose clear, meaningful names for your module
3. **Follow Laravel conventions**: Use Laravel's naming and structure conventions
4. **Document your code**: Add PHPDoc comments to classes and methods

### Security

1. **Always authenticate**: Use the `auth` middleware on all routes
2. **Check permissions**: Use `module.permission` middleware appropriately
3. **Validate input**: Always validate user input in controllers
4. **Sanitize output**: Use Blade's `{{ }}` syntax to escape output
5. **Fail securely**: Default to denying access when in doubt

### Performance

1. **Lazy load data**: Only fetch data when needed
2. **Use caching**: Cache expensive operations when appropriate
3. **Optimize queries**: Use eager loading to avoid N+1 queries
4. **Keep widgets light**: Dashboard widgets should load quickly

### User Experience

1. **Provide feedback**: Show success/error messages after actions
2. **Use consistent styling**: Follow Tailwind CSS patterns from core
3. **Make it responsive**: Ensure your module works on all screen sizes
4. **Add helpful text**: Include descriptions and help text where needed

### Testing

1. **Write tests**: Create feature tests for your module's functionality
2. **Test permissions**: Verify permission checks work correctly
3. **Test edge cases**: Consider what happens with empty data, errors, etc.
4. **Test integration**: Ensure your module works with the core system

## Troubleshooting

### Module Not Appearing

**Problem**: Your module doesn't show up in the sidebar or dashboard.

**Solutions**:
1. Check that `module.json` exists and has all required fields
2. Verify the ServiceProvider class name matches the file name
3. Ensure the namespace is correct
4. Check Laravel logs for errors: `storage/logs/laravel.log`
5. Clear Laravel cache: `php artisan cache:clear`

### Permission Denied Errors

**Problem**: Users get 403 errors when accessing your module.

**Solutions**:
1. Verify permissions are registered in ServiceProvider
2. Check that users have been granted the permission
3. Ensure middleware is correctly applied to routes
4. Verify the permission slug matches your module slug

### Views Not Loading

**Problem**: Views return 404 or "View not found" errors.

**Solutions**:
1. Check view file exists in `Views/` directory
2. Verify you're using the correct view path: `modulename::viewname`
3. Ensure file names match exactly (case-sensitive)
4. Check file permissions on the Views directory

### Routes Not Working

**Problem**: Routes return 404 errors.

**Solutions**:
1. Verify `web.php` exists in the `Routes/` directory
2. Check route names are unique
3. Ensure middleware is correctly applied
4. Run `php artisan route:list` to see registered routes
5. Clear route cache: `php artisan route:clear`

## Advanced Topics

### Adding Database Tables

If your module needs database tables:

1. Create migrations in your module:
   ```
   /app/Modules/YourModule/Database/Migrations/
   ```

2. Register migrations in your ServiceProvider:
   ```php
   public function boot(): void
   {
       $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
       
       // ... rest of boot method
   }
   ```

3. Run migrations:
   ```bash
   php artisan migrate
   ```

### Adding Models

Create models in your module:

```
/app/Modules/YourModule/Models/YourModel.php
```

```php
<?php

namespace App\Modules\YourModule\Models;

use Illuminate\Database\Eloquent\Model;

class YourModel extends Model
{
    protected $fillable = ['field1', 'field2'];
}
```

### Adding API Routes

Create API routes in your module:

```
/app/Modules/YourModule/Routes/api.php
```

Register in ServiceProvider:

```php
public function boot(): void
{
    $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
    
    // ... rest of boot method
}
```

### Integrating with Audit Logging

Use the AuditLogger service to log module actions:

```php
use App\Services\AuditLogger;

class YourModuleController extends Controller
{
    public function store(Request $request)
    {
        // Create your resource
        $item = YourModel::create($request->validated());
        
        // Log the action
        app(AuditLogger::class)->logModuleAction(
            'yourmodule',
            'Item created',
            ['item_id' => $item->id, 'name' => $item->name]
        );
        
        return redirect()->route('yourmodule.index');
    }
}
```

### Publishing Assets

If your module has CSS/JS assets:

1. Create assets directory:
   ```
   /app/Modules/YourModule/Resources/assets/
   ```

2. Register in ServiceProvider:
   ```php
   public function boot(): void
   {
       $this->publishes([
           __DIR__.'/../Resources/assets' => public_path('modules/yourmodule'),
       ], 'yourmodule-assets');
       
       // ... rest of boot method
   }
   ```

3. Publish assets:
   ```bash
   php artisan vendor:publish --tag=yourmodule-assets
   ```

## Reference

### Required Files Checklist

- [ ] `module.json` with name, slug, and version
- [ ] ServiceProvider in `Providers/` directory
- [ ] At least one route in `Routes/web.php`
- [ ] At least one controller in `Http/Controllers/`
- [ ] At least one view in `Views/` directory
- [ ] Dashboard widget view in `Views/widget.blade.php`

### ServiceProvider Methods

| Method | Purpose | When Called |
|--------|---------|-------------|
| `register()` | Register bindings | Before boot |
| `boot()` | Register hooks, routes, views | After all providers registered |

### HookManager Methods

| Method | Purpose | Parameters |
|--------|---------|------------|
| `registerSidebarItem()` | Add sidebar menu item | `$module`, `$item[]` |
| `registerDashboardWidget()` | Add dashboard widget | `$module`, `$viewPath` |
| `registerPermission()` | Register permission | `$module`, `$permission`, `$description` |

### Middleware Reference

| Middleware | Purpose | Usage |
|------------|---------|-------|
| `auth` | Require authentication | `Route::middleware(['auth'])` |
| `module.permission` | Check module permission | `Route::middleware(['module.permission:slug,view'])` |

### Common Heroicons

| Icon | Class Name |
|------|------------|
| Cube/Box | `heroicon-o-cube` |
| Users | `heroicon-o-users` |
| Settings | `heroicon-o-cog` |
| Chart | `heroicon-o-chart-bar` |
| Clipboard | `heroicon-o-clipboard-list` |
| Document | `heroicon-o-document-text` |
| Folder | `heroicon-o-folder` |
| Home | `heroicon-o-home` |

## Getting Help

If you encounter issues not covered in this guide:

1. Check the Example module in `/app/Modules/Example/` for reference
2. Review Laravel logs in `storage/logs/laravel.log`
3. Run `php artisan route:list` to debug routing issues
4. Check the core system documentation in `/docs/`
5. Review the requirements and design documents in `.kiro/specs/`

## Conclusion

You now have everything you need to create custom modules for the IT Cockpit system. Start with the simple example, then expand with your own functionality. Remember to follow Laravel conventions, implement proper security checks, and test your module thoroughly before deployment.

Happy coding!
