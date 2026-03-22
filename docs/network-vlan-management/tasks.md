# Implementation Plan: Network/VLAN Management Module

## Overview

This implementation plan breaks down the Network/VLAN Management Module into discrete coding tasks that build incrementally. The module will be implemented as a Laravel module following the IT Cockpit Core System's module infrastructure. Each task builds on previous work, with testing integrated throughout to catch errors early.

## Tasks

- [x] 1. Set up module structure and database migrations
  - Create module directory structure at app/Modules/Network/
  - Create module.json with metadata (name: "Network Management", slug: "network", version: "1.0.0")
  - Create NetworkServiceProvider.php with basic registration
  - Create migration for vlans table with all columns and indexes
  - Create migration for ip_addresses table with foreign key to vlans
  - Create migration for vlan_comments table with foreign keys to vlans and users
  - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5, 16.6, 16.9_

- [ ] 2. Create Eloquent models with relationships
  - [x] 2.1 Create Vlan model with fillable fields, casts, and default attributes
    - Define relationships: hasMany ipAddresses, hasMany comments
    - Implement scopes: scanEnabled, needsScan
    - Implement accessors: subnet, onlineCount, totalIpCount
    - Implement shouldScan() method
    - _Requirements: 1.1, 1.7, 6.4_
  
  - [x] 2.2 Create IpAddress model with fillable fields and casts
    - Define relationship: belongsTo vlan
    - Implement scopes: online, offline, neverScanned
    - Implement accessors: statusBadgeClass, statusText
    - Implement updateFromScan() method
    - _Requirements: 2.1, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8_
  
  - [x] 2.3 Create VlanComment model with fillable fields and casts
    - Define relationships: belongsTo vlan, belongsTo user
    - Implement canDelete() authorization method
    - Override boot() to auto-set created_at timestamp
    - _Requirements: 3.1, 3.2, 3.3, 3.6, 3.7_

- [ ] 3. Implement IP address generation service
  - [x] 3.1 Create IpGeneratorService class
    - Implement generateIpAddresses(Vlan $vlan): int method
    - Calculate netmask from CIDR suffix
    - Calculate network, broadcast, first_host, last_host addresses
    - Use ip2long() and long2ip() for address arithmetic
    - Create IpAddress records for all host addresses
    - Return count of generated addresses
    - _Requirements: 2.2, 2.4, 2.5, 13.1, 13.2, 13.10_
  
  - [x] 3.2 Implement calculateSubnetInfo() helper method
    - Return array with network, broadcast, first_host, last_host, host_count
    - _Requirements: 13.1, 13.2_
  
  - [ ]* 3.3 Write property test for IP address generation
    - **Property 8: Complete IP Address Generation**
    - **Validates: Requirements 2.2, 2.4, 2.5**
  
  - [ ]* 3.4 Write unit tests for specific subnet sizes
    - Test /24 subnet generates 254 addresses
    - Test /31 subnet generates 2 addresses
    - Test /32 subnet generates 1 address
    - _Requirements: 13.5, 13.6, 13.7_

- [ ] 4. Implement network scanning service
  - [x] 4.1 Create ScannerService class with ping functionality
    - Implement pingIpAddress(string $ip): array method
    - Detect OS (Windows vs Linux) for ping command
    - Execute ping with 1 second timeout, 1 packet
    - Parse ping output for success/failure and response time
    - Return ['is_online' => bool, 'ping_ms' => float|null]
    - _Requirements: 4.2, 4.3, 4.5, 4.6, 14.2, 14.3_
  
  - [x] 4.2 Implement MAC address resolution
    - Implement resolveMacAddress(string $ip): ?string method
    - Detect OS for ARP command (arp -a on Windows, arp -n on Linux)
    - Execute ARP command via shell_exec
    - Parse output with regex to extract MAC address
    - Normalize MAC address format (uppercase with colons)
    - Return MAC address or null if not found
    - _Requirements: 5.2, 5.3, 5.4, 5.5, 5.6_
  
  - [x] 4.3 Implement VLAN scanning logic
    - Implement scanVlan(Vlan $vlan): array method
    - Retrieve all IP addresses for the VLAN
    - For each IP: call pingIpAddress(), if online call resolveMacAddress()
    - Call updateFromScan() on IpAddress model with results
    - Handle errors gracefully, continue on failure
    - Return summary: ['scanned' => count, 'online' => count, 'offline' => count]
    - _Requirements: 4.1, 4.8, 4.10, 5.1, 5.7_
  
  - [x] 4.4 Implement scan interval checking
    - Implement shouldScanVlan(Vlan $vlan): bool method
    - Check if ipscan is enabled
    - Check if scan_interval_minutes have elapsed since last_scanned_at
    - _Requirements: 6.2, 6.4_
  
  - [ ]* 4.5 Write property tests for scanning service
    - **Property 14: Successful Ping Updates Online Status**
    - **Property 17: Failed Ping Updates Online Status**
    - **Property 19: All Scans Update Scan Timestamp**
    - **Validates: Requirements 4.3, 4.6, 4.8**
  
  - [ ]* 4.6 Write unit tests for ping and ARP parsing
    - Test ping output parsing for Windows and Linux
    - Test ARP output parsing for Windows and Linux
    - Test error handling for command failures
    - _Requirements: 4.10, 5.7_

