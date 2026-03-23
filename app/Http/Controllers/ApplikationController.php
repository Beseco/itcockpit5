<?php

namespace App\Http\Controllers;

use App\Models\Applikation;
use App\Models\Dienstleister;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplikationController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        $this->authorize('applikationen.view');

        $allowed = ['name', 'sg', 'hersteller', 'baustein', 'verantwortlich_sg'];
        $sort    = in_array($request->get('sort'), $allowed) ? $request->get('sort') : 'name';
        $order   = $request->get('order') === 'DESC' ? 'DESC' : 'ASC';
        $search  = $request->get('search', '');

        $query = Applikation::with('adminUser')->orderBy($sort, $order);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('einsatzzweck', 'LIKE', "%{$search}%")
                  ->orWhere('sg', 'LIKE', "%{$search}%")
                  ->orWhere('hersteller', 'LIKE', "%{$search}%");
            });
        }

        $apps = $query->paginate(25)->withQueryString();

        return view('applikationen.index', compact('apps', 'sort', 'order', 'search'));
    }

    public function create()
    {
        $this->authorize('applikationen.create');

        $vendors = Dienstleister::where('status', '!=', 'gesperrt')->orderBy('firmenname')->get();
        $users   = User::where('is_active', true)->orderBy('name')->get();
        return view('applikationen.create', [
            'app'          => null,
            'bausteine'    => Applikation::BAUSTEINE,
            'schutzbedarf' => Applikation::SCHUTZBEDARF,
            'vendors'      => $vendors,
            'users'        => $users,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('applikationen.create');

        $validated = $this->validateApp($request);
        $validated['updated_by'] = Auth::user()->name;

        $app = Applikation::create($validated);

        $this->auditLogger->log('Applikation', 'Applikation erstellt', [
            'id'   => $app->id,
            'name' => $app->name,
        ]);

        return redirect()->route('applikationen.index')->with('success', 'Applikation erfolgreich gespeichert.');
    }

    public function edit(Applikation $applikation)
    {
        $this->authorize('applikationen.edit');

        $vendors = Dienstleister::where('status', '!=', 'gesperrt')->orderBy('firmenname')->get();
        $users   = User::where('is_active', true)->orderBy('name')->get();
        return view('applikationen.edit', [
            'app'          => $applikation,
            'bausteine'    => Applikation::BAUSTEINE,
            'schutzbedarf' => Applikation::SCHUTZBEDARF,
            'vendors'      => $vendors,
            'users'        => $users,
        ]);
    }

    public function update(Request $request, Applikation $applikation)
    {
        $this->authorize('applikationen.edit');

        $validated = $this->validateApp($request);
        $validated['updated_by'] = Auth::user()->name;

        $applikation->update($validated);

        $this->auditLogger->log('Applikation', 'Applikation aktualisiert', [
            'id'   => $applikation->id,
            'name' => $applikation->name,
        ]);

        return redirect()->route('applikationen.index')->with('success', 'Applikation erfolgreich aktualisiert.');
    }

    public function destroy(Applikation $applikation)
    {
        $this->authorize('applikationen.delete');

        $data = ['id' => $applikation->id, 'name' => $applikation->name];
        $applikation->delete();

        $this->auditLogger->log('Applikation', 'Applikation gelöscht', $data);

        return redirect()->route('applikationen.index')->with('success', 'Applikation gelöscht.');
    }

    private function validateApp(Request $request): array
    {
        return $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'sg'               => ['nullable', 'string', 'max:255'],
            'einsatzzweck'     => ['nullable', 'string'],
            'confidentiality'  => ['required', 'in:A,B,C'],
            'integrity'        => ['required', 'in:A,B,C'],
            'availability'     => ['required', 'in:A,B,C'],
            'baustein'         => ['nullable', 'string', 'max:50'],
            'verantwortlich_sg'=> ['nullable', 'string', 'max:255'],
            'admin_user_id'    => ['nullable', 'integer', 'exists:users,id'],
            'ansprechpartner'  => ['nullable', 'string', 'max:255'],
            'hersteller'       => ['nullable', 'string', 'max:255'],
            'revision_date'    => ['nullable', 'date'],
            'doc_url'          => ['nullable', 'string', 'max:500'],
        ]);
    }
}
