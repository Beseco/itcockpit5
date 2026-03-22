# Requirements Document: IT Cockpit v5.0 Core System

## Introduction

The IT Cockpit v5.0 Core System is a Laravel-based administrative platform that provides centralized management of IT infrastructure through a modular architecture. The system features role-based access control, a traffic light announcement system for status communication, and a plugin-based module system that allows extensions without modifying core code.

## Glossary

- **System**: The IT Cockpit v5.0 Core Application
- **User**: Any authenticated person using the system
- **Super_Admin**: User with global system access and configuration rights
- **Admin**: User with administrative rights within their scope
- **Standard_User**: User with basic access to assigned modules
- **Module**: A self-contained functional extension in the `/app/Modules/` directory
- **Announcement**: A status message displayed on the dashboard (info, maintenance, or critical)
- **Dashboard**: The main landing page showing announcements and module widgets
- **Traffic_Light_System**: The color-coded announcement display (red=critical, yellow=maintenance, green=resolved, blue=info)
- **Audit_Log**: System activity record tracking user actions
- **Service_Provider**: Laravel class that registers a module with the system
- **Hook**: Integration point where modules can inject functionality
- **Widget**: Dashboard card displayed by a module

## Requirements

### Requirement 1: User Authentication

**User Story:** As a system administrator, I want secure user authentication, so that only authorized personnel can access the IT Cockpit.

#### Acceptance Criteria

1. THE System SHALL provide user registration with name, email, and password fields
2. THE System SHALL hash all passwords before storage using Laravel's bcrypt hashing
3. WHEN a user attempts to log in with valid credentials, THE System SHALL authenticate the user and create a session
4. WHEN a user attempts to log in with invalid credentials, THE System SHALL reject the login and display an error message
5. THE System SHALL record the timestamp of each successful login in the `last_login_at` field
6. THE System SHALL provide password reset functionality via email
7. THE System SHALL provide logout functionality that terminates the user session

### Requirement 2: Role-Based Access Control

**User Story:** As a Super Admin, I want to assign different roles to users, so that I can control what each user can access and modify.

#### Acceptance Criteria

1. THE System SHALL support three user roles: super-admin, admin, and user
2. WHEN a user is created, THE System SHALL assign exactly one role from the available roles
3. THE System SHALL store the user role in the `users.role` column as an enum type
4. THE System SHALL enforce that Super_Admin role has access to all system functions
5. THE System SHALL enforce that Admin role has access to user management and module administration
6. THE System SHALL enforce that Standard_User role has access only to assigned modules

### Requirement 3: Module-Based Permissions

**User Story:** As an administrator, I want to grant module-specific permissions to users, so that I can control access at a granular level.

#### Acceptance Criteria

1. THE System SHALL implement permission format `module.{slug}.view` for read access to modules
2. THE System SHALL implement permission format `module.{slug}.edit` for write access to modules
3. WHEN a user has `module.{slug}.view` permission, THE System SHALL display the module's dashboard widget
4. WHEN a user lacks `module.{slug}.view` permission, THE System SHALL hide the module from that user
5. WHEN a user has `module.{slug}.edit` permission, THE System SHALL allow configuration changes within that module
6. WHEN a user lacks `module.{slug}.edit` permission, THE System SHALL prevent modification attempts within that module

### Requirement 4: User Account Management

**User Story:** As an administrator, I want to manage user accounts, so that I can control who has access to the system.

#### Acceptance Criteria

1. THE System SHALL provide functionality to create new user accounts with name, email, role, and password
2. THE System SHALL provide functionality to update existing user information
3. THE System SHALL provide functionality to activate or deactivate user accounts via the `is_active` field
4. WHEN a user account is deactivated, THE System SHALL prevent that user from logging in
5. THE System SHALL provide functionality to delete user accounts
6. THE System SHALL validate that email addresses are unique across all users

### Requirement 5: Dashboard Traffic Light Announcement System

**User Story:** As a system administrator, I want to display status announcements on the dashboard, so that users are immediately informed about system status and planned maintenance.

#### Acceptance Criteria

1. THE System SHALL support four announcement types: info, maintenance, critical, and resolved
2. THE System SHALL display critical announcements with red styling at the top of the dashboard
3. THE System SHALL display maintenance announcements with yellow styling during their scheduled time window
4. THE System SHALL display resolved announcements with green styling
5. THE System SHALL display info announcements with blue styling
6. WHEN an announcement has `starts_at` and `ends_at` timestamps, THE System SHALL only display it within that time window
7. WHEN a critical announcement is marked as fixed, THE System SHALL change its display to green styling
8. WHEN an announcement has been marked as fixed for 8 hours, THE System SHALL remove it from the dashboard display
9. THE System SHALL provide a "Mark as Fixed" button for critical announcements
10. WHEN the "Mark as Fixed" button is clicked, THE System SHALL set `is_fixed` to true and record the timestamp in `fixed_at`

### Requirement 6: Announcement Management

**User Story:** As an administrator, I want to create and manage announcements, so that I can communicate system status to users.

#### Acceptance Criteria

1. THE System SHALL provide functionality to create announcements with type, message, starts_at, and ends_at fields
2. THE System SHALL provide functionality to update existing announcements
3. THE System SHALL provide functionality to delete announcements
4. THE System SHALL validate that `starts_at` is before `ends_at` when both are provided
5. THE System SHALL allow announcements without end dates for indefinite display
6. THE System SHALL store announcement messages as text to support detailed content

### Requirement 7: Module Discovery and Registration

**User Story:** As a developer, I want the system to automatically discover modules, so that I can add functionality without modifying core code.

