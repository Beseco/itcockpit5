# Implementation Plan: IT Cockpit v5.0 Core System

## Overview

This implementation plan breaks down the IT Cockpit v5.0 core system into discrete, incremental coding tasks. The approach follows a bottom-up strategy: database → models → authentication → core features → module system → UI → testing. Each task builds on previous work, ensuring no orphaned code and continuous integration.

## Tasks

- [x] 1. Set up Laravel project and core dependencies
  - Install Laravel 11 with PHP 8.2+ requirements
  - Install Laravel Breeze for authentication scaffolding
  - Install Spatie Laravel-Permission package for RBAC
  - Configure Tailwind CSS and Alpine.js
  - Set up Pest PHP for testing
  - Configure database connection in `.env`
  - _Requirements: 1.1, 1.2, 1.3, 15.1, 15.2, 15.3, 15.4_

- [ ] 2. Create database migrations for core tables
  - [x] 2.1 Create users table migration
    - Add columns: id, role (enum), name, email (unique), password, is_active (default false), last_login_at (nullable), timestamps
    - Add indexes on email, role, is_active
    - _Requirements: 10.1, 10.6, 10.8_
  
  - [x] 2.2 Create announcements table migration
    - Add columns: id, type (enum: info, maintenance, critical), message (text), starts_at (nullable), ends_at (nullable), is_fixed (default false), fixed_at (nullable), timestamps
    - Add indexes on type, is_fixed, starts_at, ends_at
    - _Requirements: 10.2, 10.7, 10.9_
  
  - [x] 2.3 Create audit_logs table migration
    - Add columns: id, user_id (foreign key), module (string), action (string), payload (json nullable), created_at (timestamp only)
    - Add foreign key constraint to users table with cascade delete
    - Add indexes on user_id, module, created_at
    - _Requirements: 10.3, 10.5_
  
  - [x] 2.4 Run migrations and verify schema
    - Execute migrations
    - Verify all tables, columns, and constraints are created correctly
    - _Requirements: 10.1, 10.2, 10.3_

- [ ] 3. Create Eloquent models with relationships and scopes
  - [x] 3.1 Create User model
    - Extend Authenticatable
    - Add fillable fields: name, email, password, role, is_active
    - Add hidden fields: password, remember_token
    - Add casts: is_active (boolean), last_login_at (datetime)
    - Add relationship: hasMany(AuditLog)
    - Add scopes: active(), byRole()
    - Add methods: isSuperAdmin(), isAdmin(), hasModulePermission()
    - _Requirements: 2.1, 2.2, 2.4, 2.5, 2.6_
  
  - [ ]* 3.2 Write property test for User model
    - **Property 6: Single Role Assignment**
    - **Validates: Requirements 2.2**
  
  - [x] 3.3 Create Announcement model
    - Add fillable fields: type, message, starts_at, ends_at, is_fixed, fixed_at
    - Add casts: starts_at (datetime), ends_at (datetime), is_fixed (boolean), fixed_at (datetime)
    - Add scopes: active(), critical(), maintenance(), info()
    - Add methods: isCritical(), isResolved(), getColorClass(), getIconClass()
    - Implement active() scope with time window and 8-hour logic
    - _Requirements: 5.1, 5.6, 5.7, 5.8_
  
  - [ ]* 3.4 Write property tests for Announcement model
    - **Property 13: Announcement Time Window Display**
    - **Property 15: Eight Hour Removal Rule**
    - **Validates: Requirements 5.6, 5.8**
  
  - [x] 3.5 Create AuditLog model
    - Disable updated_at timestamp (only created_at)
    - Add fillable fields: user_id, module, action, payload
    - Add casts: payload (array), created_at (datetime)
    - Add relationship: belongsTo(User)
    - Add scopes: byModule(), byUser(), recent()
    - _Requirements: 9.6, 9.7_

