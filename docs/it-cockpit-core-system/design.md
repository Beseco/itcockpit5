536291# Design Document: IT Cockpit v5.0 Core System

## Overview

The IT Cockpit v5.0 Core System is built on Laravel 11 and provides a modular platform for IT infrastructure management. The architecture follows Laravel's MVC pattern with a plugin-based module system that allows extensions without modifying core code.

The system consists of three main layers:
1. **Authentication & Authorization Layer**: Handles user authentication, role-based access control, and module-specific permissions
2. **Core Application Layer**: Provides the dashboard, announcement system, and module infrastructure
3. **Module Integration Layer**: Discovers, registers, and integrates external modules through a hook system

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Web Browser                          │
└────────────────────────┬────────────────────────────────────┘
                         │ HTTP/HTTPS
┌────────────────────────▼────────────────────────────────────┐
│                    Laravel Application                      │
│  ┌──────────────────────────────────────────────────────┐  │
│  │              Authentication Layer                     │  │
│  │  (Breeze/Fortify + Custom RBAC Middleware)          │  │
│  └──────────────────────┬───────────────────────────────┘  │
│                         │                                   │
│  ┌──────────────────────▼───────────────────────────────┐  │
│  │              Core Application Layer                   │  │
│  │  ├─ Dashboard Controller                             │  │
│  │  ├─ Announcement Controller                          │  │
│  │  ├─ User Management Controller                       │  │
│  │  └─ Audit Log Controller                             │  │
│  └──────────────────────┬───────────────────────────────┘  │
│                         │                                   │
│  ┌──────────────────────▼───────────────────────────────┐  │
│  │          Module Integration Layer                     │  │
│  │  ├─ Module Scanner                                    │  │
│  │  ├─ Module Registry                                   │  │
│  │  └─ Hook System (Sidebar, Dashboard, Permissions)    │  │
│  └──────────────────────┬───────────────────────────────┘  │
│                         │                                   │
│  ┌──────────────────────▼───────────────────────────────┐  │
│  │              External Modules                         │  │
│  │  (Located in /app/Modules/*)                         │  │
│  └───────────────────────────────────────────────────────┘  │
└────────────────────────┬────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────┐
│                    MySQL Database                           │
│  ├─ users                                                   │
│  ├─ announcements                                           │
│  └─ audit_logs                                              │
└─────────────────────────────────────────────────────────────┘
```

### Directory Structure

```
/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/              # Authentication controllers
│   │   │   ├── DashboardController.php
│   │   │   ├── AnnouncementController.php
│   │   │   ├── UserController.php
│   │   │   └── AuditLogController.php
│   │   └── Middleware/
│   │       ├── CheckRole.php      # Role-based access middleware
│   │       └── CheckModulePermission.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Announcement.php
│   │   └── AuditLog.php
│   ├── Services/
│   │   ├── ModuleScanner.php      # Discovers modules
│   │   ├── ModuleRegistry.php     # Manages registered modules
│   │   └── AuditLogger.php        # Centralized audit logging
│   ├── Modules/                   # Module directory (empty initially)
│   └── Providers/
│       └── ModuleServiceProvider.php
├── database/
│   └── migrations/
│       ├── 2024_01_01_000001_create_users_table.php
│       ├── 2024_01_01_000002_create_announcements_table.php
│       └── 2024_01_01_000003_create_audit_logs_table.php
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php      # Main layout with sidebar
│       │   └── guest.blade.php    # Layout for login/register
│       ├── dashboard.blade.php
│       ├── announcements/
│       ├── users/
│       └── components/
│           ├── announcement-card.blade.php
│           └── module-widget.blade.php
└── routes/
    └── web.php
```

## Components and Interfaces

### 1. Authentication System

**Implementation**: Laravel Breeze with custom role-based extensions

**Components**:
- `LoginController`: Handles user authentication
- `RegisterController`: Handles user registration (admin-only)
- `PasswordResetController`: Handles password reset flow
- `AuthenticatedSessionController`: Manages user sessions

**Key Methods**:
```php
// LoginController
authenticate(Request $request): RedirectResponse
  - Validates credentials
  - Updates last_login_at timestamp
  - Creates audit log entry
  - Redirects to dashboard

// RegisterController (admin-only)
store(Request $request): RedirectResponse
  - Validates input (name, email, password, role)
  - Creates user with hashed password
  - Creates audit log entry
  - Sends welcome email (optional)
```

### 2. Role-Based Access Control (RBAC)

**Middleware**: `CheckRole` and `CheckModulePermission`

**CheckRole Middleware**:
```php
handle(Request $request, Closure $next, string $role): Response
  - Checks if authenticated user has required role
  - Returns 403 if unauthorized
  - Allows super-admin to bypass all checks
```

**CheckModulePermission Middleware**:
```php
handle(Request $request, Closure $next, string $module, string $permission): Response
  - Checks if user has module.{slug}.{permission}
  - Returns 403 if unauthorized
  - Allows super-admin to bypass all checks
```

**Permission Storage**: Using Laravel's built-in Gate system or Spatie Laravel-Permission package

### 3. User Management

**Model**: `User` (extends Authenticatable)

**Attributes**:
- id: bigint (primary key)
- role: enum('super-admin', 'admin', 'user')
- name: string
- email: string (unique)
- password: string (hashed)
- is_active: boolean (default: false)
- last_login_at: datetime (nullable)
- timestamps

**Key Methods**:
```php
// User Model
isSuperAdmin(): bool
isAdmin(): bool
isActive(): bool
hasModulePermission(string $module, string $permission): bool
```

**UserController**:
```php
index(): View
  - Lists all users (paginated)
  - Filters by role, active status

store(Request $request): RedirectResponse
  - Validates input
  - Creates user with hashed password
  - Logs action to audit_logs

update(Request $request, User $user): RedirectResponse
  - Validates input
  - Updates user information
  - Logs action to audit_logs

destroy(User $user): RedirectResponse
  - Soft deletes or hard deletes user
  - Logs action to audit_logs

toggleActive(User $user): RedirectResponse
  - Toggles is_active status
  - Logs action to audit_logs
```

### 4. Dashboard & Traffic Light System

**DashboardController**:
```php
index(): View
  - Fetches active announcements (filtered by time and 8-hour rule)
  - Fetches module widgets (filtered by user permissions)
  - Orders announcements: critical → maintenance → info
  - Returns dashboard view with data
```

**Announcement Filtering Logic**:
```php
getActiveAnnouncements(): Collection
  - WHERE (starts_at IS NULL OR starts_at <= NOW())
  - AND (ends_at IS NULL OR ends_at >= NOW())
  - AND (is_fixed = false OR fixed_at >= NOW() - 8 HOURS)
  - ORDER BY type (critical first), created_at DESC
```

**AnnouncementController**:
```php
index(): View
  - Lists all announcements (admin view)

store(Request $request): RedirectResponse
  - Validates input (type, message, starts_at, ends_at)
  - Ensures starts_at < ends_at if both provided
  - Creates announcement
  - Logs action to audit_logs

update(Request $request, Announcement $announcement): RedirectResponse
  - Validates input
  - Updates announcement
  - Logs action to audit_logs

markAsFixed(Announcement $announcement): RedirectResponse
  - Sets is_fixed = true
  - Sets fixed_at = NOW()
  - Logs action to audit_logs

destroy(Announcement $announcement): RedirectResponse
  - Deletes announcement
  - Logs action to audit_logs
```

### 5. Announcement Model

**Model**: `Announcement`

**Attributes**:
- id: bigint (primary key)
- type: enum('info', 'maintenance', 'critical')
- message: text
- starts_at: datetime (nullable)
- ends_at: datetime (nullable)
- is_fixed: boolean (default: false)
- fixed_at: datetime (nullable)
- timestamps

**Key Methods**:
```php
// Announcement Model
isCritical(): bool
isMaintenance(): bool
isInfo(): bool
isResolved(): bool
  - Returns true if is_fixed = true

isActive(): bool
  - Checks if current time is within starts_at and ends_at
  - Checks if not expired (8-hour rule for fixed announcements)

shouldDisplay(): bool
  - Returns isActive() AND (NOT isResolved() OR fixed_at within 8 hours)

getColorClass(): string
  - Returns Tailwind CSS class based on type and is_fixed status
  - critical + not fixed: 'bg-red-100 border-red-500'
  - critical + fixed: 'bg-green-100 border-green-500'
  - maintenance: 'bg-yellow-100 border-yellow-500'
  - info: 'bg-blue-100 border-blue-500'
```

### 6. Module System

**ModuleScanner Service**:
```php
scan(): array
  - Scans /app/Modules/ directory
  - For each subdirectory:
    - Checks for module.json
    - Validates required fields (name, slug, version)
    - Checks for ServiceProvider class
    - Returns array of module metadata

validateModule(string $path): bool
  - Validates module structure
  - Checks for required files
  - Returns true if valid
```

**ModuleRegistry Service**:
```php
register(array $moduleMetadata): void
  - Registers module with Laravel
  - Loads ServiceProvider
  - Registers routes
  - Registers views
  - Stores metadata in memory

getRegisteredModules(): Collection
  - Returns all registered modules

getModuleBySlug(string $slug): ?array
  - Returns module metadata by slug

isModuleRegistered(string $slug): bool
```

**ModuleServiceProvider**:
```php
boot(): void
  - Calls ModuleScanner to discover modules
  - Registers each discovered module
  - Publishes module assets if needed

register(): void
  - Binds ModuleScanner and ModuleRegistry to container
```

### 7. Hook System

**Hook Types**:
1. **Sidebar Hook**: Allows modules to add navigation items
2. **Dashboard Hook**: Allows modules to add widgets
3. **Permission Hook**: Allows modules to register permissions

**Implementation**: Using Laravel Events or a custom Hook Manager

**HookManager Service**:
```php
registerSidebarItem(string $module, array $item): void
  - Stores sidebar item: ['label', 'route', 'icon', 'permission']

getSidebarItems(User $user): Collection
  - Returns sidebar items filtered by user permissions

registerDashboardWidget(string $module, string $viewPath): void
  - Stores widget view path

getDashboardWidgets(User $user): Collection
  - Returns widget view paths filtered by user permissions

registerPermission(string $module, string $permission, string $description): void
  - Registers custom module permission
```

**Module Integration Example**:
```php
// In module's ServiceProvider
public function boot()
{
    // Register sidebar item
    app(HookManager::class)->registerSidebarItem('inventory', [
        'label' => 'Inventory',
        'route' => 'inventory.index',
        'icon' => 'heroicon-o-cube',
        'permission' => 'module.inventory.view'
    ]);

    // Register dashboard widget
    app(HookManager::class)->registerDashboardWidget(
        'inventory',
        'inventory::widget'
    );

    // Register permissions
    app(HookManager::class)->registerPermission(
        'inventory',
        'view',
        'View inventory module'
    );
}
```

### 8. Audit Logging

**AuditLog Model**:

**Attributes**:
- id: bigint (primary key)
- user_id: bigint (foreign key to users)
- module: string (e.g., 'Core', 'Inventory')
- action: string (e.g., 'User created', 'Announcement updated')
- payload: json (stores before/after data)
- created_at: timestamp

**AuditLogger Service**:
```php
log(string $module, string $action, array $payload = []): void
  - Creates audit log entry
  - Automatically captures current user
  - Stores payload as JSON

logUserAction(string $action, User $user, array $changes = []): void
  - Specialized method for user-related actions

logAnnouncementAction(string $action, Announcement $announcement): void
  - Specialized method for announcement actions

logModuleAction(string $module, string $action, array $data = []): void
  - Specialized method for module actions
```

**AuditLogController**:
```php
index(): View
  - Lists audit logs (super-admin only)
  - Filters by module, user, date range
  - Paginated results

show(AuditLog $log): View
  - Shows detailed log entry with formatted payload
```

## Data Models

### User Model

```php
class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    // Relationships
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    // Methods
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super-admin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super-admin', 'admin']);
    }

    public function hasModulePermission(string $module, string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        return $this->hasPermissionTo("module.{$module}.{$permission}");
    }
}
```

### Announcement Model

```php
class Announcement extends Model
{
    protected $fillable = [
        'type',
        'message',
        'starts_at',
        'ends_at',
        'is_fixed',
        'fixed_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_fixed' => 'boolean',
        'fixed_at' => 'datetime',
    ];

    // Scopes
    public function scopeActive($query)
    {
        $now = now();
        $eightHoursAgo = now()->subHours(8);

        return $query
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', $now);
            })
            ->where(function ($q) use ($eightHoursAgo) {
                $q->where('is_fixed', false)
                  ->orWhere('fixed_at', '>=', $eightHoursAgo);
            });
    }

    public function scopeCritical($query)
    {
        return $query->where('type', 'critical');
    }

    public function scopeMaintenance($query)
    {
        return $query->where('type', 'maintenance');
    }

    public function scopeInfo($query)
    {
        return $query->where('type', 'info');
    }

    // Methods
    public function isCritical(): bool
    {
        return $this->type === 'critical';
    }

    public function isResolved(): bool
    {
        return $this->is_fixed;
    }

    public function getColorClass(): string
    {
        if ($this->isCritical() && !$this->isResolved()) {
            return 'bg-red-100 border-red-500 text-red-900';
        }
        
        if ($this->isCritical() && $this->isResolved()) {
            return 'bg-green-100 border-green-500 text-green-900';
        }
        
        if ($this->type === 'maintenance') {
            return 'bg-yellow-100 border-yellow-500 text-yellow-900';
        }
        
        return 'bg-blue-100 border-blue-500 text-blue-900';
    }

    public function getIconClass(): string
    {
        return match($this->type) {
            'critical' => $this->isResolved() ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-circle',
            'maintenance' => 'heroicon-o-wrench',
            'info' => 'heroicon-o-information-circle',
            default => 'heroicon-o-bell',
        };
    }
}
```

### AuditLog Model

```php
class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'module',
        'action',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
```

### Database Migrations

**users table**:
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->enum('role', ['super-admin', 'admin', 'user'])->default('user');
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->boolean('is_active')->default(false);
    $table->timestamp('last_login_at')->nullable();
    $table->timestamp('email_verified_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
    
    $table->index('email');
    $table->index('role');
    $table->index('is_active');
});
```

**announcements table**:
```php
Schema::create('announcements', function (Blueprint $table) {
    $table->id();
    $table->enum('type', ['info', 'maintenance', 'critical']);
    $table->text('message');
    $table->timestamp('starts_at')->nullable();
    $table->timestamp('ends_at')->nullable();
    $table->boolean('is_fixed')->default(false);
    $table->timestamp('fixed_at')->nullable();
    $table->timestamps();
    
    $table->index('type');
    $table->index('is_fixed');
    $table->index(['starts_at', 'ends_at']);
});
```

**audit_logs table**:
```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('module', 100);
    $table->string('action');
    $table->json('payload')->nullable();
    $table->timestamp('created_at');
    
    $table->index('user_id');
    $table->index('module');
    $table->index('created_at');
});
```


## Correctness Properties

A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.

### Property 1: Password Hashing Invariant

*For any* password provided during user creation or update, the stored password value in the database should be a bcrypt hash and not the plain text password.

**Validates: Requirements 1.2**

### Property 2: Valid Login Creates Session

*For any* user with valid credentials (correct email and password), attempting to log in should result in an authenticated session being created.

**Validates: Requirements 1.3**

### Property 3: Invalid Login Rejection

*For any* login attempt with invalid credentials (incorrect email or password), the system should reject the authentication and not create a session.

**Validates: Requirements 1.4**

### Property 4: Login Timestamp Recording

*For any* successful user login, the `last_login_at` field should be updated to the current timestamp.

**Validates: Requirements 1.5**

### Property 5: Logout Session Termination

*For any* authenticated user session, performing a logout action should terminate that session and prevent further authenticated requests.

**Validates: Requirements 1.7**

### Property 6: Single Role Assignment

*For any* user created in the system, exactly one role from the set {super-admin, admin, user} should be assigned.

**Validates: Requirements 2.2**

### Property 7: Super Admin Universal Access

*For any* system function or module, a user with the super-admin role should have access to it.

**Validates: Requirements 2.4**

### Property 8: Standard User Module Restriction

*For any* standard user and any module, the user should only have access to that module if they have been explicitly granted the `module.{slug}.view` permission.

**Validates: Requirements 2.6**

### Property 9: Widget Visibility Based on Permissions

*For any* user and any module widget, the widget should be displayed on the dashboard if and only if the user has the `module.{slug}.view` permission (or is a super-admin).

**Validates: Requirements 3.3, 3.4, 12.3**

### Property 10: Edit Permission Access Control

*For any* user and any module, the user should be able to modify module configuration if and only if they have the `module.{slug}.edit` permission (or are a super-admin).

**Validates: Requirements 3.5, 3.6**

### Property 11: Deactivated User Login Prevention

*For any* user account with `is_active` set to false, login attempts should be rejected regardless of credential validity.

**Validates: Requirements 4.4**

### Property 12: Email Uniqueness Constraint

*For any* attempt to create or update a user, if the email address already exists for a different user, the operation should be rejected with a validation error.

**Validates: Requirements 4.6**

### Property 13: Announcement Time Window Display

*For any* announcement with `starts_at` and `ends_at` timestamps, the announcement should only be included in the dashboard display if the current time is between `starts_at` and `ends_at` (inclusive).

**Validates: Requirements 5.6**

### Property 14: Fixed Announcement Styling Change

*For any* critical announcement, when `is_fixed` is set to true, the display styling should change from red to green.

**Validates: Requirements 5.7**

### Property 15: Eight Hour Removal Rule

*For any* announcement with `is_fixed` set to true, if the `fixed_at` timestamp is more than 8 hours in the past, the announcement should not be displayed on the dashboard.

**Validates: Requirements 5.8**

### Property 16: Mark as Fixed Updates Fields

*For any* critical announcement, when the "mark as fixed" action is performed, both `is_fixed` should be set to true and `fixed_at` should be set to the current timestamp.

**Validates: Requirements 5.10**

### Property 17: Announcement Date Validation

*For any* announcement creation or update where both `starts_at` and `ends_at` are provided, if `starts_at` is not before `ends_at`, the operation should be rejected with a validation error.

**Validates: Requirements 6.4**

### Property 18: Valid Module Registration

*For any* module folder in `/app/Modules/` that contains a valid ServiceProvider and valid `module.json`, the module should be successfully registered with the system.

**Validates: Requirements 7.2**

### Property 19: Module Resource Registration

*For any* registered module that contains a `Routes/web.php` file or `Views/` directory, those routes and views should be accessible through Laravel's routing and view systems.

**Validates: Requirements 7.4, 7.5**

### Property 20: Module Loading Failure Isolation

*For any* module that fails to load due to errors, the system should log the error and continue loading other modules without interruption.

**Validates: Requirements 7.6**

### Property 21: Hook Registration and Permission-Based Display

*For any* module that registers sidebar items or dashboard widgets through the hook system, those items should only be displayed to users who have the `module.{slug}.view` permission (or are super-admins).

**Validates: Requirements 8.4, 8.5, 8.6**

### Property 22: Comprehensive Action Logging

*For any* significant system action (user creation/update/deletion, login, announcement creation/update/deletion, module enable/disable, permission changes), an audit log entry should be created.

**Validates: Requirements 9.1, 9.2, 9.3, 9.4, 9.5**

### Property 23: Audit Log Entry Completeness

*For any* audit log entry created, it should contain all required fields: `user_id`, `module`, `action`, `timestamp`, and a `payload` JSON object with detailed change information.

**Validates: Requirements 9.6, 9.7**

### Property 24: Audit Log Immutability

*For any* audit log entry, attempts to modify or delete it should be prevented (no update or delete operations should succeed).

**Validates: Requirements 9.9**

### Property 25: Password Reset Token Generation

*For any* password reset request, a cryptographically secure token should be generated and associated with the user's account.

**Validates: Requirements 13.3**

### Property 26: Password Reset Email Token Inclusion

*For any* password reset email sent, the email content should include the generated reset token.

**Validates: Requirements 13.4**

### Property 27: Password Reset Token Expiration

*For any* password reset token, if more than 60 minutes have elapsed since generation, the token should be considered invalid and rejected.

**Validates: Requirements 13.5**

### Property 28: Password Reset Token Validation

*For any* password change attempt via reset, the operation should only succeed if a valid, non-expired token is provided.

**Validates: Requirements 13.6**

### Property 29: Required Module Metadata Fields

*For any* module being loaded, if the `module.json` file is missing any of the required fields (`name`, `slug`, `version`), the module should be rejected and an error should be logged.

**Validates: Requirements 14.2, 14.3, 14.4**

### Property 30: Invalid Module Metadata Handling

*For any* module with invalid or missing `module.json` metadata, the system should log an error and skip loading that module without affecting other modules.

**Validates: Requirements 14.7**

### Property 31: Announcement Ordering

*For any* collection of announcements displayed on the dashboard, critical announcements should appear before maintenance announcements, which should appear before info announcements.

**Validates: Requirements 12.6**

## Error Handling

### Authentication Errors

**Invalid Credentials**:
- Return 401 Unauthorized with message "Invalid email or password"
- Do not reveal whether email or password was incorrect (security)
- Log failed login attempt

**Inactive Account**:
- Return 403 Forbidden with message "Account is inactive"
- Log attempted login to inactive account

**Session Expiration**:
- Redirect to login page with message "Session expired, please log in again"
- Clear any remaining session data

### Authorization Errors

**Insufficient Role**:
- Return 403 Forbidden with message "Insufficient permissions"
- Log unauthorized access attempt
- Do not reveal what permissions are required

**Missing Module Permission**:
- Return 403 Forbidden or hide UI elements
- Log unauthorized module access attempt

### Validation Errors

**User Creation/Update**:
- Email already exists: "Email address is already in use"
- Invalid email format: "Please provide a valid email address"
- Password too short: "Password must be at least 8 characters"
- Invalid role: "Invalid role specified"

**Announcement Creation/Update**:
- Invalid date range: "Start date must be before end date"
- Missing required fields: "Type and message are required"
- Invalid type: "Type must be one of: info, maintenance, critical"

**Module Loading**:
- Missing module.json: Log "Module {name} missing module.json" and skip
- Invalid JSON: Log "Module {name} has invalid JSON in module.json" and skip
- Missing required fields: Log "Module {name} missing required field: {field}" and skip
- ServiceProvider not found: Log "Module {name} ServiceProvider not found" and skip

### Database Errors

**Connection Failure**:
- Display generic error message to user
- Log detailed error with connection parameters (excluding password)
- Attempt reconnection with exponential backoff

**Constraint Violations**:
- Foreign key violation: "Cannot delete user with existing audit logs"
- Unique constraint: "Email address already exists"
- Not null constraint: "Required field {field} cannot be empty"

**Query Errors**:
- Log full error details including query and parameters
- Display generic "Database error occurred" to user
- Alert administrators for critical errors

### Module Errors

**Module Loading Failure**:
- Log error with module name and exception details
- Continue loading other modules
- Display admin notification about failed module

**Module Runtime Errors**:
- Catch exceptions in module code
- Log error with module context
- Display error message in module widget area
- Do not crash entire application

### Email Errors

**SMTP Connection Failure**:
- Log error with SMTP configuration (excluding password)
- Queue email for retry
- Display message "Email queued for delivery"

**Invalid Email Address**:
- Validate before sending
- Return validation error to user
- Do not queue invalid emails

## Testing Strategy

### Dual Testing Approach

The IT Cockpit core system will use both unit testing and property-based testing to ensure comprehensive coverage:

**Unit Tests**: Focus on specific examples, edge cases, and integration points
- Specific user scenarios (super-admin access, standard user restrictions)
- Edge cases (empty announcement lists, modules with no permissions)
- Error conditions (invalid credentials, malformed module.json)
- Integration between components (authentication → audit logging)

**Property-Based Tests**: Verify universal properties across all inputs
- Generate random users with various roles and permissions
- Generate random announcements with various types and time windows
- Generate random module configurations
- Verify properties hold for all generated inputs (minimum 100 iterations per test)

### Property-Based Testing Configuration

**Framework**: Use [Pest PHP](https://pestphp.com/) with the [pest-plugin-faker](https://github.com/pestphp/pest-plugin-faker) for property-based testing in Laravel.

**Test Configuration**:
- Minimum 100 iterations per property test
- Each test tagged with: `Feature: it-cockpit-core-system, Property {number}: {property_text}`
- Use database transactions to isolate tests
- Seed database with minimal required data

**Example Property Test Structure**:
```php
test('Property 1: Password Hashing Invariant', function () {
    // Generate random passwords
    $passwords = collect(range(1, 100))->map(fn() => Str::random(rand(8, 32)));
    
    foreach ($passwords as $password) {
        $user = User::factory()->create(['password' => $password]);
        
        // Verify stored password is hashed, not plain text
        expect($user->password)->not->toBe($password);
        expect(Hash::check($password, $user->password))->toBeTrue();
    }
})->group('property-based', 'authentication');
```

### Unit Testing Strategy

**Authentication Tests**:
- Test login with valid credentials
- Test login with invalid credentials
- Test login with inactive account
- Test logout functionality
- Test password reset flow
- Test session management

**Authorization Tests**:
- Test super-admin access to all functions
- Test admin access to user management
- Test standard user module restrictions
- Test permission checking middleware

**Announcement Tests**:
- Test announcement creation with all types
- Test time window filtering
- Test 8-hour removal rule
- Test mark as fixed functionality
- Test announcement ordering

**Module System Tests**:
- Test module discovery in /app/Modules/
- Test module registration with valid metadata
- Test module rejection with invalid metadata
- Test hook system registration
- Test permission-based widget display

**Audit Logging Tests**:
- Test log creation for all action types
- Test log immutability
- Test log querying and filtering
- Test payload structure

**Database Tests**:
- Test migrations create correct schema
- Test model relationships
- Test database constraints (unique email, foreign keys)
- Test query scopes

### Integration Testing

**End-to-End Flows**:
- User registration → login → dashboard access
- Create announcement → display on dashboard → mark as fixed → auto-removal
- Module installation → registration → widget display → permission check
- User action → audit log creation → log viewing

**Browser Testing** (Laravel Dusk):
- Test complete user workflows in browser
- Test JavaScript interactions (Alpine.js)
- Test responsive layout (Tailwind CSS)
- Test form submissions and validations

### Test Coverage Goals

- Minimum 80% code coverage for core functionality
- 100% coverage for authentication and authorization logic
- 100% coverage for audit logging
- All 31 correctness properties implemented as property-based tests
- All edge cases covered by unit tests

### Continuous Integration

- Run all tests on every commit
- Run property-based tests with 100 iterations in CI
- Run property-based tests with 1000 iterations nightly
- Generate coverage reports
- Fail build if coverage drops below threshold