#### Acceptance Criteria

1. THE System SHALL scan the `/app/Modules/` directory for module folders
2. WHEN a module folder contains a valid Service_Provider, THE System SHALL register that module
3. THE System SHALL load module metadata from `module.json` files
4. THE System SHALL register module routes from the module's `Routes/web.php` file
5. THE System SHALL register module views from the module's `Views/` directory
6. WHEN a module fails to load, THE System SHALL log the error and continue loading other modules

### Requirement 8: Module Hook System

**User Story:** As a module developer, I want to inject functionality into the core system, so that my module integrates seamlessly with the dashboard and navigation.

#### Acceptance Criteria

1. THE System SHALL provide a sidebar navigation hook for modules to register menu items
2. THE System SHALL provide a dashboard grid hook for modules to register widgets
3. THE System SHALL provide a permissions hook for modules to register custom permissions
4. WHEN a module registers a sidebar item, THE System SHALL display it in the main navigation
5. WHEN a module registers a dashboard widget, THE System SHALL display it on the dashboard grid
6. THE System SHALL only display module navigation items and widgets if the user has `module.{slug}.view` permission

### Requirement 9: Audit Logging

**User Story:** As a Super Admin, I want to track all significant system actions, so that I can audit changes and troubleshoot issues.

#### Acceptance Criteria

1. THE System SHALL log user creation, update, and deletion actions
2. THE System SHALL log user login events
3. THE System SHALL log announcement creation, update, and deletion actions
4. THE System SHALL log module enable and disable actions
5. THE System SHALL log permission changes
6. WHEN an action is logged, THE System SHALL record the user_id, module context, action description, and timestamp
7. THE System SHALL store detailed change information in the `payload` JSON field
8. THE System SHALL allow Super_Admin to view audit logs
9. THE System SHALL prevent modification or deletion of audit log entries

### Requirement 10: Database Schema Implementation

**User Story:** As a developer, I want properly structured database tables, so that the system can store and retrieve data efficiently.

#### Acceptance Criteria

1. THE System SHALL create a `users` table with columns: id, role, name, email, password, is_active, last_login_at, created_at, updated_at
2. THE System SHALL create an `announcements` table with columns: id, type, message, starts_at, ends_at, is_fixed, fixed_at, created_at, updated_at
3. THE System SHALL create an `audit_logs` table with columns: id, user_id, module, action, payload, created_at
4. THE System SHALL enforce unique constraint on `users.email`
5. THE System SHALL enforce foreign key constraint from `audit_logs.user_id` to `users.id`
6. THE System SHALL use enum type for `users.role` with values: super-admin, admin, user
7. THE System SHALL use enum type for `announcements.type` with values: info, maintenance, critical
8. THE System SHALL set default value of false for `users.is_active`
9. THE System SHALL set default value of false for `announcements.is_fixed`

### Requirement 11: User Interface Layout

**User Story:** As a user, I want a clean and intuitive interface, so that I can navigate the system efficiently.

#### Acceptance Criteria

1. THE System SHALL provide a responsive layout using Tailwind CSS
2. THE System SHALL display a sidebar navigation menu on all authenticated pages
3. THE System SHALL display a header with user information and logout button
4. THE System SHALL display the dashboard as the default landing page after login
5. THE System SHALL use Alpine.js for interactive UI components
6. THE System SHALL use Blade templating for all views
7. THE System SHALL provide consistent styling across all core pages

### Requirement 12: Dashboard Widget Grid

**User Story:** As a user, I want to see relevant information from all my accessible modules on one dashboard, so that I can quickly assess system status.

#### Acceptance Criteria

1. THE System SHALL display announcements at the top of the dashboard
2. THE System SHALL display module widgets in a responsive grid layout below announcements
3. THE System SHALL only display widgets for modules where the user has view permission
4. THE System SHALL allow modules to define custom widget content via Blade templates
5. WHEN no modules are accessible, THE System SHALL display a message indicating no modules are available
6. THE System SHALL order critical announcements before maintenance announcements before info announcements

### Requirement 13: Email Notifications

**User Story:** As a user, I want to receive email notifications for password resets, so that I can regain access to my account.

#### Acceptance Criteria

1. THE System SHALL send password reset emails using Laravel's mail system
2. THE System SHALL use SMTP configuration for email delivery
3. WHEN a password reset is requested, THE System SHALL generate a secure token
4. THE System SHALL include the reset token in the password reset email
5. THE System SHALL expire password reset tokens after 60 minutes
6. THE System SHALL validate the reset token before allowing password changes

### Requirement 14: Module Metadata

**User Story:** As a module developer, I want to define module metadata, so that the system can display module information correctly.

#### Acceptance Criteria

1. THE System SHALL read module metadata from `module.json` files
2. THE System SHALL require `name` field in module metadata
3. THE System SHALL require `slug` field in module metadata for permission namespacing
4. THE System SHALL require `version` field in module metadata
5. THE System SHALL optionally read `description` field from module metadata
6. THE System SHALL optionally read `author` field from module metadata
7. WHEN module metadata is invalid or missing, THE System SHALL log an error and skip that module

### Requirement 15: System Configuration

**User Story:** As a Super Admin, I want to configure system-wide settings, so that I can customize the IT Cockpit for my organization.

#### Acceptance Criteria

1. THE System SHALL provide configuration for application name
2. THE System SHALL provide configuration for SMTP email settings
3. THE System SHALL provide configuration for database connection parameters
4. THE System SHALL store configuration in Laravel's `.env` file
5. THE System SHALL validate configuration values before saving
6. THE System SHALL provide sensible default values for all configuration options
