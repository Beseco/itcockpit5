# Design Document: Network Module Enhancements

## Overview

The Network Module Enhancements extend the existing Network/VLAN Management Module with advanced search, filtering, sorting, and IP address detail view capabilities. This design builds upon the existing Laravel 11 module architecture and maintains consistency with the IT Cockpit Core System patterns.

The enhancements add four major functional areas:

1. **IP Address Detail Pages**: Dedicated pages showing comprehensive information about individual IP addresses
2. **Global Search**: Cross-VLAN search functionality for quickly locating devices and networks
3. **Advanced Filtering and Sorting**: Powerful tools for organizing and narrowing down IP address lists
4. **DHCP Range Indicators**: Visual identification of IP addresses within DHCP ranges

Key design decisions:
- Extend existing controllers rather than creating entirely new ones where possible
- Use AJAX for filter/sort operations to provide responsive UI without full page reloads
- Leverage Laravel's query builder for efficient database operations
- Use session storage for persisting user preferences (filters, sort order)
- Maintain Tailwind CSS styling consistency with existing views
- Add helper methods to existing models rather than creating new service classes

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│              Network Module Enhancements                    │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │              Web Layer (Controllers)                  │  │
│  │  ├─ VlanController (enhanced with search & sort)     │  │
│  │  ├─ IpAddressController (enhanced with detail page)  │  │
│  │  └─ SearchController (new - global search)           │  │
│  └──────────────────────┬───────────────────────────────┘  │
│                         │                                   │
│  ┌──────────────────────▼───────────────────────────────┐  │
│  │              Enhanced Models                          │  │
│  │  ├─ Vlan (add search scopes)                         │  │
│  │  └─ IpAddress (add DHCP check, filter scopes)       │  │
│  └──────────────────────┬───────────────────────────────┘  │
│                         │                                   │
│  ┌──────────────────────▼───────────────────────────────┐  │
│  │              View Layer (Blade Templates)             │  │
│  │  ├─ vlans/index.blade.php (add search bar & sort)    │  │
│  │  ├─ vlans/show.blade.php (add filters & sort)        │  │
│  │  ├─ ip-addresses/show.blade.php (new detail page)    │  │
│  │  └─ components/search-results.blade.php (new)        │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Module Directory Structure (New/Modified Files)

```
app/Modules/Network/
├── Http/
│   ├── Controllers/
│   │   ├── VlanController.php (modified - add search & sort)
│   │   ├── IpAddressController.php (modified - add show method)
│   │   └── SearchController.php (new - global search)
│   └── Requests/
│       └── UpdateIpAddressRequest.php (new)
├── Models/
│   ├── Vlan.php (modified - add search scopes)
│   └── IpAddress.php (modified - add DHCP check, filter scopes)
├── Routes/
│   └── web.php (modified - add new routes)
└── Views/
    ├── vlans/
    │   ├── index.blade.php (modified - add search & sort)
    │   └── show.blade.php (modified - add filters & sort)
    ├── ip-addresses/
    │   └── show.blade.php (new - detail page)
    └── components/
        ├── search-bar.blade.php (new)
        ├── search-results.blade.php (new)
        ├── filter-panel.blade.php (new)
        └── dhcp-badge.blade.php (new)
```

## Components and Interfaces

### 1. IpAddressController (Enhanced)

Add new method for displaying IP address detail page.

**New Methods**:

```php
show(IpAddress $ipAddress): View
  - Loads IP address with VLAN relationship
  - Determines if IP is in DHCP range
  - Finds previous and next IP addresses in the VLAN (by numeric IP order)
  - Returns ip-addresses/show view with IP address details
  - Requires: module.network.view permission

update(UpdateIpAddressRequest $request, IpAddress $ipAddress): JsonResponse|RedirectResponse
  - Enhanced to handle both AJAX and form submissions
  - Validates input (dns_name, comment)
  - Updates IP address record
  - Preserves scan-related fields
  - Logs action via AuditLogger
  - Returns JSON for AJAX, redirect for form submission
  - Requires: module.network.edit permission
```

### 2. SearchController (New)

Handles global search across VLANs and IP addresses.

**Methods**:

```php
index(Request $request): View
  - Validates search query (min 3 characters, max 255)
  - Searches across VLANs (vlan_id, vlan_name)
  - Searches across IP addresses (ip_address, dns_name, mac_address)
  - Normalizes MAC address search (remove separators)
  - Limits results to 50 per type
  - Returns search results view
  - Requires: module.network.view permission

search(Request $request): JsonResponse
  - AJAX endpoint for live search
  - Same logic as index() but returns JSON
  - Includes VLAN context for IP address results
  - Highlights matched terms in results
  - Requires: module.network.view permission
```

**Search Algorithm**:

