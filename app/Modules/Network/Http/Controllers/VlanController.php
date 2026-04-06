<?php

namespace App\Modules\Network\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Network\Models\Vlan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VlanController extends Controller
{
    /**
     * Display a listing of all VLANs.
     */
    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        // Check for search query and redirect to SearchController
        $query = $request->input('q');
        if ($query !== null && trim($query) !== '') {
            return redirect()->route('network.search', ['q' => $query]);
        }

        // Get sort parameters from request or session
        $sortColumn = $request->input('sort', session('network.vlan_list.sort_column', 'vlan_id'));
        $sortDirection = $request->input('direction', session('network.vlan_list.sort_direction', 'asc'));

        // Validate sort column
        $allowedColumns = ['vlan_id', 'vlan_name', 'network_address', 'online_count'];
        if (!in_array($sortColumn, $allowedColumns)) {
            $sortColumn = 'vlan_id';
        }

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        // Store sort preferences in session
        session()->put('network.vlan_list.sort_column', $sortColumn);
        session()->put('network.vlan_list.sort_direction', $sortDirection);

        // Build query with sorting
        $query = Vlan::query();

        // Apply sorting
        if ($sortColumn === 'online_count') {
            // Use subquery for online count
            $query->withCount([
                'ipAddresses as online_count' => function ($q) {
                    $q->where('is_online', true);
                },
            ])->orderBy('online_count', $sortDirection);
        } else {
            $query->orderBy($sortColumn, $sortDirection);
        }

        $vlans = $query->get();

