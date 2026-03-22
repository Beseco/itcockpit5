# Requirements Document: Network/VLAN Management Module

## Introduction

The Network/VLAN Management Module is a Laravel-based system for managing virtual local area networks (VLANs) and monitoring IP address allocations within an IT infrastructure. The module integrates with the existing IT Cockpit Core System to provide network administrators with tools to define VLANs, track IP address usage, perform automated network scans, and monitor device availability. The system automatically generates IP address inventories for each VLAN and provides real-time status information through periodic ping scans and ARP table lookups.

## Glossary

- **VLAN**: Virtual Local Area Network - a logical network segment defined by VLAN ID and IP address range
- **Network_Module**: The Network/VLAN Management Module being specified in this document
- **IP_Generator**: Service component that calculates and creates all IP addresses within a VLAN's subnet
- **Scanner_Service**: Service component that performs ping scans and MAC address resolution
- **CIDR**: Classless Inter-Domain Routing notation for specifying IP address ranges (e.g., /24)
- **ARP_Cache**: Address Resolution Protocol cache containing IP-to-MAC address mappings
- **Scan_Interval**: Time period in minutes between automated network scans for a VLAN
- **Core_System**: The existing IT Cockpit v5.0 Core System with authentication, RBAC, and module infrastructure
- **Dashboard_Widget**: A visual component displayed on the main dashboard showing summary information

## Requirements

### Requirement 1: VLAN Data Management

**User Story:** As a network administrator, I want to create and manage VLAN definitions with network parameters, so that I can organize and document my network infrastructure.

#### Acceptance Criteria

1. THE Network_Module SHALL store VLAN records with the following attributes: id, vlan_id, vlan_name, network_address, cidr_suffix, gateway, dhcp_from, dhcp_to, description, internes_netz, ipscan, scan_interval_minutes
2. WHEN a VLAN is created, THE Network_Module SHALL validate that vlan_id is a positive integer between 1 and 4094
3. WHEN a VLAN is created, THE Network_Module SHALL validate that network_address is a valid IPv4 address
4. WHEN a VLAN is created, THE Network_Module SHALL validate that cidr_suffix is an integer between 0 and 32
5. WHEN a VLAN is created with dhcp_from and dhcp_to values, THE Network_Module SHALL validate that both addresses fall within the VLAN's subnet
6. WHEN a VLAN is created with dhcp_from and dhcp_to values, THE Network_Module SHALL validate that dhcp_from is less than or equal to dhcp_to
7. THE Network_Module SHALL set scan_interval_minutes to 60 by default when not explicitly provided
8. THE Network_Module SHALL allow administrators to update VLAN attributes after creation
9. THE Network_Module SHALL allow administrators to delete VLAN records
10. WHEN a VLAN is deleted, THE Network_Module SHALL cascade delete all associated IP addresses and comments

### Requirement 2: IP Address Inventory Management

**User Story:** As a network administrator, I want the system to automatically generate and track all IP addresses within each VLAN, so that I have a complete inventory of my address space.

#### Acceptance Criteria

1. THE Network_Module SHALL store IP address records with the following attributes: id, vlan_id, ip_address, dns_name, mac_address, is_online, last_online_at, last_scanned_at, ping_ms, comment
2. WHEN a new VLAN is saved, THE IP_Generator SHALL calculate all valid host IP addresses within the subnet defined by network_address and cidr_suffix
3. WHEN calculating IP addresses, THE IP_Generator SHALL use ip2long and long2ip functions for address arithmetic
4. WHEN calculating IP addresses, THE IP_Generator SHALL exclude the network address (first address in subnet)
5. WHEN calculating IP addresses, THE IP_Generator SHALL exclude the broadcast address (last address in subnet)
6. WHEN calculating IP addresses, THE IP_Generator SHALL create an IP address record for each valid host address
7. THE Network_Module SHALL allow administrators to add comments to individual IP address records
8. THE Network_Module SHALL allow administrators to manually update dns_name for IP address records
9. THE Network_Module SHALL allow administrators to manually update comment for IP address records
10. WHEN an IP address record is updated manually, THE Network_Module SHALL preserve automatically collected data (is_online, last_online_at, last_scanned_at, ping_ms, mac_address)

### Requirement 3: VLAN Comment System

**User Story:** As a network administrator, I want to add timestamped comments to VLANs, so that I can document changes, issues, and maintenance activities.

