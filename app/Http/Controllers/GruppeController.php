<?php

namespace App\Http\Controllers;

use App\Models\Gruppe;
use App\Services\AuditLogger;
use App\Services\GruppeService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class GruppeController extends Controller
{
    public function __construct(
        private AuditLogger $auditLogger,
        private GruppeService $gruppeService,
    ) {}

    public function index()
    {
        $this->authorize('base.gruppen.view');

        $gruppen = Gruppe::with(['children.children', 'roles', 'users'])
            ->roots()
            ->get();

        return view('gruppen.index', compact('gruppen'));
    }

    public function create(Request $request)
    {
        $this->authorize('base.gruppen.create');

        $allGruppen = Gruppe::orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        $selectedParent = $request->filled('parent_id') ? Gruppe::find($request->parent_id) : null;

        return view('gruppen.create', compact('allGruppen', 'roles', 'selectedParent'));
    }

    public function store(Request $request)
    {
        $this->authorize('base.gruppen.create');

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'parent_id' => 'nullable|exists:gruppen,id',
            'sort_order'=> 'nullable|integer',
            'role_ids'  => 'nullable|array',
            'role_ids.*'=> 'exists:roles,id',
        ]);

        $gruppe = Gruppe::create([
            'name'       => $validated['name'],
            'parent_id'  => $validated['parent_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        if (!empty($validated['role_ids'])) {
            $gruppe->roles()->sync($validated['role_ids']);
        }

        $this->auditLogger->log('gruppen', 'create', ['message' => "Gruppe '{$gruppe->name}' angelegt"]);

        return redirect()->route('gruppen.index')
            ->with('success', "Gruppe \"{$gruppe->name}\" wurde angelegt.");
    }

    public function edit(Gruppe $gruppe)
    {
        $this->authorize('base.gruppen.edit');

        $gruppe->load(['roles', 'users', 'children']);
        $allGruppen = Gruppe::where('id', '!=', $gruppe->id)->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        $allUsers = \App\Models\User::orderBy('name')->get();

        $selectedParent = null;

        return view('gruppen.edit', compact('gruppe', 'allGruppen', 'roles', 'allUsers', 'selectedParent'));
    }

    public function update(Request $request, Gruppe $gruppe)
    {
        $this->authorize('base.gruppen.edit');

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'parent_id' => 'nullable|exists:gruppen,id',
            'sort_order'=> 'nullable|integer',
            'role_ids'  => 'nullable|array',
            'role_ids.*'=> 'exists:roles,id',
            'user_ids'  => 'nullable|array',
            'user_ids.*'=> 'exists:users,id',
        ]);

        // Prevent setting parent to self or own descendant
        if (!empty($validated['parent_id'])) {
            $descendants = $gruppe->allChildren()->pluck('id')->push($gruppe->id);
            if ($descendants->contains((int) $validated['parent_id'])) {
                return back()->withErrors(['parent_id' => 'Eine Gruppe kann nicht ihrer eigenen Untergruppe untergeordnet werden.']);
            }
        }

        $gruppe->update([
            'name'       => $validated['name'],
            'parent_id'  => $validated['parent_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $rolesChanged = false;
        $newRoleIds = $validated['role_ids'] ?? [];
        $oldRoleIds  = $gruppe->roles->pluck('id')->toArray();
        if (array_diff($newRoleIds, $oldRoleIds) || array_diff($oldRoleIds, $newRoleIds)) {
            $rolesChanged = true;
        }

        $gruppe->roles()->sync($newRoleIds);
        $gruppe->users()->sync($validated['user_ids'] ?? []);

        if ($rolesChanged) {
            $gruppe->load('users');
            $this->gruppeService->syncMemberRoles($gruppe);
        }

        $this->auditLogger->log('gruppen', 'update', ['message' => "Gruppe '{$gruppe->name}' aktualisiert"]);

        return redirect()->route('gruppen.index')
            ->with('success', "Gruppe \"{$gruppe->name}\" wurde gespeichert.");
    }

    public function destroy(Gruppe $gruppe)
    {
        $this->authorize('base.gruppen.delete');

        if ($gruppe->children()->exists()) {
            return back()->with('error', 'Diese Gruppe hat noch Untergruppen und kann nicht gelöscht werden.');
        }

        if ($gruppe->users()->exists()) {
            return back()->with('error', 'Diese Gruppe hat noch Mitglieder und kann nicht gelöscht werden.');
        }

        if ($gruppe->stellen()->exists()) {
            $count = $gruppe->stellen()->count();
            return back()->with('error', "Diese Gruppe ist noch {$count} Stelle(n) zugewiesen und kann nicht gelöscht werden.");
        }

        $name = $gruppe->name;
        $gruppe->roles()->detach();
        $gruppe->delete();

        $this->auditLogger->log('gruppen', 'delete', ['message' => "Gruppe '{$name}' gelöscht"]);

        return redirect()->route('gruppen.index')
            ->with('success', "Gruppe \"{$name}\" wurde gelöscht.");
    }
}