- [ ] 5. Create Artisan command for network scanning
  - [x] 5.1 Create NetworkScanCommand class
    - Set signature: network:scan
    - Add options: --vlan={id}, --force
    - Implement handle() method
    - If --vlan provided, scan specific VLAN only
    - Otherwise, load all VLANs where ipscan=true
    - For each VLAN, check shouldScanVlan() unless --force
    - Call ScannerService->scanVlan() for each VLAN
    - Output progress to console
    - Log scan results to Laravel log
    - Update VLAN's last_scanned_at timestamp
    - Return exit code 0 on success
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.8, 14.7, 14.8_
  
  - [ ]* 5.2 Write unit tests for scan command
    - Test command execution with no VLANs
    - Test command with --vlan option
    - Test command with --force option
    - Test scan interval enforcement
    - _Requirements: 6.4, 6.5_

- [x] 6. Checkpoint - Ensure core services work
  - Run migrations to create database tables
  - Test IP generation service with sample VLAN
  - Test scanning service with mock ping/ARP commands
  - Ensure all tests pass, ask the user if questions arise

- [ ] 7. Implement form request validation classes
  - [x] 7.1 Create StoreVlanRequest class
    - Implement authorize() to check module.network.edit permission
    - Define validation rules for all VLAN fields
    - Add custom validation: gateway must be in subnet
    - Add custom validation: DHCP range must be in subnet and dhcp_from <= dhcp_to
    - _Requirements: 1.2, 1.3, 1.4, 1.5, 1.6, 12.1, 12.2, 12.3, 12.4_
  
  - [x] 7.2 Create UpdateVlanRequest class
    - Same as StoreVlanRequest but ignore current VLAN in unique validation
    - _Requirements: 1.2, 1.3, 1.4, 1.5, 1.6, 12.1, 12.2, 12.3, 12.4_
  
  - [ ]* 7.3 Write property tests for validation
    - **Property 2: IPv4 Address Validation**
    - **Property 3: CIDR Suffix Range Validation**
    - **Property 4: DHCP Range Subnet Validation**
    - **Property 5: DHCP Range Order Validation**
    - **Validates: Requirements 1.3, 1.4, 1.5, 1.6**

- [ ] 8. Implement VLAN controller
  - [x] 8.1 Create VlanController with index and show methods
    - Implement index(): retrieve all VLANs ordered by vlan_id, return view
    - Implement show(Vlan $vlan): load relationships, return view
    - Protect routes with module.network.view permission
    - _Requirements: 7.1, 7.2, 7.10, 8.1, 11.2_
  
  - [x] 8.2 Implement create and store methods
    - Implement create(): return form view
    - Implement store(StoreVlanRequest): create VLAN, call IpGeneratorService, log to audit, redirect
    - Protect routes with module.network.edit permission
    - _Requirements: 1.8, 10.9, 11.3, 17.1, 17.11, 17.12_
  
  - [x] 8.3 Implement edit and update methods
    - Implement edit(Vlan $vlan): return form view with VLAN data
    - Implement update(UpdateVlanRequest, Vlan $vlan): update VLAN, regenerate IPs if subnet changed, log to audit, redirect
    - Protect routes with module.network.edit permission
    - _Requirements: 1.8, 10.9, 11.3, 17.2, 17.11, 17.12_
  
  - [x] 8.4 Implement destroy method
    - Implement destroy(Vlan $vlan): delete VLAN (cascade deletes IPs and comments), log to audit, redirect
    - Protect routes with module.network.edit permission
    - _Requirements: 1.9, 1.10, 10.9, 11.3_
  
  - [ ]* 8.5 Write property tests for controller
    - **Property 47: View Permission Route Protection**
    - **Property 48: Edit Permission Route Protection**
    - **Property 7: Cascade Delete Integrity**
    - **Validates: Requirements 11.2, 11.3, 1.10**