- [ ] 4. Implement authentication system with Laravel Breeze
  - [x] 4.1 Install and configure Laravel Breeze
    - Run `php artisan breeze:install blade`
    - Customize authentication views with Tailwind styling
    - _Requirements: 1.1, 1.6, 1.7_
  
  - [x] 4.2 Extend login controller to update last_login_at
    - Override authenticated() method in LoginController
    - Update last_login_at timestamp on successful login
    - _Requirements: 1.5_
  
  - [ ]* 4.3 Write property tests for authentication
    - **Property 2: Valid Login Creates Session**
    - **Property 3: Invalid Login Rejection**
    - **Property 4: Login Timestamp Recording**
    - **Property 5: Logout Session Termination**
    - **Validates: Requirements 1.3, 1.4, 1.5, 1.7**
  
  - [x] 4.3 Implement password hashing in User model
    - Add mutator for password attribute to hash before storage
    - Ensure bcrypt is used (Laravel default)
    - _Requirements: 1.2_
  
  - [ ]* 4.4 Write property test for password hashing
    - **Property 1: Password Hashing Invariant**
    - **Validates: Requirements 1.2**
  
  - [x] 4.5 Configure password reset functionality
    - Ensure password reset routes are registered
    - Configure email settings in `.env`
    - Test password reset email sending
    - _Requirements: 1.6, 13.1, 13.2, 13.3, 13.4, 13.5, 13.6_
  
  - [ ]* 4.6 Write property tests for password reset
    - **Property 25: Password Reset Token Generation**
    - **Property 26: Password Reset Email Token Inclusion**
    - **Property 27: Password Reset Token Expiration**
    - **Property 28: Password Reset Token Validation**
    - **Validates: Requirements 13.3, 13.4, 13.5, 13.6**

- [x] 5. Checkpoint - Ensure authentication tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 6. Implement role-based access control (RBAC)
  - [x] 6.1 Install and configure Spatie Laravel-Permission
    - Run `composer require spatie/laravel-permission`
    - Publish and run migrations
    - Add HasRoles trait to User model
    - _Requirements: 2.1, 3.1, 3.2_
  
  - [x] 6.2 Create CheckRole middleware
    - Implement handle() method to check user role
    - Allow super-admin to bypass all checks
    - Return 403 for unauthorized access
    - Register middleware in Kernel
    - _Requirements: 2.4, 2.5, 2.6_
  
  - [ ]* 6.3 Write property tests for role-based access
    - **Property 7: Super Admin Universal Access**
    - **Property 8: Standard User Module Restriction**
    - **Validates: Requirements 2.4, 2.6**
  
  - [x] 6.4 Create CheckModulePermission middleware
    - Implement handle() method to check module.{slug}.{permission}
    - Allow super-admin to bypass all checks
    - Return 403 for unauthorized access
    - Register middleware in Kernel
    - _Requirements: 3.1, 3.2, 3.5, 3.6_
  
  - [ ]* 6.5 Write property tests for module permissions
    - **Property 9: Widget Visibility Based on Permissions**
    - **Property 10: Edit Permission Access Control**
    - **Validates: Requirements 3.3, 3.4, 3.5, 3.6**

- [ ] 7. Create AuditLogger service for centralized logging
  - [x] 7.1 Create AuditLogger service class
    - Implement log() method with user_id, module, action, payload
    - Implement specialized methods: logUserAction(), logAnnouncementAction(), logModuleAction()
    - Automatically capture current authenticated user
    - Bind service to container in AppServiceProvider
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7_
  
  - [ ]* 7.2 Write property tests for audit logging
    - **Property 22: Comprehensive Action Logging**
    - **Property 23: Audit Log Entry Completeness**
    - **Property 24: Audit Log Immutability**
    - **Validates: Requirements 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7, 9.9**

- [ ] 8. Implement user management functionality
  - [x] 8.1 Create UserController with CRUD operations
    - Implement index() to list users with filters (role, active status)
    - Implement store() to create users with validation
    - Implement update() to modify user information
    - Implement destroy() to delete users
    - Implement toggleActive() to activate/deactivate accounts
    - Integrate AuditLogger for all actions
    - Apply CheckRole middleware (admin and super-admin only)
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6_
  
  - [ ]* 8.2 Write property tests for user management
    - **Property 11: Deactivated User Login Prevention**
    - **Property 12: Email Uniqueness Constraint**
    - **Validates: Requirements 4.4, 4.6**
  
  - [x] 8.3 Create user management views
    - Create users/index.blade.php for user list
    - Create users/create.blade.php for user creation form
    - Create users/edit.blade.php for user editing form
    - Add Tailwind styling and Alpine.js interactions
    - _Requirements: 11.1, 11.6_
  
  - [x] 8.4 Register user management routes
    - Add resource routes for UserController
    - Add custom route for toggleActive
    - Apply auth and role middleware
    - _Requirements: 4.1, 4.2, 4.3, 4.5_