```php
searchVlans(string $query): Collection
  1. Trim and sanitize query
  2. Check if query is numeric (for VLAN ID exact match)
  3. Search vlan_name with LIKE %query%
  4. Search vlan_id with exact match if numeric
  5. Limit to 50 results
  6. Return collection with online_count and total_ip_count

searchIpAddresses(string $query): Collection
  1. Trim and sanitize query
  2. Normalize MAC address query (remove : and -)
  3. Search ip_address with LIKE %query%
  4. Search dns_name with LIKE %query% (case-insensitive)
  5. Search mac_address with normalized comparison
  6. Eager load vlan relationship
  7. Limit to 50 results
  8. Return collection with VLAN context
```

### 3. VlanController (Enhanced)

Add search and sorting capabilities to index method.

**Modified Methods**:

```php
index(Request $request): View
  - Check for search query parameter
  - If search query exists, redirect to SearchController
  - Check for sort parameter (column, direction)
  - Store sort preference in session
  - Apply sort to VLAN query
  - Default sort: vlan_id ascending
  - Supported sort columns: vlan_id, vlan_name, network_address, online_count
  - Returns vlans/index view with sorted VLANs

show(Vlan $vlan, Request $request): View
  - Load VLAN with relationships
  - Check for filter parameters (status, dhcp, has_dns, has_comment)
  - Check for sort parameter (column, direction)
  - Store filter and sort preferences in session
  - Apply filters to IP address query using scopes
  - Apply sort to IP address query
  - Paginate IP addresses (50 per page)
  - Return vlans/show view with filtered and sorted IP addresses
```

**Sort Implementation**:

```php
applySorting(Builder $query, string $column, string $direction): Builder
  - Validate column is in allowed list
  - Validate direction is 'asc' or 'desc'
  - For online_count: use subquery to count online IPs
  - For IP address sorting: use INET_ATON() for numeric comparison
  - For other columns: use standard ORDER BY
  - Return modified query builder
```

### 4. Enhanced IpAddress Model

Add methods and scopes for DHCP range checking and filtering.

**New Methods**:

```php
isInDhcpRange(): bool
  - Check if VLAN has dhcp_from and dhcp_to configured
  - Convert IP address, dhcp_from, and dhcp_to to integers using ip2long()
  - Return true if IP is between dhcp_from and dhcp_to (inclusive)
  - Return false if DHCP range not configured

getFormattedMacAddress(): ?string
  - Return null if mac_address is null
  - Convert MAC address to uppercase
  - Replace hyphens with colons
  - Return formatted MAC address (XX:XX:XX:XX:XX:XX)

getPreviousIpAddress(): ?IpAddress
  - Get all IP addresses in the same VLAN
  - Order by INET_ATON(ip_address) ascending
  - Find current IP position
  - Return previous IP or null if first

getNextIpAddress(): ?IpAddress
  - Get all IP addresses in the same VLAN
  - Order by INET_ATON(ip_address) ascending
  - Find current IP position
  - Return next IP or null if last
```

**New Scopes**:

```php
scopeInDhcpRange($query)
  - Join with vlans table
  - Filter where IP is between dhcp_from and dhcp_to
  - Use INET_ATON() for numeric comparison

scopeHasDnsName($query)
  - Filter where dns_name is not null
  - Filter where dns_name is not empty string

scopeHasComment($query)
  - Filter where comment is not null
  - Filter where comment is not empty string

scopeFilterByStatus($query, ?string $status)
  - If status is 'online': where is_online = true
  - If status is 'offline': where is_online = false
  - If status is null or 'all': no filter

scopeSearchByTerm($query, string $term)
  - Search ip_address with LIKE %term%
  - OR search dns_name with LIKE %term%
  - OR search mac_address with normalized comparison
```

### 5. Enhanced Vlan Model

Add search scope for global search functionality.

**New Scopes**:

```php
scopeSearchByTerm($query, string $term)
  - If term is numeric: where vlan_id = term (exact match)
  - OR search vlan_name with LIKE %term% (case-insensitive)
  - OR search network_address with LIKE %term%
```

### 6. UpdateIpAddressRequest (New)

Form request for validating IP address updates.

```php
authorize(): bool
  - Returns auth()->user()->hasModulePermission('network', 'edit')

rules(): array
  - dns_name: nullable|string|max:255|regex:/^[a-zA-Z0-9.-]+$/
  - comment: nullable|string|max:1000
```

## Data Models

### IpAddress Model (Enhanced)

