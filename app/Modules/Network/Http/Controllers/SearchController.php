<?php

namespace App\Modules\Network\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Network\Models\IpAddress;
use App\Modules\Network\Models\Vlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Display the search page with results.
     *
     * Searches across VLANs and IP addresses based on the query parameter.
     * Validates search query and returns results limited to 50 per type.
     */
    public function index(Request $request): View
    {
        // Permission check is handled by middleware
        // Get and validate search query
        $query = $this->validateAndSanitizeQuery($request->input('q', ''));

        $vlans = collect();
        $ipAddresses = collect();
        $validationMessage = null;

        // Only search if query is valid
        if ($query === null) {
            $validationMessage = 'Please enter at least 3 characters';
        } elseif ($query !== '') {
            $vlans = $this->searchVlans($query);
            $ipAddresses = $this->searchIpAddresses($query);
        }

        return view('network::search.index', compact(
            'query',
            'vlans',
            'ipAddresses',
            'validationMessage'
        ));
    }

    /**
     * AJAX endpoint for live search.
     *
     * Returns JSON response with search results including VLAN context
     * for IP addresses and highlighted matched terms.
     */
    public function search(Request $request): JsonResponse
    {
        // Permission check is handled by middleware
        // Get and validate search query
        $query = $this->validateAndSanitizeQuery($request->input('q', ''));

        // Validate query length
        if ($query === null) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter at least 3 characters',
            ], 422);
        }

        if ($query === '') {
            return response()->json([
                'success' => true,
                'vlans' => [],
                'ipAddresses' => [],
            ]);
        }

        // Perform search
        $vlans = $this->searchVlans($query);
        $ipAddresses = $this->searchIpAddresses($query);

        return response()->json([
            'success' => true,
            'vlans' => $vlans->map(function ($vlan) {
                return [
                    'id' => $vlan->id,
                    'vlan_id' => $vlan->vlan_id,
                    'vlan_name' => $vlan->vlan_name,
                    'network_address' => $vlan->network_address,
                    'online_count' => $vlan->online_count,
                    'total_ip_count' => $vlan->total_ip_count,
                    'url' => route('network.vlans.show', $vlan),
                ];
            }),
            'ipAddresses' => $ipAddresses->map(function ($ip) {
                return [
                    'id' => $ip->id,
                    'ip_address' => $ip->ip_address,
                    'dns_name' => $ip->dns_name,
                    'mac_address' => $ip->getFormattedMacAddress(),
                    'is_online' => $ip->is_online,
                    'status_text' => $ip->status_text,
                    'vlan_id' => $ip->vlan->vlan_id,
                    'vlan_name' => $ip->vlan->vlan_name,
                    'url' => route('network.ip-addresses.show', $ip),
                    'vlan_url' => route('network.vlans.show', $ip->vlan),
                ];
            }),
        ]);
    }

    /**
     * Search VLANs by term.
     *
     * Searches VLAN ID (exact match if numeric), VLAN name, and network address.
     * Returns up to 50 results with online count and total IP count.
     */
    protected function searchVlans(string $query): Collection
    {
        return Vlan::searchByTerm($query)
            ->withCount([
                'ipAddresses as online_count' => function ($q) {
                    $q->where('is_online', true);
                },
                'ipAddresses as total_ip_count',
            ])
            ->limit(50)
            ->get();
    }

    /**
     * Search IP addresses by term.
     *
     * Searches IP address, DNS name, and MAC address (normalized).
     * Returns up to 50 results with VLAN relationship eager loaded.
     */
    protected function searchIpAddresses(string $query): Collection
    {
        return IpAddress::searchByTerm($query)
            ->with('vlan')
            ->limit(50)
            ->get();
    }

    /**
     * Validate and sanitize search query.
     *
     * Trims whitespace, validates minimum length (3 characters),
     * truncates to maximum length (255 characters), and escapes
     * special characters to prevent SQL injection and XSS.
     *
     * @return string|null Returns sanitized query, empty string for whitespace-only, or null if too short
     */
    protected function validateAndSanitizeQuery(?string $query): ?string
    {
        // Handle null input
        if ($query === null) {
            return '';
        }
        
        // Trim leading and trailing whitespace
        $query = trim($query);

        // Treat whitespace-only queries as empty
        if ($query === '') {
            return '';
        }

        // Validate minimum length
        if (strlen($query) < 3) {
            return null;
        }

        // Truncate to maximum length
        if (strlen($query) > 255) {
            $query = substr($query, 0, 255);
        }

        // Sanitize for SQL and XSS
        // Laravel's query builder handles SQL injection prevention via parameter binding
        // We just need to escape HTML entities for XSS prevention when displaying
        $query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');

        return $query;
    }
}