- [ ] 9. Implement IP address and comment controllers
  - [x] 9.1 Create IpAddressController with update method
    - Implement update(Request, IpAddress): validate dns_name and comment, update record, preserve scan data, return JSON
    - Protect route with module.network.edit permission
    - _Requirements: 2.8, 2.9, 2.10, 11.4, 12.5_
  
  - [x] 9.2 Create VlanCommentController with store and destroy methods
    - Implement store(Request, Vlan): validate comment, create with authenticated user, log to audit, redirect
    - Implement destroy(VlanComment): check authorization (author or super-admin), delete, log to audit, redirect
    - Protect routes with module.network.view permission (ownership checked separately for delete)
    - _Requirements: 3.2, 3.3, 3.6, 3.7, 11.5, 12.6, 12.7_
  
  - [ ]* 9.3 Write property tests for IP and comment operations
    - **Property 9: Manual Update Preservation**
    - **Property 10: Comment User Association**
    - **Property 13: Comment Author Deletion Authorization**
    - **Validates: Requirements 2.10, 3.2, 3.6, 3.7**

- [ ] 10. Create Blade views for VLAN list and forms
  - [x] 10.1 Create vlans/index.blade.php
    - Display table of VLANs with vlan_id, vlan_name, network_address, cidr_suffix, gateway
    - Show ipscan status indicator for each VLAN
    - Provide link to detail page for each VLAN
    - Show edit/delete buttons if user has module.network.edit permission
    - Show "Create VLAN" button if user has module.network.edit permission
    - Display message when no VLANs exist
    - Use Tailwind CSS for styling
    - _Requirements: 7.1, 7.2, 7.4, 7.5, 7.6, 7.7, 7.9, 7.10_
  
  - [x] 10.2 Create vlans/create.blade.php and vlans/edit.blade.php
    - Create form with fields for all VLAN attributes
    - Use Tailwind CSS form styling
    - Add placeholder text and labels for each field
    - Mark required fields with visual indicators
    - Display validation errors next to fields
    - Preserve user input on validation failure
    - Add checkbox for ipscan field
    - Add numeric input for scan_interval_minutes with default 60
    - _Requirements: 17.1, 17.2, 17.3, 17.4, 17.5, 17.6, 17.7, 17.8, 17.9, 17.10_
  
  - [ ]* 10.3 Write property tests for view rendering
    - **Property 28: VLAN List Display Completeness**
    - **Property 31: Permission-Based Edit Controls**
    - **Property 76: Edit Form Data Pre-population**
    - **Validates: Requirements 7.2, 7.7, 17.2**

- [ ] 11. Create Blade view for VLAN detail page
  - [x] 11.1 Create vlans/show.blade.php
    - Display all VLAN attributes including description and DHCP range
    - Display table of IP addresses with ip_address, dns_name, mac_address, is_online, comment
    - Show green badge for online IPs, gray badge for offline IPs, neutral for never scanned
    - Display last_online_at timestamp for IPs that have been online
    - Display ping_ms for online IPs
    - Enable inline editing for dns_name and comment if user has module.network.edit permission
    - Display VLAN comments section below IP table
    - Show comment author name and timestamp for each comment
    - Provide form to add new comments
    - Show delete button for comments (author or super-admin only)
    - Use Tailwind CSS for styling
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8, 8.9, 8.10, 8.11, 8.12, 8.13, 3.4, 3.5_
  
  - [ ]* 11.2 Write property tests for detail page rendering
    - **Property 33: VLAN Detail Attribute Completeness**
    - **Property 34: IP Address Display Completeness**
    - **Property 35: Online Status Badge Styling**
    - **Property 36: Offline Status Badge Styling**
    - **Validates: Requirements 8.2, 8.4, 8.5, 8.6**

- [ ] 12. Create dashboard widget component
  - [x] 12.1 Create components/dashboard-widget.blade.php
    - Calculate total online device count (sum of is_online=true across all VLANs)
    - Calculate total monitored IP count (sum of all IP addresses)
    - Display counts in Tailwind CSS card
    - Display signal icon
    - Provide link to network.index route
    - Display "No network data" when no VLANs configured
    - _Requirements: 9.2, 9.3, 9.5, 9.6, 9.7, 9.10_
  
  - [ ]* 12.2 Write property tests for dashboard widget
    - **Property 41: Dashboard Widget Online Count Accuracy**
    - **Property 42: Dashboard Widget Total Count Accuracy**
    - **Validates: Requirements 9.2, 9.3**