```php
class IpAddress extends Model
{
    // ... existing code ...

    /**
     * Check if this IP address is within the VLAN's DHCP range.
     */
    public function isInDhcpRange(): bool
    {
        $vlan = $this->vlan;

        if (!$vlan || !$vlan->dhcp_from || !$vlan->dhcp_to) {
            return false;
        }

        $ipLong = ip2long($this->ip_address);
        $dhcpFromLong = ip2long($vlan->dhcp_from);
        $dhcpToLong = ip2long($vlan->dhcp_to);

        return $ipLong >= $dhcpFromLong && $ipLong <= $dhcpToLong;
    }

    /**
     * Get formatted MAC address (uppercase with colons).
     */
    public function getFormattedMacAddress(): ?string
    {
        if (!$this->mac_address) {
            return null;
        }

        return strtoupper(str_replace('-', ':', $this->mac_address));
    }

    /**
     * Get the previous IP address in the VLAN (by numeric order).
     */
    public function getPreviousIpAddress(): ?IpAddress
    {
        return static::where('vlan_id', $this->vlan_id)
            ->whereRaw('INET_ATON(ip_address) < INET_ATON(?)', [$this->ip_address])
            ->orderByRaw('INET_ATON(ip_address) DESC')
            ->first();
    }

    /**
     * Get the next IP address in the VLAN (by numeric order).
     */
    public function getNextIpAddress(): ?IpAddress
    {
        return static::where('vlan_id', $this->vlan_id)
            ->whereRaw('INET_ATON(ip_address) > INET_ATON(?)', [$this->ip_address])
            ->orderByRaw('INET_ATON(ip_address) ASC')
            ->first();
    }

    /**
     * Scope to filter IP addresses within DHCP range.
     */
    public function scopeInDhcpRange($query)
    {
        return $query->whereHas('vlan', function ($q) {
            $q->whereNotNull('dhcp_from')
              ->whereNotNull('dhcp_to');
        })->whereRaw('INET_ATON(ip_addresses.ip_address) >= INET_ATON(vlans.dhcp_from)')
          ->whereRaw('INET_ATON(ip_addresses.ip_address) <= INET_ATON(vlans.dhcp_to)')
          ->join('vlans', 'ip_addresses.vlan_id', '=', 'vlans.id');
    }

    /**
     * Scope to filter IP addresses with DNS names.
     */
    public function scopeHasDnsName($query)
    {
        return $query->whereNotNull('dns_name')
                     ->where('dns_name', '!=', '');
    }

    /**
     * Scope to filter IP addresses with comments.
     */
    public function scopeHasComment($query)
    {
        return $query->whereNotNull('comment')
                     ->where('comment', '!=', '');
    }

    /**
     * Scope to filter by online/offline status.
     */
    public function scopeFilterByStatus($query, ?string $status)
    {
        if ($status === 'online') {
            return $query->where('is_online', true);
        } elseif ($status === 'offline') {
            return $query->where('is_online', false);
        }

        return $query;
    }

    /**
     * Scope to search by term across multiple fields.
     */
    public function scopeSearchByTerm($query, string $term)
    {
        $normalizedTerm = str_replace([':', '-'], '', $term);

        return $query->where(function ($q) use ($term, $normalizedTerm) {
            $q->where('ip_address', 'LIKE', "%{$term}%")
              ->orWhere('dns_name', 'LIKE', "%{$term}%")
              ->orWhereRaw('REPLACE(REPLACE(mac_address, ":", ""), "-", "") LIKE ?', ["%{$normalizedTerm}%"]);
        });
    }
}
```

### Vlan Model (Enhanced)

```php
class Vlan extends Model
{
    // ... existing code ...

    /**
     * Scope to search VLANs by term.
     */
    public function scopeSearchByTerm($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            // Exact match for numeric VLAN ID
            if (is_numeric($term)) {
                $q->where('vlan_id', $term);
            }

            // Partial match for VLAN name and network address
            $q->orWhere('vlan_name', 'LIKE', "%{$term}%")
              ->orWhere('network_address', 'LIKE', "%{$term}%");
        });
    }
}
```

### Database Indexes (New)

No new tables required, but add indexes for performance:

```php
// Migration: add_search_indexes_to_network_tables.php

Schema::table('ip_addresses', function (Blueprint $table) {
    $table->index('dns_name');
    $table->index('mac_address');
});

Schema::table('vlans', function (Blueprint $table) {
    $table->index('vlan_name');
    $table->index('network_address');
});
```

## Routes

### New Routes

```php
// IP Address Detail Page
Route::get('/ip-addresses/{ipAddress}', [IpAddressController::class, 'show'])
    ->name('network.ip-addresses.show')
    ->middleware(['auth', 'permission:module.network.view']);

// Global Search
Route::get('/search', [SearchController::class, 'index'])
    ->name('network.search')
    ->middleware(['auth', 'permission:module.network.view']);

Route::get('/search/ajax', [SearchController::class, 'search'])
    ->name('network.search.ajax')
    ->middleware(['auth', 'permission:module.network.view']);
```

## Views

### 1. IP Address Detail Page (ip-addresses/show.blade.php)

**Layout**:
- Header with IP address and back button
- IP Information Card
  - IP Address (large, prominent)
  - DNS Name (editable if has edit permission)
  - MAC Address (formatted)
  - Comment (editable if has edit permission)
  - Online Status Badge
  - DHCP Range Badge (if applicable)
- VLAN Information Card
  - VLAN ID and Name (link to VLAN detail)
  - Network Address
  - Gateway
  - DHCP Range
