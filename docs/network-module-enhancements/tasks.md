# Implementation Plan: Network Module Enhancements

## Overview

This implementation plan breaks down the Network Module Enhancements into discrete, incremental coding tasks. The approach focuses on building features layer by layer: first the data layer (model enhancements), then the business logic (controllers), then the presentation layer (views), and finally integration and testing. Each task builds on previous work to ensure no orphaned code.

## Tasks

- [x] 1. Enhance IpAddress model with DHCP range and navigation methods
  - Add isInDhcpRange() method to check if IP is within VLAN's DHCP range
  - Add getFormattedMacAddress() method for consistent MAC address formatting
  - Add getPreviousIpAddress() method for navigation
  - Add getNextIpAddress() method for navigation
  - Add query scopes: inDhcpRange, hasDnsName, hasComment, filterByStatus, searchByTerm
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 13.1, 13.2, 13.3, 13.4, 13.7, 17.1, 17.2, 17.3, 17.4, 17.6, 17.7_

- [ ]* 1.1 Write property test for DHCP range calculation
  - **Property 1: DHCP Range Membership Calculation**
  - **Validates: Requirements 2.1, 2.2, 2.4**

- [ ]* 1.2 Write property test for DHCP range null handling
  - **Property 2: DHCP Range Null Handling**
  - **Validates: Requirements 2.3**

- [ ]* 1.3 Write property test for MAC address formatting
  - **Property 29: MAC Address Formatting Consistency**
  - **Property 30: MAC Address Format Conversion**
  - **Validates: Requirements 17.1, 17.2, 17.3, 17.4, 17.7, 17.10**

- [ ]* 1.4 Write unit tests for IP navigation methods
  - Test getPreviousIpAddress() with first, middle, and last IPs
  - Test getNextIpAddress() with first, middle, and last IPs
  - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5, 13.6, 13.7_

- [x] 2. Enhance Vlan model with search scope
  - Add searchByTerm() scope for global search functionality
  - Handle numeric queries (VLAN ID exact match)
  - Handle text queries (VLAN name and network address partial match)
  - _Requirements: 3.2, 3.6, 3.7_

- [ ]* 2.1 Write property test for VLAN search
  - **Property 5: Search Across Multiple Fields**
  - **Validates: Requirements 3.2, 3.3, 3.4, 3.5, 3.6, 3.7**

- [x] 3. Create database migration for search indexes
  - Add index on ip_addresses.dns_name
  - Add index on ip_addresses.mac_address
  - Add index on vlans.vlan_name
  - Add index on vlans.network_address
  - _Requirements: 11.1_

- [x] 4. Create UpdateIpAddressRequest form request class
  - Add authorization check (module.network.edit permission)
  - Add validation rules for dns_name (nullable, string, max 255, regex for valid DNS characters)
  - Add validation rules for comment (nullable, string, max 1000)
  - _Requirements: 9.3_

- [ ]* 4.1 Write unit tests for UpdateIpAddressRequest validation
  - Test valid DNS names
  - Test invalid DNS names (special characters)
  - Test comment length validation
  - _Requirements: 9.3_

- [x] 5. Enhance IpAddressController with detail page and update functionality
  - Add show() method to display IP address detail page
  - Load IP address with VLAN relationship
  - Calculate previous and next IP addresses
  - Determine DHCP range membership
  - Enhance update() method to handle both AJAX and form submissions
  - Return JSON for AJAX requests, redirect for form submissions
  - Log updates to audit log
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 1.10, 1.11, 9.4, 9.5, 9.6, 9.7, 9.8, 9.9, 9.10_

- [ ]* 5.1 Write property test for IP detail page field display
  - **Property 21: IP Detail Page Field Display**
  - **Validates: Requirements 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 1.10**

- [ ]* 5.2 Write property test for IP update preserving scan data
  - **Property 24: IP Update Preserves Scan Data**
  - **Validates: Requirements 9.5**

- [ ]* 5.3 Write unit tests for IpAddressController
  - Test show() with valid IP address ID
  - Test show() with invalid IP address ID (404)
  - Test update() with valid data
  - Test update() with invalid data (validation errors)
  - Test update() permission checks
  - _Requirements: 1.1, 9.4, 9.6, 9.7, 20.1, 20.2_

