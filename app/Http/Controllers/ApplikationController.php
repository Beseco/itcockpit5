<?php

namespace App\Http\Controllers;

use App\Models\Abteilung;
use App\Models\Applikation;
use App\Models\Dienstleister;
use App\Models\User;
use App\Modules\AdUsers\Models\AdUser;
use App\Modules\Server\Models\Server;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplikationController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        $this->authorize('applikationen.view');

        $sessionKey = 'applikationen_filters';

        // Reset: Session löschen und zur sauberen URL weiterleiten
        if ($request->has('reset')) {
            session()->forget($sessionKey);
            return redirect()->route('applikationen.index');
        }

        // Frischer Aufruf ohne explizit gesetzten Filter: Session laden und weiterleiten
        if (!$request->has('filter_applied') && session()->has($sessionKey)) {
            $saved = array_filter(session($sessionKey), fn($v) => $v !== '' && $v !== null && $v !== false);
            return redirect()->route('applikationen.index',
                array_merge($saved, ['filter_applied' => '1'])
            );
        }

        $allowed = ['name', 'sg', 'hersteller', 'baustein', 'verantwortlich_sg'];
        $sort    = in_array($request->get('sort'), $allowed) ? $request->get('sort') : 'name';
        $order   = $request->get('order') === 'DESC' ? 'DESC' : 'ASC';
        $search  = $request->get('search', '');

        $filterAbteilungId        = $request->get('filter_abteilung_id', '');
        $filterBaustein           = $request->get('filter_baustein', '');
        $filterAdminUserId        = $request->get('filter_admin_user_id', '');
        $filterOhneVerantwortlich = $request->boolean('filter_ohne_verantwortlich');
        $filterConfidentiality    = $request->get('filter_confidentiality', '');
        $filterIntegrity          = $request->get('filter_integrity', '');
        $filterAvailability       = $request->get('filter_availability', '');
        $filterOffeneRevision     = $request->boolean('filter_offene_revision');

        // Aktive Filter in Session speichern (nur bei expliziter Filterübergabe)
        if ($request->has('filter_applied')) {
            session([$sessionKey => [
                'search'                    => $search,
                'filter_abteilung_id'       => $filterAbteilungId,
                'filter_baustein'           => $filterBaustein,
                'filter_admin_user_id'      => $filterAdminUserId,
                'filter_ohne_verantwortlich'=> $filterOhneVerantwortlich ? '1' : '',
                'filter_confidentiality'    => $filterConfidentiality,
                'filter_integrity'          => $filterIntegrity,
                'filter_availability'       => $filterAvailability,
                'filter_offene_revision'    => $filterOffeneRevision ? '1' : '',
            ]]);
        }

        $query = Applikation::with(['adminUser', 'verantwortlichAdUser', 'abteilung'])->orderBy($sort, $order);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('einsatzzweck', 'LIKE', "%{$search}%")
                  ->orWhere('sg', 'LIKE', "%{$search}%")
                  ->orWhere('hersteller', 'LIKE', "%{$search}%");
            });
        }

        if ($filterAbteilungId !== '')     $query->where('abteilung_id', $filterAbteilungId);
        if ($filterBaustein !== '')        $query->where('baustein', $filterBaustein);
        if ($filterAdminUserId === 'none') {
            $query->whereNull('admin_user_id');
        } elseif ($filterAdminUserId !== '') {
            $query->where('admin_user_id', $filterAdminUserId);
        }
        if ($filterOhneVerantwortlich) $query->whereNull('verantwortlich_ad_user_id');
        if ($filterConfidentiality !== '') $query->where('confidentiality', $filterConfidentiality);
        if ($filterIntegrity !== '')       $query->where('integrity', $filterIntegrity);
        if ($filterAvailability !== '')    $query->where('availability', $filterAvailability);
        if ($filterOffeneRevision)         $query->whereNotNull('revision_date')->where('revision_date', '<=', now()->toDateString());

        $apps = $query->paginate(25)->withQueryString();

        $abteilungen         = Abteilung::orderBy('sort_order')->orderBy('name')->get();
        $adminUsers          = User::whereIn('id', Applikation::whereNotNull('admin_user_id')->pluck('admin_user_id'))
                                   ->orderBy('name')->get();
        $verantwortlichUsers = AdUser::whereIn('id', Applikation::whereNotNull('verantwortlich_ad_user_id')->pluck('verantwortlich_ad_user_id'))
                                     ->orderBy('anzeigename')->get();

        return view('applikationen.index', compact(
            'apps', 'sort', 'order', 'search',
            'filterAbteilungId', 'filterBaustein', 'filterAdminUserId',
            'filterOhneVerantwortlich',
            'filterConfidentiality', 'filterIntegrity', 'filterAvailability',
            'filterOffeneRevision',
            'abteilungen', 'adminUsers', 'verantwortlichUsers'
        ));
    }

    public function create()
    {
        $this->authorize('applikationen.create');

        $vendors     = Dienstleister::where('status', '!=', 'gesperrt')->orderBy('firmenname')->get();
        $users       = User::where('is_active', true)->orderBy('name')->get();
        $adUsers     = AdUser::aktiv()->orderBy('anzeigename')->get();
        $abteilungen = Abteilung::orderBy('sort_order')->orderBy('name')->get();
        $servers     = class_exists(Server::class) ? Server::orderBy('name')->get() : collect();
        return view('applikationen.create', [
            'app'          => null,
            'bausteine'    => Applikation::BAUSTEINE,
            'schutzbedarf' => Applikation::SCHUTZBEDARF,
            'vendors'      => $vendors,
            'users'        => $users,
            'adUsers'      => $adUsers,
            'abteilungen'  => $abteilungen,
            'servers'      => $servers,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('applikationen.create');

        $validated = $this->validateApp($request);
        $serverIds = $request->input('server_ids', []);
        $validated['updated_by'] = Auth::user()->name;

        $app = Applikation::create($validated);
        $app->servers()->sync($serverIds);

        $this->auditLogger->log('Applikation', 'Applikation erstellt', [
            'id'   => $app->id,
            'name' => $app->name,
        ]);

        return redirect()->route('applikationen.index')->with('success', 'Applikation erfolgreich gespeichert.');
    }

    public function edit(Applikation $applikation)
    {
        $this->authorize('applikationen.edit');

        $vendors     = Dienstleister::where('status', '!=', 'gesperrt')->orderBy('firmenname')->get();
        $users       = User::where('is_active', true)->orderBy('name')->get();
        $adUsers     = AdUser::aktiv()->orderBy('anzeigename')->get();
        $abteilungen = Abteilung::orderBy('sort_order')->orderBy('name')->get();
        $servers     = class_exists(Server::class) ? Server::orderBy('name')->get() : collect();
        return view('applikationen.edit', [
            'app'          => $applikation,
            'bausteine'    => Applikation::BAUSTEINE,
            'schutzbedarf' => Applikation::SCHUTZBEDARF,
            'vendors'      => $vendors,
            'users'        => $users,
            'adUsers'      => $adUsers,
            'abteilungen'  => $abteilungen,
            'servers'      => $servers,
        ]);
    }

    public function update(Request $request, Applikation $applikation)
    {
        $this->authorize('applikationen.edit');

        $validated   = $this->validateApp($request);
        $serverIds   = $request->input('server_ids', []);
        $validated['updated_by'] = Auth::user()->name;

        $applikation->update($validated);
        $applikation->servers()->sync($serverIds);

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
            'sg'               => ['sometimes', 'nullable', 'string', 'max:255'],
            'einsatzzweck'     => ['nullable', 'string'],
            'confidentiality'  => ['required', 'in:A,B,C'],
            'integrity'        => ['required', 'in:A,B,C'],
            'availability'     => ['required', 'in:A,B,C'],
            'baustein'         => ['nullable', 'string', 'max:50'],
            'abteilung_id'             => ['nullable', 'integer', 'exists:abteilungen,id'],
            'verantwortlich_sg'        => ['sometimes', 'nullable', 'string', 'max:255'],
            'verantwortlich_ad_user_id'=> ['nullable', 'integer', 'exists:adusers,id'],
            'admin_user_id'            => ['nullable', 'integer', 'exists:users,id'],
            'ansprechpartner'  => ['nullable', 'string', 'max:255'],
            'hersteller'       => ['nullable', 'string', 'max:255'],
            'revision_date'    => ['nullable', 'date'],
            'doc_url'          => ['nullable', 'string', 'max:500'],
        ]);
    }
}