- Scan History Card
  - Last Scanned (relative time)
  - Last Online (relative time)
  - Ping Response Time (if online)
- Navigation Buttons
  - Previous IP (disabled if first)
  - Next IP (disabled if last)
  - Back to VLAN

**Edit Functionality**:
- Inline editing for DNS name and comment (if has edit permission)
- AJAX form submission
- Success/error messages without page reload

### 2. Enhanced VLAN List (vlans/index.blade.php)

**New Elements**:
- Search bar at top of page
  - Text input with search icon
  - "Search" button
  - Placeholder: "Search VLANs and IP addresses..."
  - Min 3 characters validation
- Sortable column headers
  - Click to sort ascending
  - Click again to sort descending
  - Click third time to reset to default
  - Visual indicator (up/down arrow) for current sort
- Sort columns: VLAN ID, Name, Network, Online Count

### 3. Enhanced VLAN Detail (vlans/show.blade.php)

**New Elements**:
- Filter Panel (collapsible)
  - Status filter: All / Online / Offline
  - DHCP Range filter: checkbox
  - Has DNS Name filter: checkbox
  - Has Comment filter: checkbox
  - Active filter count badge
  - "Clear Filters" button
- Sortable IP address table headers
  - Sort columns: IP Address, DNS Name, Status, Last Scan, Ping Time
  - Visual indicators for current sort
- DHCP badge in IP address rows
  - Blue badge with "DHCP" text
  - Only shown for IPs in DHCP range
- Pagination controls
  - 50 IPs per page
  - First, Previous, Next, Last buttons
  - Page number and total pages
  - IP range display (e.g., "Showing 1-50 of 254")
- Link to IP detail page from IP address column

### 4. Search Results Page (search/index.blade.php)

**Layout**:
- Search bar (pre-filled with query)
- Results summary (e.g., "Found 5 VLANs and 12 IP addresses")
- VLAN Results Section
  - Table with VLAN ID, Name, Network, Online Count
  - Highlighted search terms
  - Link to VLAN detail page
  - "More results available" message if >50
- IP Address Results Section
  - Table with IP, DNS Name, MAC, Status, VLAN Context
  - Highlighted search terms
  - Link to IP detail page
  - VLAN name and link
  - "More results available" message if >50
- No results message if no matches

### 5. Component: Search Bar (components/search-bar.blade.php)

Reusable search bar component.

```blade
<div class="mb-4">
    <form action="{{ route('network.search') }}" method="GET" class="flex gap-2">
        <div class="flex-1 relative">
            <input type="text" 
                   name="q" 
                   value="{{ request('q') }}" 
                   placeholder="Search VLANs and IP addresses..." 
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                   minlength="3"
                   required>
            <svg class="absolute right-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700">
            Search
        </button>
    </form>
    @if(request('q') && strlen(request('q')) < 3)
        <p class="mt-2 text-sm text-red-600">Please enter at least 3 characters</p>
    @endif
</div>
```

### 6. Component: Filter Panel (components/filter-panel.blade.php)

Collapsible filter panel for IP address lists.

```blade
<div x-data="{ open: {{ count(request()->only(['status', 'dhcp', 'has_dns', 'has_comment'])) > 0 ? 'true' : 'false' }} }" class="mb-4">
    <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2 bg-gray-100 rounded-md">
        <span class="font-medium">
            Filters
            @if(count(request()->only(['status', 'dhcp', 'has_dns', 'has_comment'])) > 0)
                <span class="ml-2 px-2 py-1 text-xs bg-indigo-600 text-white rounded-full">
                    {{ count(request()->only(['status', 'dhcp', 'has_dns', 'has_comment'])) }}
                </span>
            @endif
        </span>
        <svg :class="{ 'rotate-180': open }" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <div x-show="open" x-transition class="mt-2 p-4 bg-white border rounded-md">
        <form method="GET" class="space-y-4">
            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All</option>
                    <option value="online" {{ request('status') === 'online' ? 'selected' : '' }}>Online</option>
                    <option value="offline" {{ request('status') === 'offline' ? 'selected' : '' }}>Offline</option>
                </select>
            </div>

            <!-- DHCP Range Filter -->
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="dhcp" value="1" {{ request('dhcp') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700">In DHCP Range</span>
                </label>
            </div>

            <!-- Has DNS Name Filter -->
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="has_dns" value="1" {{ request('has_dns') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700">Has DNS Name</span>
                </label>
            </div>

            <!-- Has Comment Filter -->
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="has_comment" value="1" {{ request('has_comment') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700">Has Comment</span>
                </label>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700">
                    Apply Filters
                </button>
                <a href="{{ request()->url() }}" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                    Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>
```

### 7. Component: DHCP Badge (components/dhcp-badge.blade.php)

Badge component for indicating DHCP range membership.

```blade
@if($ipAddress->isInDhcpRange())
    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
        DHCP
    </span>
@endif
```

## Session State Management

### Filter and Sort Persistence

Store user preferences in session to maintain state across page navigations.