#### Acceptance Criteria

1. THE Network_Module SHALL store VLAN comment records with the following attributes: id, vlan_id, user_id, comment, created_at
2. WHEN a comment is created, THE Network_Module SHALL associate it with the authenticated user
3. WHEN a comment is created, THE Network_Module SHALL automatically set created_at to the current timestamp
4. THE Network_Module SHALL display comments in chronological order with newest first
5. THE Network_Module SHALL display the author's name alongside each comment
6. THE Network_Module SHALL allow comment authors to delete their own comments
7. WHERE the user has super-admin role, THE Network_Module SHALL allow deletion of any comment

### Requirement 4: Network Scanning Service

**User Story:** As a network administrator, I want the system to automatically scan VLANs to detect online devices, so that I can monitor network availability and device presence.

#### Acceptance Criteria

1. THE Scanner_Service SHALL perform ping scans on all IP addresses within VLANs where ipscan is true
2. WHEN scanning an IP address, THE Scanner_Service SHALL execute a ping command to test reachability
3. WHEN a ping succeeds, THE Scanner_Service SHALL update the IP address record with is_online set to true
4. WHEN a ping succeeds, THE Scanner_Service SHALL update last_online_at to the current timestamp
5. WHEN a ping succeeds, THE Scanner_Service SHALL record the ping response time in ping_ms
6. WHEN a ping fails, THE Scanner_Service SHALL update the IP address record with is_online set to false
7. WHEN a ping fails, THE Scanner_Service SHALL preserve the previous last_online_at value
8. WHEN scanning any IP address, THE Scanner_Service SHALL update last_scanned_at to the current timestamp
9. THE Scanner_Service SHALL process all IP addresses in a VLAN sequentially during a scan
10. IF a scan operation fails for any reason, THEN THE Scanner_Service SHALL log the error and continue with remaining IP addresses

### Requirement 5: MAC Address Resolution

**User Story:** As a network administrator, I want the system to automatically resolve MAC addresses for online devices, so that I can identify devices by their hardware address.

#### Acceptance Criteria

1. WHEN an IP address is detected as online, THE Scanner_Service SHALL attempt to resolve its MAC address
2. WHERE the system is running on Windows, THE Scanner_Service SHALL execute shell_exec('arp -a ' . $ip) to query the ARP cache
3. WHERE the system is running on Linux, THE Scanner_Service SHALL execute shell_exec('arp -n ' . $ip) to query the ARP cache
4. WHEN the ARP cache contains an entry for the IP address, THE Scanner_Service SHALL parse the MAC address from the output
5. WHEN a MAC address is successfully resolved, THE Scanner_Service SHALL update the IP address record with the mac_address value
6. WHEN the ARP cache does not contain an entry, THE Scanner_Service SHALL leave the mac_address field unchanged
7. IF the ARP command execution fails, THEN THE Scanner_Service SHALL log the error and continue processing

### Requirement 6: Scheduled Network Scanning

**User Story:** As a network administrator, I want scans to run automatically at configurable intervals, so that device status information stays current without manual intervention.

#### Acceptance Criteria

1. THE Network_Module SHALL provide an Artisan command 'network:scan' that triggers network scanning
2. WHEN the network:scan command executes, THE Scanner_Service SHALL identify all VLANs where ipscan is true
3. WHEN the network:scan command executes, THE Scanner_Service SHALL check each VLAN's last scan timestamp
4. WHEN scan_interval_minutes have elapsed since a VLAN's last scan, THE Scanner_Service SHALL perform a scan of that VLAN
5. WHEN scan_interval_minutes have not elapsed since a VLAN's last scan, THE Scanner_Service SHALL skip that VLAN
6. THE Network_Module SHALL register the network:scan command with Laravel's task scheduler
7. THE Network_Module SHALL configure the scheduler to execute network:scan every minute
8. WHEN a VLAN scan completes, THE Scanner_Service SHALL update a last_scanned_at timestamp for the VLAN
9. THE Scanner_Service SHALL process VLANs sequentially to avoid resource contention
10. IF the scheduler execution fails, THEN THE Network_Module SHALL log the error without affecting other scheduled tasks

### Requirement 7: VLAN List Interface

**User Story:** As a network administrator, I want to view a list of all VLANs with key information, so that I can quickly navigate to specific networks.