        return view('network::vlans.index', compact('vlans', 'sortColumn', 'sortDirection'));
    }

    /**
     * Display the specified VLAN with all related data.
     */
    public function show(Vlan $vlan, Request $request): View
    {
        // Load comments relationship for the VLAN
        $vlan->load(['comments.user']);

        return view('network::vlans.show', compact('vlan'));
    }

    /**
     * Show the form for creating a new VLAN.
     */
    public function create(): View
    {
        return view('network::vlans.create');
    }

    /**
     * AJAX: Check if a network address/CIDR already exists or overlaps with existing networks.
     */
    public function checkNetwork(Request $request): \Illuminate\Http\JsonResponse
    {
        $network = trim($request->query('network', ''));
        $cidr    = (int) $request->query('cidr', 0);

        if (!filter_var($network, FILTER_VALIDATE_IP) || $cidr < 0 || $cidr > 32) {
            return response()->json(['error' => 'Ungültige Eingabe'], 422);
        }

        $mask      = $cidr === 0 ? 0 : ((0xFFFFFFFF << (32 - $cidr)) & 0xFFFFFFFF);
        $newStart  = ip2long($network) & $mask;
        $newEnd    = $newStart | (~$mask & 0xFFFFFFFF);

        $exact     = [];
        $overlaps  = [];

        Vlan::all(['id', 'vlan_id', 'vlan_name', 'network_address', 'cidr_suffix'])
            ->each(function (Vlan $v) use ($network, $cidr, $newStart, $newEnd, &$exact, &$overlaps) {
                $eMask  = $v->cidr_suffix === 0 ? 0 : ((0xFFFFFFFF << (32 - $v->cidr_suffix)) & 0xFFFFFFFF);
                $eStart = ip2long($v->network_address) & $eMask;
                $eEnd   = $eStart | (~$eMask & 0xFFFFFFFF);

                if ($v->network_address === $network && $v->cidr_suffix === $cidr) {
                    $exact[] = ['vlan_id' => $v->vlan_id, 'vlan_name' => $v->vlan_name, 'subnet' => "{$v->network_address}/{$v->cidr_suffix}"];
                } elseif ($newStart <= $eEnd && $eStart <= $newEnd) {
                    $overlaps[] = ['vlan_id' => $v->vlan_id, 'vlan_name' => $v->vlan_name, 'subnet' => "{$v->network_address}/{$v->cidr_suffix}"];
                }
            });

        return response()->json([
            'exact'    => $exact,
            'overlaps' => $overlaps,
        ]);
    }

    /**
     * AJAX: Check if a VLAN ID is already taken.
     */
    public function checkVlanId(Request $request): \Illuminate\Http\JsonResponse
    {
        $id = (int) $request->query('id');
        $exists = Vlan::where('vlan_id', $id)->exists();
        return response()->json(['taken' => $exists]);
    }

    /**
     * AJAX: Find free VLAN IDs in a given range.
     */
    public function freeVlanIds(Request $request): \Illuminate\Http\JsonResponse
    {
        $from = max(1, (int) $request->query('from', 1));
        $to   = min(4094, (int) $request->query('to', 4094));

        if ($from > $to) {
            return response()->json(['error' => 'Ungültiger Bereich'], 422);
        }

        $taken = Vlan::whereBetween('vlan_id', [$from, $to])
            ->pluck('vlan_id')
            ->flip();

        $free = [];
        $next5 = [];
        for ($i = $from; $i <= $to; $i++) {
            if (!$taken->has($i)) {
                $free[] = $i;
                if (count($next5) < 5) {
                    $next5[] = $i;
                }
            }
        }

        return response()->json([
            'total_in_range' => $to - $from + 1,
            'free_count'     => count($free),
            'next5'          => $next5,
        ]);
    }

    /**
     * Store a newly created VLAN in storage.
     */
    public function store(\App\Modules\Network\Http\Requests\StoreVlanRequest $request): \Illuminate\Http\RedirectResponse
    {
        // Create the VLAN
        $vlan = Vlan::create($request->validated());

        // Generate IP addresses for the VLAN
        $ipGenerator = app(\App\Modules\Network\Services\IpGeneratorService::class);
        $ipCount = $ipGenerator->generateIpAddresses($vlan);

        // Log the action
        $auditLogger = app(\App\Services\AuditLogger::class);
        $auditLogger->logModuleAction('Network', 'VLAN created', [
            'vlan_id' => $vlan->id,
            'vlan_name' => $vlan->vlan_name,
            'network_address' => $vlan->network_address,
            'cidr_suffix' => $vlan->cidr_suffix,
            'ip_count' => $ipCount,
        ]);

        return redirect()
            ->route('network.vlans.show', $vlan)
            ->with('success', "VLAN '{$vlan->vlan_name}' created successfully with {$ipCount} IP addresses.");
    }

    /**
     * Show standalone IP address list for a VLAN.
     */
    public function ips(Vlan $vlan, Request $request): View
    {
        $search = $request->input('q', '');
        $sortColumn = $request->input('sort', 'ip_address');
        $sortDirection = $request->input('direction', 'asc');

        $allowedColumns = ['ip_address', 'dns_name', 'is_online', 'last_scanned_at', 'ping_ms'];
        if (!in_array($sortColumn, $allowedColumns)) $sortColumn = 'ip_address';
        if (!in_array($sortDirection, ['asc', 'desc'])) $sortDirection = 'asc';

        $query = $vlan->ipAddresses();

        // Text search
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('ip_address', 'LIKE', "%{$search}%")
                  ->orWhere('dns_name', 'LIKE', "%{$search}%")
                  ->orWhere('mac_address', 'LIKE', "%{$search}%")
                  ->orWhere('comment', 'LIKE', "%{$search}%");
            });
        }

        // Status filter
        if ($request->input('status')) {
            $query->filterByStatus($request->input('status'));
        }

        // DHCP filter
        if ($request->input('dhcp')) {
            if ($vlan->dhcp_from && $vlan->dhcp_to) {
                $query->whereRaw('INET_ATON(ip_address) >= INET_ATON(?)', [$vlan->dhcp_from])
                      ->whereRaw('INET_ATON(ip_address) <= INET_ATON(?)', [$vlan->dhcp_to]);
            }
        }

        // Has DNS filter
        if ($request->input('has_dns')) {
            $query->hasDnsName();
        }

        // Has comment filter
        if ($request->input('has_comment')) {
            $query->hasComment();
        }

        // Sorting
        if ($sortColumn === 'ip_address') {
            $query->orderByRaw("INET_ATON(ip_address) {$sortDirection}");
        } else {
            $query->orderBy($sortColumn, $sortDirection);
        }

        $ipAddresses = $query->paginate(100)->withQueryString();

        return view('network::vlans.ips', compact('vlan', 'ipAddresses', 'search', 'sortColumn', 'sortDirection'));
    }

    /**
     * AJAX search for IP addresses within a VLAN.
     */
    public function ipsSearch(Vlan $vlan, Request $request): \Illuminate\Http\JsonResponse
    {
        $search = trim($request->input('q', ''));

        $query = $vlan->ipAddresses();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('ip_address', 'LIKE', "%{$search}%")
                  ->orWhere('dns_name', 'LIKE', "%{$search}%")
                  ->orWhere('mac_address', 'LIKE', "%{$search}%")
                  ->orWhere('comment', 'LIKE', "%{$search}%");
            });
        }

        $query->orderByRaw("INET_ATON(ip_address) asc");
        $results = $query->limit(200)->get(['id', 'ip_address', 'dns_name', 'mac_address', 'is_online', 'last_scanned_at', 'ping_ms', 'comment']);

        return response()->json($results);
    }

    /**
     * Show the form for editing the specified VLAN.
     */
    public function edit(Vlan $vlan): View
    {
        return view('network::vlans.edit', compact('vlan'));
    }

    /**
     * Update the specified VLAN in storage.
     */
    public function update(\App\Modules\Network\Http\Requests\UpdateVlanRequest $request, Vlan $vlan): \Illuminate\Http\RedirectResponse
    {
        $oldData = $vlan->only(['network_address', 'cidr_suffix']);

        // Update the VLAN
        $vlan->update($request->validated());

        // Check if network parameters changed
        $networkChanged = $oldData['network_address'] !== $vlan->network_address
                       || $oldData['cidr_suffix'] !== $vlan->cidr_suffix;

        $ipCount = 0;
        if ($networkChanged) {
            // Delete existing IP addresses
            $vlan->ipAddresses()->delete();

            // Regenerate IP addresses
            $ipGenerator = app(\App\Modules\Network\Services\IpGeneratorService::class);
            $ipCount = $ipGenerator->generateIpAddresses($vlan);
        }

        // Log the action
        $auditLogger = app(\App\Services\AuditLogger::class);
        $payload = [
            'vlan_id' => $vlan->id,
            'vlan_name' => $vlan->vlan_name,
            'changes' => $request->validated(),
        ];

        if ($networkChanged) {
            $payload['ip_regenerated'] = true;
            $payload['ip_count'] = $ipCount;
        }

        $auditLogger->logModuleAction('Network', 'VLAN updated', $payload);

        $message = "VLAN '{$vlan->vlan_name}' updated successfully.";
        if ($networkChanged) {
            $message .= " IP addresses regenerated ({$ipCount} addresses).";
        }

        return redirect()
            ->route('network.vlans.show', $vlan)
            ->with('success', $message);
    }

    /**
     * Remove the specified VLAN from storage.
     */
    public function destroy(Vlan $vlan): \Illuminate\Http\RedirectResponse
    {
        $vlanName = $vlan->vlan_name;
        $vlanId = $vlan->id;

        // Log the action before deletion
        $auditLogger = app(\App\Services\AuditLogger::class);
        $auditLogger->logModuleAction('Network', 'VLAN deleted', [
            'vlan_id' => $vlanId,
            'vlan_name' => $vlanName,
            'network_address' => $vlan->network_address,
            'cidr_suffix' => $vlan->cidr_suffix,
        ]);

        // Delete the VLAN (cascade deletes IP addresses and comments)
        $vlan->delete();

        return redirect()
            ->route('network.index')
            ->with('success', "VLAN '{$vlanName}' deleted successfully.");
    }
}