- [ ] 9. Implement announcement management functionality
  - [x] 9.1 Create AnnouncementController with CRUD operations
    - Implement index() to list all announcements
    - Implement store() to create announcements with validation (starts_at < ends_at)
    - Implement update() to modify announcements
    - Implement destroy() to delete announcements
    - Implement markAsFixed() to mark critical announcements as resolved
    - Integrate AuditLogger for all actions
    - Apply CheckRole middleware (admin and super-admin only)
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 5.10_
  
  - [ ]* 9.2 Write property tests for announcement management
    - **Property 14: Fixed Announcement Styling Change**
    - **Property 16: Mark as Fixed Updates Fields**
    - **Property 17: Announcement Date Validation**
    - **Validates: Requirements 5.7, 5.10, 6.4**
  
  - [x] 9.3 Create announcement management views
    - Create announcements/index.blade.php for announcement list
    - Create announcements/create.blade.php for creation form
    - Create announcements/edit.blade.php for editing form
    - Add Tailwind styling and Alpine.js interactions
    - _Requirements: 11.1, 11.6_
  
  - [x] 9.4 Register announcement management routes
    - Add resource routes for AnnouncementController
    - Add custom route for markAsFixed
    - Apply auth and role middleware
    - _Requirements: 6.1, 6.2, 6.3_

- [ ] 10. Checkpoint - Ensure core functionality tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 11. Implement dashboard with traffic light system
  - [x] 11.1 Create DashboardController
    - Implement index() method
    - Fetch active announcements using Announcement::active() scope
    - Order announcements: critical → maintenance → info
    - Fetch module widgets (placeholder for now)
    - Return dashboard view with data
    - _Requirements: 5.2, 5.3, 5.4, 5.5, 5.6, 5.8, 12.6_
  
  - [ ]* 11.2 Write property test for announcement ordering
    - **Property 31: Announcement Ordering**
    - **Validates: Requirements 12.6**
  
  - [x] 11.3 Create announcement card component
    - Create components/announcement-card.blade.php
    - Display announcement type, message, timestamps
    - Apply color styling based on type and is_fixed status
    - Add "Mark as Fixed" button for critical announcements (admin only)
    - Use Alpine.js for interactive elements
    - _Requirements: 5.2, 5.3, 5.4, 5.5, 5.7, 5.9_
  
  - [x] 11.4 Create dashboard view
    - Create dashboard.blade.php
    - Display announcements at top using announcement-card component
    - Add placeholder for module widgets grid
    - Apply responsive Tailwind grid layout
    - _Requirements: 11.4, 12.1, 12.2_
  
  - [x] 11.5 Register dashboard route
    - Add route for DashboardController@index
    - Set as default authenticated landing page
    - Apply auth middleware
    - _Requirements: 11.4_

- [ ] 12. Implement module system infrastructure
  - [x] 12.1 Create ModuleScanner service
    - Implement scan() method to scan /app/Modules/ directory
    - Implement validateModule() to check for required files
    - Check for module.json and validate required fields (name, slug, version)
    - Check for ServiceProvider class
    - Return array of valid module metadata
    - Log errors for invalid modules
    - _Requirements: 7.1, 7.3, 14.1, 14.2, 14.3, 14.4, 14.7_
  
  - [ ]* 12.2 Write property tests for module scanning
    - **Property 18: Valid Module Registration**
    - **Property 29: Required Module Metadata Fields**
    - **Property 30: Invalid Module Metadata Handling**
    - **Validates: Requirements 7.2, 14.2, 14.3, 14.4, 14.7**
  
  - [x] 12.3 Create ModuleRegistry service
    - Implement register() to register module with Laravel
    - Implement getRegisteredModules() to return all modules
    - Implement getModuleBySlug() to find module by slug
    - Implement isModuleRegistered() to check registration status
    - Store module metadata in memory (collection)
    - _Requirements: 7.2, 7.4, 7.5_
  
  - [ ]* 12.4 Write property tests for module registration
    - **Property 19: Module Resource Registration**
    - **Property 20: Module Loading Failure Isolation**
    - **Validates: Requirements 7.4, 7.5, 7.6**
  
  - [x] 12.5 Create ModuleServiceProvider
    - Implement boot() to call ModuleScanner and register modules
    - Implement register() to bind services to container
    - Register provider in config/app.php
    - _Requirements: 7.1, 7.2_