#### Acceptance Criteria

1. THE Network_Module SHALL display a VLAN list page showing all configured VLANs
2. WHEN displaying the VLAN list, THE Network_Module SHALL show vlan_id, vlan_name, network_address, cidr_suffix, and gateway for each VLAN
3. THE Network_Module SHALL style the VLAN list using Tailwind CSS classes for consistent appearance
4. THE Network_Module SHALL provide a link from each VLAN list entry to the detailed VLAN view
5. THE Network_Module SHALL display an indicator showing whether ipscan is enabled for each VLAN
6. THE Network_Module SHALL provide a button to create new VLANs
7. WHERE the user has module.network.edit permission, THE Network_Module SHALL display edit and delete buttons for each VLAN
8. WHERE the user has only module.network.view permission, THE Network_Module SHALL hide edit and delete buttons
9. THE Network_Module SHALL display a message when no VLANs are configured
10. THE Network_Module SHALL order VLANs by vlan_id in ascending order

### Requirement 8: VLAN Detail Interface

**User Story:** As a network administrator, I want to view detailed information about a VLAN including all IP addresses and their status, so that I can monitor device availability and identify issues.

#### Acceptance Criteria

1. THE Network_Module SHALL display a VLAN detail page showing complete VLAN information
2. WHEN displaying VLAN details, THE Network_Module SHALL show all VLAN attributes including description and DHCP range
3. THE Network_Module SHALL display a table of all IP addresses within the VLAN
4. WHEN displaying IP addresses, THE Network_Module SHALL show ip_address, dns_name, mac_address, is_online status, and comment for each entry
5. WHEN an IP address is online, THE Network_Module SHALL display a green status badge
6. WHEN an IP address is offline, THE Network_Module SHALL display a gray status badge
7. WHEN an IP address has never been scanned, THE Network_Module SHALL display a neutral status indicator
8. THE Network_Module SHALL display last_online_at timestamp for IP addresses that have been online
9. THE Network_Module SHALL display ping_ms response time for online IP addresses
10. THE Network_Module SHALL provide inline editing capability for dns_name and comment fields
11. WHERE the user has module.network.edit permission, THE Network_Module SHALL enable inline editing
12. THE Network_Module SHALL display VLAN comments in a separate section below the IP address table
13. THE Network_Module SHALL provide a form to add new VLAN comments

### Requirement 9: Dashboard Widget Integration

**User Story:** As a system user, I want to see a summary of network status on the main dashboard, so that I can quickly assess overall network health.

#### Acceptance Criteria

1. THE Network_Module SHALL register a dashboard widget with the Core_System hook system
2. THE Dashboard_Widget SHALL display the total count of online devices across all VLANs
3. THE Dashboard_Widget SHALL display the total count of monitored IP addresses
4. THE Dashboard_Widget SHALL calculate online device count by summing IP addresses where is_online is true
5. THE Dashboard_Widget SHALL use Tailwind CSS card styling consistent with other dashboard widgets
6. THE Dashboard_Widget SHALL display an appropriate icon (signal icon)
7. THE Dashboard_Widget SHALL provide a link to the VLAN list page
8. WHERE the user lacks module.network.view permission, THE Dashboard_Widget SHALL not be displayed
9. THE Dashboard_Widget SHALL update counts in real-time when the dashboard page is loaded
10. THE Dashboard_Widget SHALL display "No network data" when no VLANs are configured

### Requirement 10: Module Registration and Navigation

**User Story:** As a system administrator, I want the Network module to integrate seamlessly with the IT Cockpit interface, so that users can access it through standard navigation.

#### Acceptance Criteria

1. THE Network_Module SHALL register itself with the Core_System module infrastructure
2. THE Network_Module SHALL provide a module.json file with name, slug, and version metadata
3. THE Network_Module SHALL register a sidebar navigation item with label "Network", route "network.index", and signal icon
4. THE Network_Module SHALL register module.network.view permission for read-only access
5. THE Network_Module SHALL register module.network.edit permission for full access
6. WHERE the user has module.network.view permission, THE sidebar navigation item SHALL be visible
7. WHERE the user lacks module.network.view permission, THE sidebar navigation item SHALL be hidden
8. THE Network_Module SHALL integrate with the Core_System audit logging service
9. WHEN VLANs are created, updated, or deleted, THE Network_Module SHALL create audit log entries
10. WHEN network scans are executed, THE Network_Module SHALL create audit log entries with scan results summary

