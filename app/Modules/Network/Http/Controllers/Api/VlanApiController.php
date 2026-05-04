<?php

namespace App\Modules\Network\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Network\Http\Resources\VlanResource;
use App\Modules\Network\Models\Vlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VlanApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->hasModulePermission('network', 'view') || $request->user()->isSuperAdmin(), 403);

        $query = Vlan::withCount('ipAddresses')
            ->when($request->filled('search'), fn($q) => $q->where(fn($q2) =>
                $q2->where('vlan_name', 'like', '%' . $request->search . '%')
                   ->orWhere('network_address', 'like', '%' . $request->search . '%')
                   ->orWhere('description', 'like', '%' . $request->search . '%')
            ));

        return VlanResource::collection($query->orderBy('vlan_id')->paginate(100));
    }

    public function show(Request $request, Vlan $vlan): VlanResource
    {
        abort_unless($request->user()->hasModulePermission('network', 'view') || $request->user()->isSuperAdmin(), 403);

        $vlan->load('ipAddresses');
        return new VlanResource($vlan);
    }
}