**Session Keys**:
- `network.vlan_list.sort_column`: Current sort column for VLAN list
- `network.vlan_list.sort_direction`: Current sort direction for VLAN list
- `network.vlan_detail.{vlan_id}.filters`: Active filters for specific VLAN
- `network.vlan_detail.{vlan_id}.sort_column`: Current sort column for VLAN detail
- `network.vlan_detail.{vlan_id}.sort_direction`: Current sort direction for VLAN detail

**Implementation**:

```php
// Store sort preference
session()->put('network.vlan_list.sort_column', $column);
session()->put('network.vlan_list.sort_direction', $direction);

// Retrieve sort preference
$sortColumn = session('network.vlan_list.sort_column', 'vlan_id');
$sortDirection = session('network.vlan_list.sort_direction', 'asc');

// Store filter preferences
session()->put("network.vlan_detail.{$vlan->id}.filters", $request->only(['status', 'dhcp', 'has_dns', 'has_comment']));

// Retrieve filter preferences
$filters = session("network.vlan_detail.{$vlan->id}.filters", []);
```


## Correctness Properties

A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.

### Property 1: DHCP Range Membership Calculation

*For any* IP address with a VLAN that has dhcp_from and dhcp_to configured, the isInDhcpRange() method should return true if and only if the IP address (as an integer) is between dhcp_from and dhcp_to (inclusive).

**Validates: Requirements 2.1, 2.2, 2.4**

### Property 2: DHCP Range Null Handling

*For any* IP address with a VLAN that has null dhcp_from or null dhcp_to, the isInDhcpRange() method should return false.

**Validates: Requirements 2.3**

### Property 3: DHCP Badge Display Consistency

*For any* IP address displayed in a table, a DHCP badge should be shown if and only if isInDhcpRange() returns true.

**Validates: Requirements 2.5, 2.6, 10.1, 10.2, 10.5**

### Property 4: Search Query Minimum Length

*For any* search query with fewer than 3 characters, the system should display a validation message and not execute the search.

**Validates: Requirements 11.7, 14.1**

### Property 5: Search Across Multiple Fields

*For any* search query, the results should include all VLANs and IP addresses where the query matches vlan_id, vlan_name, network_address, ip_address, dns_name, or mac_address.

**Validates: Requirements 3.2, 3.3, 3.4, 3.5, 3.6, 3.7**

### Property 6: Search Result Limit

*For any* search query, the results should be limited to 50 VLANs and 50 IP addresses maximum.

**Validates: Requirements 8.11, 11.3**

### Property 7: Search Result VLAN Context

*For any* IP address in search results, the VLAN name and VLAN ID should be included in the result data.

**Validates: Requirements 3.9, 8.5**

### Property 8: MAC Address Search Normalization

*For any* search query containing a MAC address with colons or hyphens, the search should match MAC addresses regardless of separator format.

**Validates: Requirements 3.5, 18.7**

### Property 9: VLAN List Sort Persistence

*For any* sort operation on the VLAN list, the sort column and direction should be stored in the session and restored when the page is reloaded.

**Validates: Requirements 4.11, 4.12, 12.1, 12.5**

### Property 10: VLAN List Sort Toggle

*For any* sortable column header clicked once, the list should sort ascending; clicked twice, descending; clicked three times, return to default (vlan_id ascending).

**Validates: Requirements 4.7, 4.8, 4.9**

### Property 11: IP Address Numeric Sorting

*For any* IP address list sorted by IP address, the sorting should use numeric comparison (via INET_ATON) rather than string comparison.

**Validates: Requirements 6.7**

### Property 12: Online Status Filter

*For any* IP address list with status filter set to "online", only IP addresses where is_online is true should be displayed.

**Validates: Requirements 5.6**

### Property 13: Offline Status Filter

*For any* IP address list with status filter set to "offline", only IP addresses where is_online is false should be displayed.

**Validates: Requirements 5.7**

### Property 14: DHCP Range Filter

*For any* IP address list with DHCP range filter enabled, only IP addresses where isInDhcpRange() returns true should be displayed.

**Validates: Requirements 5.8**

### Property 15: DNS Name Filter

*For any* IP address list with "Has DNS name" filter enabled, only IP addresses with non-null and non-empty dns_name should be displayed.

**Validates: Requirements 5.9**

### Property 16: Comment Filter

*For any* IP address list with "Has comment" filter enabled, only IP addresses with non-null and non-empty comment should be displayed.

**Validates: Requirements 5.10**

### Property 17: Multiple Filter AND Logic

*For any* IP address list with multiple filters active, only IP addresses matching all filter criteria should be displayed.

**Validates: Requirements 5.11**

### Property 18: Filter State Persistence

*For any* filter operation on a VLAN detail page, the filter selections should be stored in the session and restored when the page is reloaded.

**Validates: Requirements 5.13, 5.14, 12.2, 12.3, 12.4**

### Property 19: Pagination Page Size