- [ ] 13. Register module with Core System
  - [x] 13.1 Complete NetworkServiceProvider implementation
    - Implement register() to bind services to container
    - Implement boot() to load migrations, routes, views
    - Register sidebar hook with label "Network", route "network.index", icon "heroicon-o-signal"
    - Register dashboard widget hook with view "network::components.dashboard-widget"
    - Register permissions: module.network.view, module.network.edit
    - Schedule network:scan command to run every minute
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 6.6, 6.7_
  
  - [x] 13.2 Create Routes/web.php with all module routes
    - Define routes for VlanController (index, show, create, store, edit, update, destroy)
    - Define route for IpAddressController (update)
    - Define routes for VlanCommentController (store, destroy)
    - Apply permission middleware to all routes
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5_
  
  - [ ]* 13.3 Write property tests for module integration
    - **Property 44: Sidebar Permission Visibility**
    - **Property 43: Dashboard Widget Permission Visibility**
    - **Property 51: Super Admin Universal Access**
    - **Validates: Requirements 10.6, 9.8, 11.6**

- [ ] 14. Implement audit logging integration
  - [x] 14.1 Add audit logging to all VLAN operations
    - Log VLAN creation with VLAN details
    - Log VLAN updates with changed fields
    - Log VLAN deletion with VLAN details
    - Use Core System's AuditLogger service
    - _Requirements: 10.8, 10.9_
  
  - [x] 14.2 Add audit logging to scan operations
    - Log scan execution with VLAN ID and summary statistics
    - Log unauthorized access attempts
    - _Requirements: 10.10, 11.10_
  
  - [ ]* 14.3 Write property tests for audit logging
    - **Property 45: VLAN Operation Audit Logging**
    - **Property 46: Scan Execution Audit Logging**
    - **Property 52: Unauthorized Access Audit Logging**
    - **Validates: Requirements 10.9, 10.10, 11.10**

- [ ] 15. Implement comprehensive error logging
  - [x] 15.1 Add error logging throughout the module
    - Log database operation failures with context
    - Log ping command failures with IP and error
    - Log ARP command failures with IP and error
    - Log IP generation failures with VLAN details
    - Log validation failures with input data
    - Use appropriate log levels (ERROR, WARNING, INFO)
    - Include contextual information (user_id, vlan_id, ip_address)
    - Ensure no sensitive information in logs
    - _Requirements: 15.1, 15.2, 15.3, 15.4, 15.5, 15.6, 15.7, 15.8, 15.9, 15.10_
  
  - [ ]* 15.2 Write property tests for error logging
    - **Property 67: Database Error Logging**
    - **Property 68: Ping Error Logging**
    - **Property 72: Log Entry Contextual Information**
    - **Property 75: Sensitive Information Exclusion**
    - **Validates: Requirements 15.1, 15.2, 15.7, 15.10**

- [x] 16. Final checkpoint - Integration testing
  - Run all migrations
  - Create test VLAN via UI
  - Verify IP addresses generated correctly
  - Run network:scan command manually
  - Verify scan results displayed correctly
  - Test all permission scenarios (view-only, edit, super-admin)
  - Verify dashboard widget displays correct counts
  - Verify audit logs created for all operations
  - Ensure all tests pass, ask the user if questions arise

- [ ] 17. Performance optimization and final polish
  - [x] 17.1 Add database query optimization
    - Add eager loading for relationships in controllers
    - Add indexes for frequently queried columns (already in migrations)
    - Optimize scan queries to use batch updates
    - _Requirements: 14.4, 14.5_
  
  - [x] 17.2 Add scan concurrency control
    - Implement lock mechanism to prevent concurrent scans of same VLAN
    - Add scan duration logging and warnings for long scans
    - _Requirements: 14.6, 14.7, 14.8, 14.9_
  
  - [ ]* 17.3 Write property tests for performance features
    - **Property 63: Concurrent Scan Prevention**
    - **Property 66: Long Scan Warning**
    - **Validates: Requirements 14.6, 14.9**

## Notes

- Tasks marked with `*` are optional property-based tests and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at key milestones
- Property tests validate universal correctness properties across all inputs
- Unit tests validate specific examples, edge cases, and error conditions
- The module integrates with existing IT Cockpit Core System infrastructure
- All UI uses Tailwind CSS for consistent styling
- All operations are protected by permission middleware
- Comprehensive audit logging and error logging throughout
