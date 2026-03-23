<?php

namespace App\Http\Controllers;

use App\Models\Aufgabe;
use App\Models\AufgabeZuweisung;
use App\Models\Gruppe;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class AufgabeController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        $this->authorize('base.aufgaben.view');

        $search    = $request->input('search', '');
        $gruppeId  = $request->input('gruppe_id', '');
        $adminId   = $request->input('admin_id', '');
        $nurEigene = $request->boolean('nur_eigene');
        $sortDir   = $request->input('sort', 'asc') === 'desc' ? 'desc' : 'asc';
        $isFiltered = $search !== '' || $gruppeId !== '' || $adminId !== '' || $nurEigene;

        $gruppen = Gruppe::orderBy('name')->get();
        $adminUserIds = AufgabeZuweisung::whereNotNull('admin_user_id')->pluck('admin_user_id')->unique();
        $admins = User::whereIn('id', $adminUserIds)->orderBy('name')->get();

        if ($isFiltered) {
            $aufgaben = Aufgabe::with(['zuweisungen.gruppe', 'zuweisungen.admin', 'zuweisungen.stellvertreter', 'parent'])
                ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
                ->when($gruppeId, fn($q) => $q->whereHas('zuweisungen', fn($q2) => $q2->where('gruppe_id', $gruppeId)))
                ->when($adminId, fn($q) => $q->whereHas('zuweisungen', fn($q2) => $q2->where('admin_user_id', $adminId)))
                ->when($nurEigene, fn($q) => $q->whereHas('zuweisungen', function ($q2) {
                    $q2->where('admin_user_id', auth()->id())
                       ->orWhere('stellvertreter_user_id', auth()->id());
                }))
                ->orderBy('name', $sortDir)
                ->get();
        } else {
            $aufgaben = Aufgabe::with([
                'children.children.zuweisungen.gruppe',
                'children.children.zuweisungen.admin',
                'children.children.zuweisungen.stellvertreter',
                'children.zuweisungen.gruppe',
                'children.zuweisungen.admin',
                'children.zuweisungen.stellvertreter',
                'zuweisungen.gruppe',
                'zuweisungen.admin',
                'zuweisungen.stellvertreter',
            ])->roots()->get();
        }

        return view('aufgaben.index', compact(
            'aufgaben', 'gruppen', 'admins',
            'isFiltered', 'search', 'gruppeId', 'adminId', 'nurEigene', 'sortDir'
        ));
    }

    public function create(Request $request)
    {
        $this->authorize('base.aufgaben.create');

        $alleAufgaben = $this->aufgabenAlsHierarchie();
        $gruppen = Gruppe::orderBy('name')->get();
        $users   = User::where('is_active', true)->orderBy('name')->get();
        $selectedParent = $request->filled('parent_id') ? Aufgabe::find($request->parent_id) : null;

        return view('aufgaben.create', compact('alleAufgaben', 'gruppen', 'users', 'selectedParent'));
    }

    public function store(Request $request)
    {
        $this->authorize('base.aufgaben.create');

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'parent_id'  => 'nullable|exists:aufgaben,id',
            'sort_order' => 'nullable|integer',
            'zuweisungen'                       => 'nullable|array',
            'zuweisungen.*.gruppe_id'           => 'nullable|exists:gruppen,id',
            'zuweisungen.*.admin_user_id'       => 'nullable|exists:users,id',
            'zuweisungen.*.stellvertreter_user_id' => 'nullable|exists:users,id',
        ]);

        $aufgabe = Aufgabe::create([
            'name'       => $validated['name'],
            'parent_id'  => $validated['parent_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        foreach (($validated['zuweisungen'] ?? []) as $zuw) {
            if (!empty($zuw['gruppe_id']) || !empty($zuw['admin_user_id'])) {
                AufgabeZuweisung::create([
                    'aufgabe_id'               => $aufgabe->id,
                    'gruppe_id'                => $zuw['gruppe_id'] ?? null,
                    'admin_user_id'            => $zuw['admin_user_id'] ?? null,
                    'stellvertreter_user_id'   => $zuw['stellvertreter_user_id'] ?? null,
                ]);
            }
        }

        $this->auditLogger->log('aufgaben', 'create', ['name' => $aufgabe->name]);

        return redirect()->route('aufgaben.index')
            ->with('success', "Aufgabe \"{$aufgabe->name}\" wurde angelegt.");
    }

    public function edit(Aufgabe $aufgabe)
    {
        $this->authorize('base.aufgaben.edit');

        $aufgabe->load(['zuweisungen.gruppe', 'zuweisungen.admin', 'zuweisungen.stellvertreter']);
        $excludeIds = array_merge([$aufgabe->id], $aufgabe->allChildren()->pluck('id')->all());
        $alleAufgaben = $this->aufgabenAlsHierarchie($excludeIds);
        $gruppen = Gruppe::orderBy('name')->get();
        $users   = User::where('is_active', true)->orderBy('name')->get();

        $selectedParent = null;

        return view('aufgaben.edit', compact('aufgabe', 'alleAufgaben', 'gruppen', 'users', 'selectedParent'));
    }

    public function update(Request $request, Aufgabe $aufgabe)
    {
        $this->authorize('base.aufgaben.edit');

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'parent_id'  => 'nullable|exists:aufgaben,id',
            'sort_order' => 'nullable|integer',
            'zuweisungen'                       => 'nullable|array',
            'zuweisungen.*.gruppe_id'           => 'nullable|exists:gruppen,id',
            'zuweisungen.*.admin_user_id'       => 'nullable|exists:users,id',
            'zuweisungen.*.stellvertreter_user_id' => 'nullable|exists:users,id',
        ]);

        $aufgabe->update([
            'name'       => $validated['name'],
            'parent_id'  => $validated['parent_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        // Sync zuweisungen
        $aufgabe->zuweisungen()->delete();
        foreach (($validated['zuweisungen'] ?? []) as $zuw) {
            if (!empty($zuw['gruppe_id']) || !empty($zuw['admin_user_id'])) {
                AufgabeZuweisung::create([
                    'aufgabe_id'               => $aufgabe->id,
                    'gruppe_id'                => $zuw['gruppe_id'] ?? null,
                    'admin_user_id'            => $zuw['admin_user_id'] ?? null,
                    'stellvertreter_user_id'   => $zuw['stellvertreter_user_id'] ?? null,
                ]);
            }
        }

        $this->auditLogger->log('aufgaben', 'update', ['name' => $aufgabe->name]);

        return redirect()->route('aufgaben.index')
            ->with('success', "Aufgabe \"{$aufgabe->name}\" wurde gespeichert.");
    }

    private function aufgabenAlsHierarchie(array $excludeIds = []): array
    {
        $roots = Aufgabe::with('children.children.children.children')
            ->whereNull('parent_id')
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        $result = [];
        $flatten = function ($items, int $depth) use (&$flatten, $excludeIds, &$result) {
            foreach ($items as $a) {
                if (in_array($a->id, $excludeIds)) {
                    continue;
                }
                $result[] = ['id' => $a->id, 'name' => $a->name, 'depth' => $depth];
                if ($a->children->isNotEmpty()) {
                    $flatten($a->children, $depth + 1);
                }
            }
        };
        $flatten($roots, 0);

        return $result;
    }

    public function destroy(Aufgabe $aufgabe)
    {
        $this->authorize('base.aufgaben.delete');

        if ($aufgabe->children()->exists()) {
            return back()->with('error', 'Diese Aufgabe hat noch Unteraufgaben und kann nicht gelöscht werden.');
        }

        $name = $aufgabe->name;
        $aufgabe->zuweisungen()->delete();
        $aufgabe->delete();

        $this->auditLogger->log('aufgaben', 'delete', ['name' => $name]);

        return redirect()->route('aufgaben.index')
            ->with('success', "Aufgabe \"{$name}\" wurde gelöscht.");
    }
}