*For any* paginated IP address list, exactly 50 IP addresses (or fewer if less than 50 remain) should be displayed per page.

**Validates: Requirements 7.2**

### Property 20: Pagination State Preservation

*For any* pagination navigation, the active filters and sort order should be maintained across page changes.

**Validates: Requirements 7.6**

### Property 21: IP Detail Page Field Display

*For any* IP address detail page, the rendered output should include IP address, DNS name, MAC address, online status, last scan time, ping time, last online time, comment, and VLAN information.

**Validates: Requirements 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 1.10**

### Property 22: IP Detail Edit Permission

*For any* user with module.network.edit permission viewing an IP detail page, the DNS name and comment fields should be editable.

**Validates: Requirements 1.12, 9.1, 9.2**

### Property 23: IP Detail Read-Only Permission

*For any* user with only module.network.view permission viewing an IP detail page, the DNS name and comment fields should be displayed as read-only text.

**Validates: Requirements 1.13, 19.6**

### Property 24: IP Update Preserves Scan Data

*For any* IP address update operation, the scan-related fields (is_online, last_online_at, last_scanned_at, ping_ms, mac_address) should remain unchanged.

**Validates: Requirements 9.5**

### Property 25: Previous IP Navigation

*For any* IP address that is not the first in its VLAN (by numeric IP order), the detail page should provide a working "Previous IP" button that navigates to the previous IP.

**Validates: Requirements 13.1, 13.3, 13.7**

### Property 26: Next IP Navigation

*For any* IP address that is not the last in its VLAN (by numeric IP order), the detail page should provide a working "Next IP" button that navigates to the next IP.

**Validates: Requirements 13.2, 13.4, 13.7**

### Property 27: First IP Previous Button Disabled

*For any* IP address that is the first in its VLAN (by numeric IP order), the "Previous IP" button should be disabled.

**Validates: Requirements 13.5**

### Property 28: Last IP Next Button Disabled

*For any* IP address that is the last in its VLAN (by numeric IP order), the "Next IP" button should be disabled.

**Validates: Requirements 13.6**

### Property 29: MAC Address Formatting Consistency

*For any* MAC address displayed in the UI, it should be formatted in uppercase with colon separators (XX:XX:XX:XX:XX:XX), regardless of how it's stored.

**Validates: Requirements 17.1, 17.2, 17.3, 17.4, 17.10**

### Property 30: MAC Address Format Conversion

*For any* MAC address stored with hyphen separators, the getFormattedMacAddress() method should convert it to colon format.

**Validates: Requirements 17.3, 17.7**

### Property 31: Null MAC Address Display

*For any* IP address with null mac_address, the display should show "Not resolved".

**Validates: Requirements 17.5**

### Property 32: Search Term Highlighting

*For any* search result, all occurrences of the search term should be highlighted with distinct styling.

**Validates: Requirements 18.1, 18.2, 18.3, 18.4**

### Property 33: Search Input Sanitization

*For any* search query, special characters should be escaped to prevent SQL injection and XSS attacks.

**Validates: Requirements 14.3, 14.7, 14.8**

### Property 34: Search Query Length Limit

*For any* search query exceeding 255 characters, the query should be truncated and a warning displayed.

**Validates: Requirements 14.5, 14.6**

### Property 35: DNS Name Validation

*For any* IP address update with a dns_name value, the dns_name should contain only valid DNS characters (alphanumeric, hyphens, dots), otherwise validation should fail.

**Validates: Requirements 9.3**

### Property 36: Permission-Based Route Access

*For any* user without module.network.view permission attempting to access search, IP detail, or VLAN pages, the request should be denied with a 403 Forbidden response.

**Validates: Requirements 19.1, 19.2, 19.4, 19.5**

### Property 37: Permission-Based Edit Access

*For any* user without module.network.edit permission attempting to update IP address information, the request should be denied with a 403 Forbidden response.

**Validates: Requirements 19.3**

### Property 38: Super Admin Universal Access

*For any* user with super-admin role, access should be granted to all network module features regardless of specific module permissions.

**Validates: Requirements 19.10**

### Property 39: Invalid IP Address 404 Error

*For any* request to view an IP address detail page with a non-existent IP address ID, the system should return a 404 Not Found error with a user-friendly message.

**Validates: Requirements 20.1, 20.2**

### Property 40: Scan History Display

*For any* IP address detail page, the last scanned timestamp and last online timestamp should be displayed in human-readable relative format (e.g., "2 hours ago").

**Validates: Requirements 16.1, 16.2, 16.7, 16.8, 16.9**

### Property 41: Never Scanned Display

*For any* IP address that has never been scanned (last_scanned_at is null), the detail page should display "Never scanned".

**Validates: Requirements 16.3**

### Property 42: Never Online Display

*For any* IP address that has never been online (last_online_at is null), the detail page should display "Never online".

**Validates: Requirements 16.4**

### Property 43: Online Ping Time Display

*For any* IP address that is currently online, the detail page should display the ping response time.