- [x] 6. Create SearchController for global search functionality
  - Add index() method for search page
  - Add search() method for AJAX search endpoint
  - Implement searchVlans() helper method
  - Implement searchIpAddresses() helper method
  - Validate search query (min 3 characters, max 255)
  - Sanitize search input to prevent SQL injection and XSS
  - Normalize MAC address queries (remove separators)
  - Limit results to 50 per type
  - Eager load relationships for efficiency
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10, 3.11, 3.12, 3.13, 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8, 8.9, 8.10, 8.11, 8.12, 11.2, 11.3, 11.4, 11.7, 14.1, 14.2, 14.3, 14.4, 14.5, 14.6, 14.7, 14.8_

- [ ]* 6.1 Write property test for search query minimum length
  - **Property 4: Search Query Minimum Length**
  - **Validates: Requirements 11.7, 14.1**

- [ ]* 6.2 Write property test for search result limit
  - **Property 6: Search Result Limit**
  - **Validates: Requirements 8.11, 11.3**

- [ ]* 6.3 Write property test for MAC address search normalization
  - **Property 8: MAC Address Search Normalization**
  - **Validates: Requirements 3.5, 18.7**

- [ ]* 6.4 Write property test for search input sanitization
  - **Property 33: Search Input Sanitization**
  - **Validates: Requirements 14.3, 14.7, 14.8**

- [ ]* 6.5 Write unit tests for SearchController
  - Test search with IP address queries
  - Test search with DNS name queries
  - Test search with MAC address queries (various formats)
  - Test search with VLAN name queries
  - Test search with VLAN ID queries
  - Test search with queries <3 characters
  - Test search with empty/whitespace queries
  - Test search permission checks
  - _Requirements: 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.12, 3.13, 14.1, 14.2, 19.1, 19.4_

- [x] 7. Enhance VlanController with search redirect, sorting, and filtering
  - Modify index() method to handle search queries (redirect to SearchController)
  - Add sort parameter handling to index() method
  - Store sort preferences in session
  - Apply sorting to VLAN query (vlan_id, vlan_name, network_address, online_count)
  - Modify show() method to handle filter parameters
  - Add filter parameter handling (status, dhcp, has_dns, has_comment)
  - Store filter preferences in session
  - Apply filters using model scopes
  - Add sort parameter handling to show() method
  - Apply sorting to IP address query
  - Add pagination (50 IPs per page)
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 4.10, 4.11, 4.12, 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9, 5.10, 5.11, 5.12, 5.13, 5.14, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8, 6.9, 6.10, 6.11, 6.12, 6.13, 6.14, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9, 7.10, 12.1, 12.2, 12.3, 12.4, 12.5_

- [ ]* 7.1 Write property test for VLAN list sort persistence
  - **Property 9: VLAN List Sort Persistence**
  - **Validates: Requirements 4.11, 4.12, 12.1, 12.5**

- [ ]* 7.2 Write property test for sort toggle behavior
  - **Property 10: VLAN List Sort Toggle**
  - **Validates: Requirements 4.7, 4.8, 4.9**

- [ ]* 7.3 Write property test for IP numeric sorting
  - **Property 11: IP Address Numeric Sorting**
  - **Validates: Requirements 6.7**

- [ ]* 7.4 Write property test for status filters
  - **Property 12: Online Status Filter**
  - **Property 13: Offline Status Filter**
  - **Validates: Requirements 5.6, 5.7**

- [ ]* 7.5 Write property test for multiple filter AND logic
  - **Property 17: Multiple Filter AND Logic**
  - **Validates: Requirements 5.11**

- [ ]* 7.6 Write property test for filter state persistence
  - **Property 18: Filter State Persistence**
  - **Validates: Requirements 5.13, 5.14, 12.2, 12.3, 12.4**

- [ ]* 7.7 Write property test for pagination
  - **Property 19: Pagination Page Size**
  - **Property 20: Pagination State Preservation**
  - **Validates: Requirements 7.2, 7.6**

- [ ]* 7.8 Write unit tests for VlanController enhancements
  - Test index() with sort parameters
  - Test show() with filter parameters
  - Test show() with sort parameters
  - Test show() with pagination
  - Test session storage of preferences
  - _Requirements: 4.6, 4.7, 4.8, 4.9, 4.11, 4.12, 5.6, 5.7, 5.8, 5.9, 5.10, 5.11, 5.13, 5.14, 6.7, 6.8, 6.9, 6.10, 6.12, 6.13, 6.14, 7.2, 7.6_

- [x] 8. Add new routes for IP detail page and search
  - Add route for IP address detail page (network.ip-addresses.show)
  - Add route for search page (network.search)
  - Add route for AJAX search endpoint (network.search.ajax)
  - Apply auth and permission middleware
  - _Requirements: 1.1, 3.1, 19.1, 19.2_