### Requirement 11: Permission-Based Access Control

**User Story:** As a system administrator, I want to control who can view and modify network configuration, so that I can maintain security and prevent unauthorized changes.

#### Acceptance Criteria

1. THE Network_Module SHALL protect all routes with the Core_System permission middleware
2. THE Network_Module SHALL require module.network.view permission for accessing VLAN list and detail pages
3. THE Network_Module SHALL require module.network.edit permission for creating, updating, or deleting VLANs
4. THE Network_Module SHALL require module.network.edit permission for modifying IP address records
5. THE Network_Module SHALL require module.network.view permission for adding VLAN comments
6. WHERE a user with super-admin role accesses any Network_Module function, THE Network_Module SHALL grant access
7. WHERE a user without required permissions attempts to access a protected route, THE Network_Module SHALL return a 403 Forbidden response
8. THE Network_Module SHALL hide edit controls in the UI when the user lacks module.network.edit permission
9. THE Network_Module SHALL validate permissions on both frontend and backend
10. THE Network_Module SHALL log unauthorized access attempts to the audit log

### Requirement 12: Data Validation and Integrity

**User Story:** As a network administrator, I want the system to validate network configuration data, so that I can prevent configuration errors and maintain data integrity.

#### Acceptance Criteria

1. WHEN a VLAN is created or updated, THE Network_Module SHALL validate that vlan_name is not empty
2. WHEN a VLAN is created or updated, THE Network_Module SHALL validate that network_address combined with cidr_suffix forms a valid subnet
3. WHEN a VLAN is created or updated with a gateway, THE Network_Module SHALL validate that the gateway address falls within the subnet
4. WHEN a VLAN is created or updated, THE Network_Module SHALL validate that scan_interval_minutes is a positive integer
5. WHEN an IP address record is updated, THE Network_Module SHALL validate that dns_name contains only valid DNS characters
6. WHEN a VLAN comment is created, THE Network_Module SHALL validate that comment text is not empty
7. WHEN a VLAN comment is created, THE Network_Module SHALL validate that comment text does not exceed 1000 characters
8. IF any validation fails, THEN THE Network_Module SHALL return validation errors to the user without saving data
9. THE Network_Module SHALL use Laravel's validation system for all input validation
10. THE Network_Module SHALL display validation errors in the UI using Tailwind CSS error styling

### Requirement 13: IP Address Generation Algorithm

**User Story:** As a network administrator, I want IP addresses to be generated correctly for any valid subnet size, so that I have accurate inventory regardless of network configuration.

#### Acceptance Criteria

1. WHEN generating IP addresses for a VLAN, THE IP_Generator SHALL calculate the first host address as network_address + 1
2. WHEN generating IP addresses for a VLAN, THE IP_Generator SHALL calculate the last host address as broadcast_address - 1
3. WHEN generating IP addresses for a VLAN, THE IP_Generator SHALL calculate broadcast_address using the formula: network_address OR (NOT netmask)
4. WHEN generating IP addresses for a VLAN, THE IP_Generator SHALL calculate netmask from cidr_suffix using the formula: (0xFFFFFFFF << (32 - cidr_suffix))
5. WHEN generating IP addresses for a /31 subnet, THE IP_Generator SHALL generate exactly 2 host addresses
6. WHEN generating IP addresses for a /32 subnet, THE IP_Generator SHALL generate exactly 1 host address
7. WHEN generating IP addresses for a /24 subnet, THE IP_Generator SHALL generate exactly 254 host addresses
8. THE IP_Generator SHALL use PHP's ip2long function to convert IP addresses to integers for arithmetic
9. THE IP_Generator SHALL use PHP's long2ip function to convert integers back to IP address strings
10. WHEN IP address generation completes, THE IP_Generator SHALL return the count of generated addresses

### Requirement 14: Scanner Performance and Resource Management

**User Story:** As a system administrator, I want network scans to execute efficiently without overloading the server, so that scanning does not impact other system functions.

#### Acceptance Criteria

