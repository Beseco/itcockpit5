<?php

namespace App\Modules\Server\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Server\Http\Resources\ServerResource;
use App\Modules\Server\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

class ServerApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->hasModulePermission('server', 'view') || $request->user()->isSuperAdmin(), 403);

        $query = Server::with(['abteilung', 'adminUser'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('type'),   fn($q) => $q->where('type', $request->type))
            ->when($request->filled('abteilung_id'), fn($q) => $q->where('abteilung_id', $request->abteilung_id))
            ->when($request->filled('search'), fn($q) => $q->where(fn($q2) =>
                $q2->where('name', 'like', '%' . $request->search . '%')
                   ->orWhere('dns_hostname', 'like', '%' . $request->search . '%')
                   ->orWhere('ip_address', 'like', '%' . $request->search . '%')
            ));

        $perPage = min((int) $request->get('per_page', 50), 200);

        return ServerResource::collection($query->orderBy('name')->paginate($perPage));
    }

    public function show(Request $request, Server $server): ServerResource
    {
        abort_unless($request->user()->hasModulePermission('server', 'view') || $request->user()->isSuperAdmin(), 403);

        $server->load(['abteilung', 'adminUser']);
        return new ServerResource($server);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasModulePermission('server', 'edit') || $request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'dns_hostname'     => ['nullable', 'string', 'max:255'],
            'ip_address'       => ['nullable', 'string', 'max:45'],
            'status'           => ['nullable', 'string'],
            'type'             => ['nullable', 'string'],
            'operating_system' => ['nullable', 'string', 'max:255'],
            'os_version'       => ['nullable', 'string', 'max:100'],
            'description'      => ['nullable', 'string'],
            'abteilung_id'     => ['nullable', 'integer', 'exists:abteilungen,id'],
            'admin_user_id'    => ['nullable', 'integer', 'exists:users,id'],
            'revision_date'    => ['nullable', 'date'],
            'doc_url'          => ['nullable', 'url', 'max:500'],
        ]);

        $server = Server::create($validated);
        $server->load(['abteilung', 'adminUser']);

        return (new ServerResource($server))->response()->setStatusCode(201);
    }

    public function update(Request $request, Server $server): ServerResource
    {
        abort_unless($request->user()->hasModulePermission('server', 'edit') || $request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name'             => ['sometimes', 'required', 'string', 'max:255'],
            'dns_hostname'     => ['nullable', 'string', 'max:255'],
            'ip_address'       => ['nullable', 'string', 'max:45'],
            'status'           => ['nullable', 'string'],
            'type'             => ['nullable', 'string'],
            'operating_system' => ['nullable', 'string', 'max:255'],
            'os_version'       => ['nullable', 'string', 'max:100'],
            'description'      => ['nullable', 'string'],
            'abteilung_id'     => ['nullable', 'integer', 'exists:abteilungen,id'],
            'admin_user_id'    => ['nullable', 'integer', 'exists:users,id'],
            'revision_date'    => ['nullable', 'date'],
            'doc_url'          => ['nullable', 'url', 'max:500'],
        ]);

        $server->update($validated);
        $server->load(['abteilung', 'adminUser']);

        return new ServerResource($server);
    }

    public function destroy(Request $request, Server $server): JsonResponse
    {
        abort_unless($request->user()->hasModulePermission('server', 'edit') || $request->user()->isSuperAdmin(), 403);

        $server->delete();
        return response()->json(null, 204);
    }
}