- [x] 9. Checkpoint - Ensure backend functionality works
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. Create IP address detail page view (ip-addresses/show.blade.php)
  - Create layout with header and back button
  - Add IP Information Card with all IP details
  - Add VLAN Information Card with VLAN context
  - Add Scan History Card with timestamps
  - Add DHCP badge if IP is in DHCP range
  - Add editable fields for DNS name and comment (if has edit permission)
  - Add read-only display for DNS name and comment (if only view permission)
  - Add navigation buttons (Previous IP, Next IP, Back to VLAN)
  - Disable Previous button if first IP
  - Disable Next button if last IP
  - Add AJAX form submission for inline editing
  - Display success/error messages
  - Use Tailwind CSS for styling
  - _Requirements: 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 1.10, 1.11, 1.12, 1.13, 9.1, 9.2, 9.8, 9.9, 13.1, 13.2, 13.3, 13.4, 13.5, 13.6, 13.8, 13.9, 13.10, 16.1, 16.2, 16.3, 16.4, 16.5, 16.6, 16.7, 16.8, 16.9, 16.10_

- [ ]* 10.1 Write property test for IP detail edit permission
  - **Property 22: IP Detail Edit Permission**
  - **Property 23: IP Detail Read-Only Permission**
  - **Validates: Requirements 1.12, 1.13, 9.1, 9.2, 19.6**

- [ ]* 10.2 Write property test for IP navigation
  - **Property 25: Previous IP Navigation**
  - **Property 26: Next IP Navigation**
  - **Property 27: First IP Previous Button Disabled**
  - **Property 28: Last IP Next Button Disabled**
  - **Validates: Requirements 13.1, 13.2, 13.3, 13.4, 13.5, 13.6, 13.7**

- [x] 11. Create search bar component (components/search-bar.blade.php)
  - Create reusable search bar component
  - Add text input with search icon
  - Add search button
  - Add placeholder text
  - Add min length validation (3 characters)
  - Display validation message if query too short
  - Pre-fill with current query if present
  - Use Tailwind CSS for styling
  - _Requirements: 3.1, 11.7, 14.1_

- [x] 12. Create filter panel component (components/filter-panel.blade.php)
  - Create collapsible filter panel
  - Add status filter dropdown (All/Online/Offline)
  - Add DHCP range checkbox filter
  - Add "Has DNS name" checkbox filter
  - Add "Has comment" checkbox filter
  - Display active filter count badge in header
  - Add "Apply Filters" button
  - Add "Clear Filters" button
  - Use Alpine.js for collapsible behavior
  - Use Tailwind CSS for styling
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 12.6, 12.7, 12.8, 15.1, 15.2_

- [x] 13. Create DHCP badge component (components/dhcp-badge.blade.php)
  - Create reusable DHCP badge component
  - Display blue badge with "DHCP" text
  - Only render if IP is in DHCP range
  - Use Tailwind CSS for styling
  - _Requirements: 2.5, 2.6, 2.7, 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7_

- [ ]* 13.1 Write property test for DHCP badge display
  - **Property 3: DHCP Badge Display Consistency**
  - **Validates: Requirements 2.5, 2.6, 10.1, 10.2, 10.5**

- [x] 14. Create search results page view (search/index.blade.php)
  - Create layout with search bar (pre-filled)
  - Add results summary (count of VLANs and IPs found)
  - Add VLAN results section with table
  - Add IP address results section with table
  - Include VLAN context for IP results
  - Highlight matched search terms
  - Add links to detail pages
  - Display "More results available" message if >50 results
  - Display "No results" message if no matches
  - Use Tailwind CSS for styling
  - _Requirements: 3.8, 3.9, 3.10, 3.11, 3.12, 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8, 8.9, 8.10, 8.11, 8.12, 18.1, 18.2, 18.3, 18.4, 18.5, 18.6, 18.7, 18.8, 18.9, 18.10_

- [ ]* 14.1 Write property test for search term highlighting
  - **Property 32: Search Term Highlighting**
  - **Validates: Requirements 18.1, 18.2, 18.3, 18.4**

- [ ]* 14.2 Write property test for search result VLAN context
  - **Property 7: Search Result VLAN Context**
  - **Validates: Requirements 3.9, 8.5**

- [x] 15. Enhance VLAN list view (vlans/index.blade.php)
  - Add search bar component at top of page
  - Add sortable column headers (VLAN ID, Name, Network, Online Count)
  - Add sort direction indicators (up/down arrows)
  - Add click handlers for sort toggle
  - Maintain current sort state in UI
  - Use Tailwind CSS for styling
  - _Requirements: 3.1, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 4.10_