1. WHEN scanning a VLAN, THE Scanner_Service SHALL process IP addresses sequentially rather than in parallel
2. WHEN executing a ping command, THE Scanner_Service SHALL set a timeout of 1 second
3. WHEN executing a ping command, THE Scanner_Service SHALL send exactly 1 ping packet
4. THE Scanner_Service SHALL use database transactions when updating multiple IP address records
5. THE Scanner_Service SHALL commit transactions after processing each VLAN to prevent long-running locks
6. WHEN a scan is already running, THE Scanner_Service SHALL prevent concurrent scan execution for the same VLAN
7. THE Scanner_Service SHALL log scan start time, end time, and total IP addresses processed
8. THE Scanner_Service SHALL calculate and log scan duration for performance monitoring
9. IF a scan exceeds 5 minutes for a single VLAN, THEN THE Scanner_Service SHALL log a warning
10. THE Scanner_Service SHALL provide progress output when executed manually via Artisan command

### Requirement 15: Error Handling and Logging

**User Story:** As a system administrator, I want comprehensive error logging for network operations, so that I can troubleshoot issues and monitor system health.

#### Acceptance Criteria

1. WHEN any database operation fails, THE Network_Module SHALL log the error with full context
2. WHEN a ping command fails to execute, THE Scanner_Service SHALL log the error with IP address and error message
3. WHEN an ARP command fails to execute, THE Scanner_Service SHALL log the error with IP address and error message
4. WHEN IP address generation fails, THE IP_Generator SHALL log the error with VLAN details
5. WHEN a validation error occurs, THE Network_Module SHALL log the validation failure with input data
6. THE Network_Module SHALL use Laravel's logging system with appropriate log levels (error, warning, info)
7. THE Network_Module SHALL include contextual information in all log entries (user_id, vlan_id, ip_address as applicable)
8. WHEN a critical error occurs, THE Network_Module SHALL log at ERROR level
9. WHEN a scan completes successfully, THE Scanner_Service SHALL log at INFO level with summary statistics
10. THE Network_Module SHALL not log sensitive information such as passwords or authentication tokens

### Requirement 16: Database Schema and Migrations

**User Story:** As a developer, I want database migrations that create the required schema, so that the module can be installed and upgraded reliably.

#### Acceptance Criteria

1. THE Network_Module SHALL provide a migration that creates the vlans table with all required columns
2. THE Network_Module SHALL provide a migration that creates the ip_addresses table with all required columns
3. THE Network_Module SHALL provide a migration that creates the vlan_comments table with all required columns
4. WHEN creating the ip_addresses table, THE migration SHALL define a foreign key constraint on vlan_id referencing vlans.id
5. WHEN creating the vlan_comments table, THE migration SHALL define a foreign key constraint on vlan_id referencing vlans.id
6. WHEN creating the vlan_comments table, THE migration SHALL define a foreign key constraint on user_id referencing users.id
7. THE migration SHALL set ON DELETE CASCADE for the vlan_id foreign key in ip_addresses
8. THE migration SHALL set ON DELETE CASCADE for the vlan_id foreign key in vlan_comments
9. THE migration SHALL create indexes on frequently queried columns (vlan_id, is_online, ip_address)
10. THE migration SHALL set appropriate default values (internes_netz default false, ipscan default false, scan_interval_minutes default 60)

### Requirement 17: VLAN Form Interface

**User Story:** As a network administrator, I want intuitive forms for creating and editing VLANs, so that I can configure networks efficiently and without errors.

#### Acceptance Criteria

1. THE Network_Module SHALL provide a VLAN creation form with fields for all VLAN attributes
2. THE Network_Module SHALL provide a VLAN edit form pre-populated with existing VLAN data
3. WHEN displaying VLAN forms, THE Network_Module SHALL use Tailwind CSS form styling consistent with the Core_System
4. THE Network_Module SHALL provide helpful placeholder text for each form field
5. THE Network_Module SHALL provide field labels that clearly describe expected input
6. THE Network_Module SHALL mark required fields with visual indicators
7. WHEN a form submission fails validation, THE Network_Module SHALL display error messages next to the relevant fields
8. WHEN a form submission fails validation, THE Network_Module SHALL preserve user input for correction
9. THE Network_Module SHALL provide a checkbox for the ipscan field with clear labeling
10. THE Network_Module SHALL provide a numeric input for scan_interval_minutes with a default value of 60
11. WHEN a VLAN is successfully created or updated, THE Network_Module SHALL redirect to the VLAN detail page
12. WHEN a VLAN is successfully created or updated, THE Network_Module SHALL display a success message