**Validates: Requirements 16.5**

### Property 44: Offline Ping Time Omission

*For any* IP address that is currently offline, the detail page should not display ping response time.

**Validates: Requirements 16.6**

### Property 45: Clear Filters Button

*For any* VLAN detail page with active filters, clicking the "Clear Filters" button should remove all filters and clear the filter state from the session.

**Validates: Requirements 12.6, 12.7, 12.8**

### Property 46: Reset Sort Button

*For any* VLAN list or detail page with custom sorting, clicking the "Reset Sort" button should return to default sort order (vlan_id or ip_address ascending).

**Validates: Requirements 12.9, 12.10**

### Property 47: Search Result Grouping

*For any* search results, VLANs should be displayed first, followed by IP addresses.

**Validates: Requirements 8.10**

### Property 48: Empty Search Query Handling

*For any* search query containing only whitespace, the system should treat it as empty and display the standard VLAN list.

**Validates: Requirements 3.13, 14.2**

### Property 49: Filter Result Count Display

*For any* filtered IP address list, the count of filtered results should be displayed.

**Validates: Requirements 5.12**

### Property 50: Active Filter Count Badge

*For any* filter panel with active filters, the panel header should display a badge showing the count of active filters.

**Validates: Requirements 15.2**

## Error Handling

### Validation Errors

**Search Query Validation**:
- Query less than 3 characters: Display message "Please enter at least 3 characters"
- Query exceeds 255 characters: Truncate query and display warning "Search query truncated to 255 characters"
- Query contains only whitespace: Treat as empty, display standard VLAN list

**IP Address Update Validation**:
- Invalid dns_name characters: Return validation error "DNS name contains invalid characters (only alphanumeric, hyphens, and dots allowed)"
- Comment exceeds 1000 characters: Return validation error "Comment must not exceed 1000 characters"

**Filter Validation**:
- Invalid status value: Ignore filter, log warning
- Invalid filter combination: Apply valid filters only, log warning

### Authorization Errors

**Permission Denied**:
- User lacks module.network.view for search: Return 403 Forbidden with message "You do not have permission to search the network module"
- User lacks module.network.view for IP detail: Return 403 Forbidden with message "You do not have permission to view IP address details"
- User lacks module.network.edit for IP update: Return 403 Forbidden with message "You do not have permission to edit IP address information"
- Log all unauthorized access attempts to audit log

### Resource Not Found Errors

**IP Address Not Found**:
- Invalid IP address ID: Return 404 Not Found with message "IP address not found"
- Display link to return to VLAN list
- Log the 404 error with requested ID

**VLAN Not Found**:
- Invalid VLAN ID in navigation: Return 404 Not Found with message "VLAN not found"
- Display link to return to VLAN list

### Database Errors

**Query Errors**:
- Database connection lost during search: Log error, display "Search temporarily unavailable, please try again"
- Query timeout during search: Log error with query details, display "Search timed out, please try a more specific query"
- Transaction deadlock during update: Log warning, retry operation up to 3 times

**Data Integrity Errors**:
- Concurrent update conflict: Log warning, display "This IP address was updated by another user, please refresh and try again"
- Foreign key violation: Should not occur due to proper relationships, but log error if it does

### AJAX Errors

**AJAX Request Failures**:
- Network error during AJAX update: Display error message "Update failed, please check your connection and try again"
- Server error during AJAX update: Display error message "An error occurred, please try again"
- Timeout during AJAX request: Display error message "Request timed out, please try again"
- Restore previous UI state on AJAX failure

**AJAX Response Errors**:
- Invalid JSON response: Log error, display generic error message
- Missing expected fields in response: Log error, display generic error message
- Validation errors in response: Display validation errors next to relevant fields

### Search Performance Errors

**Slow Query Detection**:
- Search query exceeds 2 seconds: Log warning with query details and execution time
- Search query exceeds 5 seconds: Log error, terminate query, display timeout message
- Multiple slow queries from same user: Log warning for potential abuse

**Result Set Too Large**:
- More than 50 VLANs match: Display first 50 with message "More than 50 VLANs found, showing first 50. Please refine your search."
- More than 50 IP addresses match: Display first 50 with message "More than 50 IP addresses found, showing first 50. Please refine your search."

### Session Errors

**Session State Errors**:
- Session expired during filter/sort operation: Clear stored preferences, apply defaults
- Invalid session data format: Clear corrupted session data, log warning
- Session storage full: Log error, continue without storing preferences

## Testing Strategy

### Dual Testing Approach

The Network Module Enhancements will use both unit testing and property-based testing to ensure comprehensive coverage:

**Unit Tests**: Focus on specific examples, edge cases, and integration points
- Specific IP addresses in/out of DHCP ranges
- Edge cases (first/last IP navigation, empty search results, no filters)
- Error conditions (invalid IP IDs, permission denied, database errors)
- Integration with existing Network module (VLAN relationships, audit logging)
- UI rendering (detail pages, search results, filter panels)
- AJAX functionality (inline editing, filter updates)

