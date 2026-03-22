<?php

namespace App\Modules\Network\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Network\Http\Requests\UpdateIpAddressRequest;
use App\Modules\Network\Models\IpAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IpAddressController extends Controller
{
    /**
     * Display the specified IP address detail page.
     *
     * Shows comprehensive information about a single IP address including
     * VLAN context, scan history, and navigation to adjacent IPs.
     */
    public function show(IpAddress $ipAddress): View
    {
        // Permission check is handled by middleware
        // Load IP address with VLAN relationship
        $ipAddress->load('vlan');

        // Calculate previous and next IP addresses
        $previousIp = $ipAddress->getPreviousIpAddress();
        $nextIp = $ipAddress->getNextIpAddress();

        // Determine DHCP range membership
        $isInDhcpRange = $ipAddress->isInDhcpRange();

        return view('network::ip-addresses.show', compact(
            'ipAddress',
            'previousIp',
            'nextIp',
            'isInDhcpRange'
        ));
    }

    /**
     * Update the specified IP address record.
     *
     * This method handles both AJAX and form submissions for updating
     * IP address records, allowing users to update dns_name and comment
     * fields while preserving automatically collected scan data.
     */
    public function update(UpdateIpAddressRequest $request, IpAddress $ipAddress): JsonResponse|RedirectResponse
    {
        // Get validated data
        $validated = $request->validated();

        // Update only the user-editable fields
        // Preserve scan-related fields (is_online, last_online_at, last_scanned_at, ping_ms, mac_address)
        $ipAddress->update($validated);

        // Log the action
        $auditLogger = app(\App\Services\AuditLogger::class);
        $auditLogger->logModuleAction('Network', 'IP address updated', [
            'ip_address_id' => $ipAddress->id,
            'ip_address' => $ipAddress->ip_address,
            'vlan_id' => $ipAddress->vlan_id,
            'changes' => $validated,
        ]);

        // Return JSON for AJAX requests, redirect for form submissions
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'IP address updated successfully.',
                'data' => [
                    'id' => $ipAddress->id,
                    'dns_name' => $ipAddress->dns_name,
                    'comment' => $ipAddress->comment,
                ],
            ]);
        }

        return redirect()
            ->route('network.ip-addresses.show', $ipAddress)
            ->with('success', 'IP address updated successfully.');
    }
}
