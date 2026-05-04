<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->isSuperAdmin() || $request->user()->hasModulePermission('base', 'users.view'), 403);

        $query = User::with('roles')
            ->when($request->filled('search'), fn($q) => $q->where(fn($q2) =>
                $q2->where('name', 'like', '%' . $request->search . '%')
                   ->orWhere('email', 'like', '%' . $request->search . '%')
            ))
            ->when($request->filled('active'), fn($q) =>
                $request->active === '1' ? $q->where('is_active', true) : $q->where('is_active', false)
            );

        return UserResource::collection($query->orderBy('name')->paginate(100));
    }

    public function show(Request $request, User $user): UserResource
    {
        abort_unless($request->user()->isSuperAdmin() || $request->user()->hasModulePermission('base', 'users.view'), 403);

        $user->load('roles');
        return new UserResource($user);
    }
}
