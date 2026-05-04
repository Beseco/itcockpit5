<?php

namespace App\Modules\Network\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Network\Http\Resources\IpAddressResource;
use App\Modules\Network\Models\IpAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IpAddressApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->hasModulePermission('network', 'view') || $request->user()->isSuperAdmin(), 403);

        $query = IpAddress::with('vlan')
            ->when($request->filled('vlan_id'),   fn($q) => $q->where('vlan_id', $request->vlan_id))
            ->when($request->filled('is_online'),  fn($q) => $q->where('is_online', filter_var($request->is_online, FILTER_VALIDATE_BOOLEAN)))
            ->when($request->filled('search'),     fn($q) => $q->where(fn($q2) =>
                $q2->where('ip_address', 'like', '%' . $request->search . '%')
                   ->orWhere('dns_name', 'like', '%' . $request->search . '%')
                   ->orWhere('mac_address', 'like', '%' . $request->search . '%')
            ));

        $perPage = min((int) $request->get('per_page', 100), 500);

        return IpAddressResource::collection($query->orderBy('ip_address')->paginate($perPage));
    }

    public function show(Request $request, IpAddress $ipAddress): IpAddressResource
    {
        abort_unless($request->user()->hasModulePermission('network', 'view') || $request->user()->isSuperAdmin(), 403);

        $ipAddress->load('vlan');
        return new IpAddressResource($ipAddress);
    }

    public function update(Request $request, IpAddress $ipAddress): IpAddressResource
    {
        abort_unless($request->user()->hasModulePermission('network', 'edit') || $request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'dns_name' => ['nullable', 'string', 'max:255'],
            'comment'  => ['nullable', 'string', 'max:500'],
        ]);

        $ipAddress->update($validated);
        $ipAddress->load('vlan');

        return new IpAddressResource($ipAddress);
    }
}
