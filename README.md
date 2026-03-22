# IT Cockpit v5.0 Core System

A Laravel-based administrative platform for centralized IT infrastructure management with modular architecture, role-based access control, and a traffic light announcement system.

## Features

- **Role-Based Access Control**: Three-tier user system (Super Admin, Admin, User) with granular module permissions
- **Traffic Light Announcement System**: Color-coded status messages (critical/red, maintenance/yellow, info/blue, resolved/green)
- **Modular Architecture**: Plugin-based module system for extending functionality without modifying core code
- **Audit Logging**: Comprehensive tracking of all system actions
- **Dashboard Widgets**: Customizable dashboard with module-specific widgets
- **Secure Authentication**: Laravel Breeze with bcrypt password hashing and password reset functionality

## Requirements

- PHP 8.2 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer 2.x
- Node.js 18+ and npm
- Web server (Apache/Nginx)

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url> it-cockpit
cd it-cockpit
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 3. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Database

Edit `.env` and set your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=it_cockpit
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Create the database:

```bash
mysql -u root -p -e "CREATE DATABASE it_cockpit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 5. Configure Email (Optional)

For password reset functionality, configure SMTP settings in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@itcockpit.local
MAIL_FROM_NAME="${APP_NAME}"
```

For local development, use [MailHog](https://github.com/mailhog/MailHog) or [Mailpit](https://github.com/axllent/mailpit):

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
```

### 6. Run Migrations

```bash
php artisan migrate
```

### 7. Seed Initial Data

```bash
# Seed all initial data (users and sample announcements)
php artisan db:seed

# Or seed individually
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=AnnouncementSeeder
```

**Default Users Created:**

| Email | Password | Role | Status |
|-------|----------|------|--------|
| admin@itcockpit.local | password | super-admin | Active |
| manager@itcockpit.local | password | admin | Active |
| user@itcockpit.local | password | user | Active |

⚠️ **Change these passwords immediately in production!**

### 8. Build Assets

```bash
# Development
npm run dev

# Production
npm run build
```

### 9. Start Development Server

```bash
php artisan serve
```

Visit `http://localhost:8000` and log in with one of the default accounts.

## Configuration

### Application Settings

Key configuration options in `.env`:

```env
APP_NAME="IT Cockpit v5.0"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
```

### Permission System

The system uses [Spatie Laravel-Permission](https://spatie.be/docs/laravel-permission) for role and permission management.

**Module Permission Format:**
- View access: `module.{slug}.view`
- Edit access: `module.{slug}.edit`

Super admins bypass all permission checks automatically.

## Seeding Initial Data

### User Seeder

Creates three default users with different roles:

```bash
php artisan db:seed --class=UserSeeder
```

### Announcement Seeder

Creates sample announcements for testing the traffic light system:

```bash
php artisan db:seed --class=AnnouncementSeeder
```

### Custom Seeding

To create your own initial data, edit `database/seeders/DatabaseSeeder.php` or create new seeder classes:

```bash
php artisan make:seeder CustomSeeder
```

## Module Development

IT Cockpit uses a modular architecture that allows you to extend functionality without modifying core code.

### Module Structure

Create modules in `app/Modules/`:

```
app/Modules/YourModule/
├── Providers/
│   └── YourModuleServiceProvider.php
├── Http/
│   └── Controllers/
│       └── YourController.php
├── Views/
│   ├── index.blade.php
│   └── widget.blade.php
├── Routes/
│   └── web.php
└── module.json
```

### Module Metadata (module.json)

```json
{
  "name": "Your Module",
  "slug": "your-module",
  "version": "1.0.0",
  "description": "Module description",
  "author": "Your Name"
}
```

**Required fields:** `name`, `slug`, `version`

### Service Provider

```php
<?php

namespace App\Modules\YourModule\Providers;

use Illuminate\Support\ServiceProvider;

class YourModuleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Register routes
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        
        // Register views
        $this->loadViewsFrom(__DIR__.'/../Views', 'your-module');
        
        // Register sidebar item
        app(\App\Services\HookManager::class)->registerSidebarItem('your-module', [
            'label' => 'Your Module',
            'route' => 'your-module.index',
            'icon' => 'heroicon-o-cube',
            'permission' => 'module.your-module.view'
        ]);
        
        // Register dashboard widget
        app(\App\Services\HookManager::class)->registerDashboardWidget(
            'your-module',
            'your-module::widget'
        );
        
        // Register permissions
        app(\App\Services\HookManager::class)->registerPermission(
            'your-module',
            'view',
            'View your module'
        );
        
        app(\App\Services\HookManager::class)->registerPermission(
            'your-module',
            'edit',
            'Edit your module'
        );
    }

    public function register()
    {
        //
    }
}
```

### Module Routes

Create `Routes/web.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Modules\YourModule\Http\Controllers\YourController;

Route::middleware(['auth', 'check.module.permission:your-module,view'])
    ->prefix('your-module')
    ->name('your-module.')
    ->group(function () {
        Route::get('/', [YourController::class, 'index'])->name('index');
        
        Route::middleware('check.module.permission:your-module,edit')->group(function () {
            Route::post('/', [YourController::class, 'store'])->name('store');
            Route::put('/{id}', [YourController::class, 'update'])->name('update');
            Route::delete('/{id}', [YourController::class, 'destroy'])->name('destroy');
        });
    });
```

### Dashboard Widget

Create `Views/widget.blade.php`:

```blade
<div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold mb-4">{{ __('Your Module') }}</h3>
    <p class="text-gray-600">Widget content here</p>
    <a href="{{ route('your-module.index') }}" class="text-blue-600 hover:text-blue-800 mt-4 inline-block">
        {{ __('View Details') }} →
    </a>
</div>
```

### Module Discovery

Modules are automatically discovered on application boot. The system:
1. Scans `app/Modules/` directory
2. Validates `module.json` metadata
3. Registers ServiceProvider
4. Loads routes and views
5. Registers hooks (sidebar, widgets, permissions)

If a module fails to load, an error is logged and other modules continue loading.

### Permission Checking

In controllers:

```php
// Check if user has permission
if (!auth()->user()->hasModulePermission('your-module', 'edit')) {
    abort(403);
}
```

In Blade views:

```blade
@can('module.your-module.edit')
    <button>Edit</button>
@endcan
```

### Audit Logging

Log module actions:

```php
use App\Services\AuditLogger;

app(AuditLogger::class)->logModuleAction(
    'your-module',
    'Item created',
    ['item_id' => $item->id, 'name' => $item->name]
);
```

## Testing

The project uses [Pest PHP](https://pestphp.com/) for testing with both unit tests and property-based tests.

### Run All Tests

```bash
php artisan test
# or
./vendor/bin/pest
```

### Run Specific Test Suites

```bash
# Feature tests only
./vendor/bin/pest --group=feature

# Property-based tests only
./vendor/bin/pest --group=property-based

# Authentication tests
./vendor/bin/pest --group=authentication
```

### Run Tests with Coverage

```bash
./vendor/bin/pest --coverage
```

### Writing Tests

Create new tests:

```bash
php artisan make:test YourFeatureTest
```

Example test:

```php
<?php

use App\Models\User;

test('super admin can access all modules', function () {
    $admin = User::factory()->create(['role' => 'super-admin']);
    
    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertStatus(200);
});
```

## User Roles and Permissions

### Roles

| Role | Description | Access Level |
|------|-------------|--------------|
| super-admin | Full system access | All modules, user management, system configuration |
| admin | Administrative access | User management, assigned modules |
| user | Standard user | Assigned modules only |

### Permission Management

Grant module permissions to users:

```php
use Spatie\Permission\Models\Permission;

// Create permission
Permission::create(['name' => 'module.inventory.view']);
Permission::create(['name' => 'module.inventory.edit']);

// Assign to user
$user->givePermissionTo('module.inventory.view');

// Check permission
if ($user->hasPermissionTo('module.inventory.view')) {
    // User can view inventory module
}
```

### Middleware

Protect routes with role and permission middleware:

```php
// Require specific role
Route::middleware('check.role:admin')->group(function () {
    // Admin-only routes
});

// Require module permission
Route::middleware('check.module.permission:inventory,view')->group(function () {
    // Routes requiring inventory view permission
});
```

## Announcement System

### Announcement Types

| Type | Color | Use Case |
|------|-------|----------|
| critical | Red | System outages, critical issues |
| maintenance | Yellow | Scheduled maintenance windows |
| info | Blue | General information, updates |
| resolved | Green | Fixed critical issues (auto-removed after 8 hours) |

### Creating Announcements

Via UI: Navigate to Announcements → Create New

Via code:

```php
use App\Models\Announcement;

Announcement::create([
    'type' => 'maintenance',
    'message' => 'Scheduled maintenance tonight 10 PM - 2 AM',
    'starts_at' => now()->setHour(22),
    'ends_at' => now()->addDay()->setHour(2),
]);
```

### Time Windows

Announcements with `starts_at` and `ends_at` only display during that window. Leave `ends_at` null for indefinite display.

### Mark as Fixed

Critical announcements can be marked as fixed, changing them to green. They auto-remove 8 hours after being marked fixed.

## Audit Logging

All significant actions are logged to the `audit_logs` table:

- User creation, updates, deletion
- User logins
- Announcement changes
- Module enable/disable
- Permission changes

View logs (super-admin only): Navigate to Audit Logs

Logs are immutable and cannot be modified or deleted.

## Troubleshooting

### Module Not Loading

Check logs:
```bash
tail -f storage/logs/laravel.log
```

Common issues:
- Missing or invalid `module.json`
- ServiceProvider class not found
- Syntax errors in module code

### Permission Denied Errors

Ensure:
- User has correct role
- Module permissions are assigned
- Middleware is properly configured

### Email Not Sending

Verify SMTP configuration in `.env` and test:

```bash
php artisan tinker
Mail::raw('Test email', function($msg) {
    $msg->to('test@example.com')->subject('Test');
});
```

### Database Connection Issues

Check:
- Database exists
- Credentials in `.env` are correct
- MySQL service is running
- User has proper database privileges

## Development Workflow

1. **Create feature branch**
   ```bash
   git checkout -b feature/your-feature
   ```

2. **Make changes**
   - Follow Laravel coding standards
   - Write tests for new functionality
   - Update documentation

3. **Run tests**
   ```bash
   ./vendor/bin/pest
   ```

4. **Check code style**
   ```bash
   ./vendor/bin/pint
   ```

5. **Commit and push**
   ```bash
   git add .
   git commit -m "Add your feature"
   git push origin feature/your-feature
   ```

## Production Deployment

### Pre-Deployment Checklist

- [ ] Update `.env` with production values
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Change default user passwords
- [ ] Configure proper SMTP settings
- [ ] Set up SSL certificate
- [ ] Configure web server (Apache/Nginx)
- [ ] Set proper file permissions
- [ ] Enable caching

### Deployment Steps

```bash
# Pull latest code
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Run migrations
php artisan migrate --force

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers if using
php artisan queue:restart
```

### File Permissions

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Security Considerations

- Change all default passwords immediately
- Use strong passwords (minimum 12 characters)
- Enable HTTPS in production
- Keep Laravel and dependencies updated
- Review audit logs regularly
- Limit super-admin accounts
- Use environment-specific `.env` files
- Never commit `.env` to version control

## Documentation

Additional documentation available in `docs/`:

- `architecture.md` - System architecture overview
- `module_guideline.md` - Detailed module development guide
- `PASSWORD_RESET_CONFIGURATION.md` - Email configuration guide

## Support

For issues, questions, or contributions:

1. Check existing documentation
2. Review audit logs for errors
3. Check Laravel logs in `storage/logs/`
4. Consult Laravel documentation: https://laravel.com/docs

## License

This project is proprietary software. All rights reserved.

## Credits

Built with:
- [Laravel 11](https://laravel.com)
- [Laravel Breeze](https://laravel.com/docs/starter-kits#breeze)
- [Spatie Laravel-Permission](https://spatie.be/docs/laravel-permission)
- [Tailwind CSS](https://tailwindcss.com)
- [Alpine.js](https://alpinejs.dev)
- [Pest PHP](https://pestphp.com)