- [ ] 13. Implement hook system for module integration
  - [x] 13.1 Create HookManager service
    - Implement registerSidebarItem() to store sidebar items
    - Implement getSidebarItems() to return items filtered by user permissions
    - Implement registerDashboardWidget() to store widget view paths
    - Implement getDashboardWidgets() to return widgets filtered by user permissions
    - Implement registerPermission() to register custom module permissions
    - Bind service to container in AppServiceProvider
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6_
  
  - [ ]* 13.2 Write property test for hook system
    - **Property 21: Hook Registration and Permission-Based Display**
    - **Validates: Requirements 8.4, 8.5, 8.6**
  
  - [x] 13.3 Update DashboardController to use HookManager
    - Fetch dashboard widgets from HookManager
    - Pass widgets to dashboard view
    - Filter by user permissions
    - _Requirements: 8.5, 8.6, 12.3_
  
  - [x] 13.4 Create module widget component
    - Create components/module-widget.blade.php
    - Accept widget view path as parameter
    - Include module widget view
    - Apply Tailwind card styling
    - _Requirements: 12.2, 12.4_
  
  - [x] 13.5 Update dashboard view to display module widgets
    - Add module widgets grid below announcements
    - Use module-widget component for each widget
    - Display message when no modules are accessible
    - _Requirements: 12.2, 12.3, 12.5_

- [ ] 14. Create main application layout with sidebar
  - [x] 14.1 Create app layout blade template
    - Create layouts/app.blade.php
    - Add responsive sidebar navigation
    - Add header with user info and logout button
    - Include sidebar items from HookManager (filtered by permissions)
    - Add main content area
    - Include Tailwind CSS and Alpine.js
    - _Requirements: 11.1, 11.2, 11.3, 11.5, 11.6, 11.7_
  
  - [x] 14.2 Create guest layout blade template
    - Create layouts/guest.blade.php for login/register pages
    - Apply Tailwind styling
    - _Requirements: 11.1, 11.6_
  
  - [x] 14.3 Update all views to use layouts
    - Update dashboard view to extend app layout
    - Update user management views to extend app layout
    - Update announcement management views to extend app layout
    - Update auth views to extend guest layout
    - _Requirements: 11.2, 11.4_
  
  - [x] 14.4 Add core navigation items to sidebar
    - Add Dashboard link (all authenticated users)
    - Add Users link (admin and super-admin only)
    - Add Announcements link (admin and super-admin only)
    - Add Audit Logs link (super-admin only)
    - Use HookManager for dynamic module items
    - _Requirements: 11.2, 8.4_

- [ ] 15. Implement audit log viewing functionality
  - [x] 15.1 Create AuditLogController
    - Implement index() to list audit logs with filters
    - Implement show() to display detailed log entry
    - Apply CheckRole middleware (super-admin only)
    - Add pagination
    - _Requirements: 9.8_
  
  - [x] 15.2 Create audit log views
    - Create audit-logs/index.blade.php for log list
    - Create audit-logs/show.blade.php for detailed view
    - Display formatted payload JSON
    - Add filters for module, user, date range
    - Apply Tailwind styling
    - _Requirements: 9.8, 11.1, 11.6_
  
  - [x] 15.3 Register audit log routes
    - Add routes for AuditLogController
    - Apply auth and super-admin middleware
    - _Requirements: 9.8_

- [ ] 16. Create database seeders for initial data
  - [x] 16.1 Create UserSeeder
    - Create super-admin user with email admin@example.com
    - Create sample admin user
    - Create sample standard user
    - Hash all passwords
    - _Requirements: 2.1, 2.2_
  
  - [x] 16.2 Create AnnouncementSeeder
    - Create sample critical announcement
    - Create sample maintenance announcement
    - Create sample info announcement
    - _Requirements: 5.1_
  
  - [x] 16.3 Update DatabaseSeeder to call seeders
    - Call UserSeeder
    - Call AnnouncementSeeder
    - _Requirements: 2.1, 5.1_

