<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $this->authorize('base.roles.view');

        $roles = Role::withCount(['permissions', 'users'])->orderBy('name')->get();

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $this->authorize('base.roles.create');

        $permissionsByModule  = $this->permissionsByModule();
        $moduleDisplayNames   = $this->moduleDisplayNames();
        $rolePermissions      = collect();

        return view('roles.create', compact('permissionsByModule', 'moduleDisplayNames', 'rolePermissions'));
    }

    public function store(Request $request)
    {
        $this->authorize('base.roles.create');

        $request->validate([
            'name'          => ['required', 'string', 'max:100', 'unique:roles,name'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);
        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('roles.index')->with('success', "Rolle \"{$role->name}\" wurde angelegt.");
    }

    public function edit(Role $role)
    {
        $this->authorize('base.roles.edit');

        $permissionsByModule  = $this->permissionsByModule();
        $moduleDisplayNames   = $this->moduleDisplayNames();
        $rolePermissions      = $role->permissions->pluck('name');

        return view('roles.edit', compact('role', 'permissionsByModule', 'moduleDisplayNames', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorize('base.roles.edit');

        $isSuperAdmin = $role->name === 'Superadministrator';

        $rules = [
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];

        if (!$isSuperAdmin) {
            $rules['name'] = ['required', 'string', 'max:100', "unique:roles,name,{$role->id}"];
        }

        $validated = $request->validate($rules);

        if (!$isSuperAdmin) {
            $role->update(['name' => $validated['name']]);
        }

        if (!$isSuperAdmin) {
            $role->syncPermissions($request->permissions ?? []);
        }

        return redirect()->route('roles.index')->with('success', "Rolle \"{$role->name}\" wurde aktualisiert.");
    }

    public function destroy(Role $role)
    {
        $this->authorize('base.roles.delete');

        if ($role->name === 'Superadministrator') {
            abort(403, 'Die Superadministrator-Rolle kann nicht gelöscht werden.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', "Rolle \"{$role->name}\" hat noch {$role->users()->count()} zugewiesene Benutzer und kann nicht gelöscht werden.");
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', "Rolle \"{$role->name}\" wurde gelöscht.");
    }

    private function moduleDisplayNames(): Collection
    {
        return Module::all()->pluck('display_name', 'name');
    }

    private function permissionsByModule(): Collection
    {
        $columns = ['view', 'create', 'edit', 'delete', 'manage'];

        return Permission::all()
            ->groupBy(fn($p) => explode('.', $p->name)[0])
            ->map(fn($perms, $module) => collect($columns)
                ->mapWithKeys(fn($action) => [
                    $action => $perms->first(fn($p) => $p->name === "{$module}.{$action}"),
                ])
                ->filter()
            )
            ->sortKeys();
    }
}
