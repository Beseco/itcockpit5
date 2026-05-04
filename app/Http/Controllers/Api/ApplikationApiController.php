<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplikationResource;
use App\Models\Applikation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

class ApplikationApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->hasModulePermission('applikationen', 'view') || $request->user()->isSuperAdmin(), 403);

        $query = Applikation::with(['abteilung', 'adminUser'])
            ->when($request->filled('search'), fn($q) => $q->where(fn($q2) =>
                $q2->where('name', 'like', '%' . $request->search . '%')
                   ->orWhere('hersteller', 'like', '%' . $request->search . '%')
                   ->orWhere('einsatzzweck', 'like', '%' . $request->search . '%')
            ))
            ->when($request->filled('abteilung_id'), fn($q) => $q->where('abteilung_id', $request->abteilung_id));

        return ApplikationResource::collection($query->orderBy('name')->paginate(100));
    }

    public function show(Request $request, Applikation $applikation): ApplikationResource
    {
        abort_unless($request->user()->hasModulePermission('applikationen', 'view') || $request->user()->isSuperAdmin(), 403);

        $applikation->load(['abteilung', 'adminUser']);
        return new ApplikationResource($applikation);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasModulePermission('applikationen', 'edit') || $request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'sg'            => ['nullable', 'string', 'max:50'],
            'einsatzzweck'  => ['nullable', 'string'],
            'hersteller'    => ['nullable', 'string', 'max:255'],
            'baustein'      => ['nullable', 'string'],
            'abteilung_id'  => ['nullable', 'integer', 'exists:abteilungen,id'],
            'admin_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'revision_date' => ['nullable', 'date'],
            'doc_url'       => ['nullable', 'url', 'max:500'],
        ]);

        $applikation = Applikation::create($validated);
        $applikation->load(['abteilung', 'adminUser']);

        return (new ApplikationResource($applikation))->response()->setStatusCode(201);
    }

    public function update(Request $request, Applikation $applikation): ApplikationResource
    {
        abort_unless($request->user()->hasModulePermission('applikationen', 'edit') || $request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name'          => ['sometimes', 'required', 'string', 'max:255'],
            'sg'            => ['nullable', 'string', 'max:50'],
            'einsatzzweck'  => ['nullable', 'string'],
            'hersteller'    => ['nullable', 'string', 'max:255'],
            'baustein'      => ['nullable', 'string'],
            'abteilung_id'  => ['nullable', 'integer', 'exists:abteilungen,id'],
            'admin_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'revision_date' => ['nullable', 'date'],
            'doc_url'       => ['nullable', 'url', 'max:500'],
        ]);

        $applikation->update($validated);
        $applikation->load(['abteilung', 'adminUser']);

        return new ApplikationResource($applikation);
    }

    public function destroy(Request $request, Applikation $applikation): JsonResponse
    {
        abort_unless($request->user()->hasModulePermission('applikationen', 'edit') || $request->user()->isSuperAdmin(), 403);

        $applikation->delete();
        return response()->json(null, 204);
    }
}