- [ ] 17. Checkpoint - Ensure all core features work end-to-end
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 18. Create example module for testing module system
  - [x] 18.1 Create example module directory structure
    - Create /app/Modules/Example/ directory
    - Create Providers/, Http/Controllers/, Views/, Routes/ subdirectories
    - _Requirements: 7.1_
  
  - [x] 18.2 Create example module metadata
    - Create module.json with name, slug, version, description, author
    - _Requirements: 14.1, 14.2, 14.3, 14.4, 14.5, 14.6_
  
  - [x] 18.3 Create example module ServiceProvider
    - Create ExampleServiceProvider
    - Register sidebar item via HookManager
    - Register dashboard widget via HookManager
    - Register permissions via HookManager
    - _Requirements: 7.2, 8.4, 8.5_
  
  - [x] 18.4 Create example module routes and controller
    - Create Routes/web.php with example routes
    - Create ExampleController with index method
    - Apply CheckModulePermission middleware
    - _Requirements: 7.4_
  
  - [x] 18.5 Create example module views
    - Create Views/index.blade.php for module main page
    - Create Views/widget.blade.php for dashboard widget
    - Apply Tailwind styling
    - _Requirements: 7.5, 12.4_
  
  - [x] 18.6 Test example module integration
    - Verify module is discovered and registered
    - Verify sidebar item appears for users with permission
    - Verify dashboard widget appears for users with permission
    - Verify module routes are accessible
    - _Requirements: 7.1, 7.2, 7.4, 7.5, 8.4, 8.5, 8.6_

- [ ] 19. Write comprehensive unit tests for edge cases
  - [ ]* 19.1 Write unit tests for authentication edge cases
    - Test login with inactive account
    - Test password reset with expired token
    - Test session expiration handling
    - _Requirements: 1.3, 1.4, 1.6_
  
  - [ ]* 19.2 Write unit tests for authorization edge cases
    - Test access with missing permissions
    - Test super-admin bypass
    - Test role-based restrictions
    - _Requirements: 2.4, 2.5, 2.6_
  
  - [ ]* 19.3 Write unit tests for announcement edge cases
    - Test announcement with no end date
    - Test announcement exactly at 8-hour boundary
    - Test empty announcement list display
    - _Requirements: 5.6, 5.8, 6.5, 12.5_
  
  - [ ]* 19.4 Write unit tests for module system edge cases
    - Test module with missing module.json
    - Test module with invalid JSON
    - Test module with missing required fields
    - Test module loading failure isolation
    - _Requirements: 7.6, 14.7_
  
  - [ ]* 19.5 Write unit tests for validation errors
    - Test user creation with duplicate email
    - Test announcement with invalid date range
    - Test user creation with invalid role
    - _Requirements: 4.6, 6.4_

- [ ] 20. Write integration tests for end-to-end flows
  - [ ]* 20.1 Write integration test for user lifecycle
    - Create user → login → access dashboard → logout
    - Verify audit logs created at each step
    - _Requirements: 1.3, 1.5, 1.7, 9.1, 9.2_
  
  - [ ]* 20.2 Write integration test for announcement lifecycle
    - Create announcement → display on dashboard → mark as fixed → auto-removal after 8 hours
    - Verify audit logs created
    - _Requirements: 5.6, 5.7, 5.8, 5.10, 9.3_
  
  - [ ]* 20.3 Write integration test for module lifecycle
    - Install module → register → display widget → access module → verify permissions
    - Verify audit logs created
    - _Requirements: 7.1, 7.2, 7.4, 7.5, 8.4, 8.5, 8.6_
  
  - [ ]* 20.4 Write integration test for permission changes
    - Grant permission → verify access → revoke permission → verify denial
    - Verify audit logs created
    - _Requirements: 3.3, 3.4, 3.5, 3.6, 9.5_

- [ ] 21. Final checkpoint - Run all tests and verify coverage
  - Run full test suite (unit + property + integration)
  - Verify minimum 80% code coverage
  - Verify all 31 correctness properties pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 22. Create documentation and README
  - [x] 22.1 Create README.md
    - Document installation steps
    - Document configuration requirements
    - Document seeding initial data
    - Document module development guidelines
    - _Requirements: 15.1, 15.2, 15.3, 15.4, 15.5, 15.6_
  
  - [x] 22.2 Create API documentation for services
    - Document AuditLogger service API
    - Document ModuleScanner service API
    - Document ModuleRegistry service API
    - Document HookManager service API
    - _Requirements: 7.1, 7.2, 8.1, 8.2, 8.3, 9.6, 9.7_
  
  - [x] 22.3 Create module development guide
    - Document module directory structure
    - Document module.json format
    - Document ServiceProvider requirements
    - Document hook system usage
    - Document permission registration
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 8.1, 8.2, 8.3, 14.1, 14.2, 14.3, 14.4_

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at key milestones
- Property tests validate universal correctness properties (minimum 100 iterations each)
- Unit tests validate specific examples and edge cases
- Integration tests validate end-to-end workflows
- The example module (task 18) serves as both a test and a template for future modules
- All audit logging is integrated throughout to ensure complete traceability
- The module system is designed to be extensible without modifying core code