**Property-Based Tests**: Verify universal properties across all inputs
- Generate random IP addresses and DHCP ranges
- Generate random search queries
- Generate random filter combinations
- Generate random sort orders
- Generate random users with various permission combinations
- Verify properties hold for all generated inputs (minimum 100 iterations per test)

### Property-Based Testing Configuration

**Framework**: Use [Pest PHP](https://pestphp.com/) with [pest-plugin-faker](https://github.com/pestphp/pest-plugin-faker) for property-based testing in Laravel.

**Test Configuration**:
- Minimum 100 iterations per property test
- Each test tagged with: `Feature: network-module-enhancements, Property {number}: {property_text}`
- Use database transactions to isolate tests
- Seed database with test VLANs and IP addresses

**Example Property Test Structure**:

```php
test('Property 1: DHCP Range Membership Calculation', function () {
    // Generate 100 test cases with random IPs and DHCP ranges
    $testCases = collect(range(1, 100))->map(function () {
        $network = fake()->ipv4();
        $dhcpFrom = fake()->ipv4();
        $dhcpTo = fake()->ipv4();
        $testIp = fake()->ipv4();
        
        return compact('network', 'dhcpFrom', 'dhcpTo', 'testIp');
    });
    
    foreach ($testCases as $case) {
        $vlan = Vlan::factory()->create([
            'network_address' => $case['network'],
            'cidr_suffix' => 24,
            'dhcp_from' => $case['dhcpFrom'],
            'dhcp_to' => $case['dhcpTo'],
        ]);
        
        $ipAddress = IpAddress::factory()->create([
            'vlan_id' => $vlan->id,
            'ip_address' => $case['testIp'],
        ]);
        
        // Calculate expected result
        $ipLong = ip2long($case['testIp']);
        $fromLong = ip2long($case['dhcpFrom']);
        $toLong = ip2long($case['dhcpTo']);
        $expected = $ipLong >= $fromLong && $ipLong <= $toLong;
        
        // Verify method returns correct result
        expect($ipAddress->isInDhcpRange())->toBe($expected);
    }
})->group('property-based', 'dhcp-range');
```

### Unit Testing Strategy

**Model Tests**:
- Test IpAddress::isInDhcpRange() with specific cases (in range, out of range, null DHCP)
- Test IpAddress::getFormattedMacAddress() with various formats
- Test IpAddress::getPreviousIpAddress() and getNextIpAddress() with edge cases
- Test IpAddress scopes (inDhcpRange, hasDnsName, hasComment, filterByStatus)
- Test Vlan::searchByTerm() with various query types

**Controller Tests**:
- Test IpAddressController::show() with valid and invalid IDs
- Test IpAddressController::update() with valid and invalid data
- Test SearchController::index() with various queries
- Test VlanController::index() with sort parameters
- Test VlanController::show() with filter parameters
- Test permission checks for all controller methods

**Search Tests**:
- Test search with IP address queries
- Test search with DNS name queries
- Test search with MAC address queries (various formats)
- Test search with VLAN name queries
- Test search with VLAN ID queries
- Test search result limits (50 per type)
- Test search with queries <3 characters
- Test search with empty/whitespace queries

**Filter Tests**:
- Test online status filter
- Test offline status filter
- Test DHCP range filter
- Test has DNS name filter
- Test has comment filter
- Test multiple filters combined (AND logic)
- Test filter persistence in session

**Sort Tests**:
- Test VLAN list sorting by each column
- Test IP address list sorting by each column
- Test sort direction toggle (asc → desc → default)
- Test sort persistence in session
- Test numeric IP address sorting

**Pagination Tests**:
- Test pagination with 50 IPs per page
- Test pagination with filters active
- Test pagination with sort active
- Test pagination navigation (first, prev, next, last)
- Test pagination with result counts

**Navigation Tests**:
- Test previous IP navigation
- Test next IP navigation
- Test first IP (previous disabled)
- Test last IP (next disabled)
- Test navigation with filters active

**Permission Tests**:
- Test search access with and without module.network.view
- Test IP detail access with and without module.network.view
- Test IP update with and without module.network.edit
- Test super-admin access to all features

**AJAX Tests**:
- Test inline IP address update via AJAX
- Test filter update via AJAX
- Test sort update via AJAX
- Test AJAX error handling
- Test AJAX response format

**View Tests** (Laravel Dusk):
- Test IP detail page rendering
- Test search bar and results rendering
- Test filter panel rendering and interaction
- Test sort indicators rendering
- Test DHCP badge rendering
- Test pagination controls rendering
- Test inline editing functionality
- Test permission-based UI element visibility

### Test Coverage Goals

- Minimum 80% code coverage for all new code
- 100% coverage for DHCP range calculation logic
- 100% coverage for search query sanitization
- 100% coverage for permission checks
- All 50 correctness properties implemented as property-based tests
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