- [x] 16. Enhance VLAN detail view (vlans/show.blade.php)
  - Add filter panel component
  - Add sortable column headers for IP address table
  - Add sort direction indicators
  - Add DHCP badge to IP address rows
  - Add links to IP detail page from IP address column
  - Add pagination controls (50 IPs per page)
  - Display page number, total pages, and IP range
  - Add "First", "Previous", "Next", "Last" buttons
  - Disable buttons at first/last page
  - Maintain filter and sort state in pagination links
  - Use Tailwind CSS for styling
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.12, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9, 7.10, 10.1, 10.2, 10.3, 10.4_

- [x] 17. Add AJAX functionality for inline editing and filters
  - Add JavaScript for inline IP address editing
  - Handle form submission via AJAX
  - Update displayed values without page reload
  - Display success/error messages
  - Add JavaScript for filter updates via AJAX (optional enhancement)
  - Add JavaScript for sort updates via AJAX (optional enhancement)
  - Add loading indicators during AJAX requests
  - Handle AJAX errors gracefully
  - _Requirements: 9.8, 9.9, 15.3, 15.4, 15.5, 15.6, 15.7, 15.8, 15.9_

- [ ]* 17.1 Write unit tests for AJAX functionality
  - Test inline editing AJAX requests
  - Test AJAX response handling
  - Test AJAX error handling
  - _Requirements: 9.8, 9.9, 15.8, 15.9_

- [x] 18. Add permission checks to all new routes and views
  - Verify module.network.view permission for search routes
  - Verify module.network.view permission for IP detail route
  - Verify module.network.edit permission for IP update route
  - Hide edit controls in views based on permissions
  - Add super-admin bypass for all permission checks
  - Log unauthorized access attempts to audit log
  - _Requirements: 19.1, 19.2, 19.3, 19.4, 19.5, 19.6, 19.7, 19.8, 19.9, 19.10_

- [ ]* 18.1 Write property test for permission-based route access
  - **Property 36: Permission-Based Route Access**
  - **Property 37: Permission-Based Edit Access**
  - **Property 38: Super Admin Universal Access**
  - **Validates: Requirements 19.1, 19.2, 19.3, 19.4, 19.5, 19.10**

- [ ]* 18.2 Write unit tests for permission checks
  - Test search access with and without module.network.view
  - Test IP detail access with and without module.network.view
  - Test IP update with and without module.network.edit
  - Test super-admin access to all features
  - _Requirements: 19.1, 19.2, 19.3, 19.4, 19.5, 19.6, 19.10_

- [x] 19. Add error handling and user-friendly error messages
  - Add 404 error handling for invalid IP address IDs
  - Add 404 error handling for invalid VLAN IDs
  - Add validation error display in forms
  - Add database error handling with generic messages
  - Add AJAX error handling with user-friendly messages
  - Add search timeout handling
  - Add slow query logging
  - Ensure no sensitive information in error messages
  - _Requirements: 20.1, 20.2, 20.3, 20.4, 20.5, 20.6, 20.7, 20.8, 20.9, 20.10_

- [ ]* 19.1 Write property test for invalid IP address 404
  - **Property 39: Invalid IP Address 404 Error**
  - **Validates: Requirements 20.1, 20.2**

- [ ]* 19.2 Write unit tests for error handling
  - Test 404 errors for invalid IDs
  - Test validation error display
  - Test database error handling
  - Test AJAX error responses
  - _Requirements: 20.1, 20.2, 20.3, 20.4, 20.5, 20.6_

- [x] 20. Final checkpoint - Integration testing and verification
  - Test complete search workflow (search → results → detail page)
  - Test complete filter workflow (apply filters → view results → clear filters)
  - Test complete sort workflow (sort → navigate → sort persists)
  - Test complete pagination workflow (filter → sort → paginate → navigate)
  - Test complete IP detail workflow (view → edit → navigate → back)
  - Test permission-based access across all features
  - Test DHCP badge display across all views
  - Test MAC address formatting consistency
  - Verify all audit log entries are created
  - Verify session state persistence works correctly
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional property-based and unit tests that can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties
- Unit tests validate specific examples and edge cases
- The implementation follows a layered approach: models → controllers → routes → views → integration
- AJAX functionality is added after basic functionality works to ensure graceful degradation
- Permission checks are added near the end to ensure they're applied consistently across all features
