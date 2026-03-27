<?php

namespace App\Modules\AdUsers\Http\Controllers;

use App\Modules\AdUsers\Models\AdUser;
use App\Modules\AdUsers\Models\AdUserSettings;
use App\Modules\AdUsers\Models\OffboardingRecord;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AdUserController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        $settings = AdUserSettings::getSingleton();
        $query    = AdUser::query()->orderBy('nachname')->orderBy('vorname');

        // Suche
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('samaccountname', 'like', "%{$search}%")
                  ->orWhere('vorname',       'like', "%{$search}%")
                  ->orWhere('nachname',      'like', "%{$search}%")
                  ->orWhere('anzeigename',   'like', "%{$search}%")
                  ->orWhere('email',         'like', "%{$search}%")
                  ->orWhere('organisation',  'like', "%{$search}%")
                  ->orWhere('abteilung',     'like', "%{$search}%")
                  ->orWhere('telefon',       'like', "%{$search}%");
            });
        }

        // Aktive Offboarding-SAMAccountNames für Filter + Badge
        $offboardingSams = OffboardingRecord::whereNotIn('status', ['abgeschlossen'])
            ->pluck('status', 'samaccountname');  // [sam => status]

        // Filter
        if ($request->get('status') === 'aktiv') {
            $query->where('ad_aktiv', true)->where('ad_vorhanden', true);
        } elseif ($request->get('status') === 'deaktiviert') {
            $query->where('ad_aktiv', false);
        } elseif ($request->get('status') === 'offboarding') {
            $query->whereIn('samaccountname', $offboardingSams->keys());
        }

        if ($request->get('vorhanden') === 'nein') {
            $query->where('ad_vorhanden', false);
        } elseif ($request->get('vorhanden') === 'ja') {
            $query->where('ad_vorhanden', true);
        }

        if ($inaktivSeit = $request->get('inaktiv_seit')) {
            $query->where('letzter_import_at', '<', now()->subDays((int) $inaktivSeit));
        }

        $users     = $query->paginate(100)->withQueryString();
        $canDelete = Auth::user()->can('adusers.delete');

        return view('adusers::index', compact('users', 'settings', 'search', 'canDelete', 'offboardingSams'));
    }

    public function show(AdUser $user)
    {
        return view('adusers::show', compact('user'));
    }

    public function destroy(AdUser $user)
    {
        Auth::user()->can('adusers.delete') || abort(403);

        $settings = AdUserSettings::getSingleton();
        if ($user->ad_vorhanden && !$user->istVeraltet($settings->max_inactive_days)) {
            return back()->with('error', 'Benutzer ist noch im AD vorhanden und darf nicht gelöscht werden.');
        }

        $this->auditLogger->logModuleAction('AdUsers', 'delete', ['sam' => $user->samaccountname]);
        $user->delete();

        return redirect()->route('adusers.index')->with('success', 'Benutzer gelöscht.');
    }

    public function bulkDestroy(Request $request)
    {
        Auth::user()->can('adusers.delete') || abort(403);

        $ids      = $request->input('ids', []);
        $settings = AdUserSettings::getSingleton();
        $deleted  = 0;

        foreach (AdUser::whereIn('id', $ids)->get() as $user) {
            if ($user->ad_vorhanden && !$user->istVeraltet($settings->max_inactive_days)) {
                continue; // Schutz: Aktive AD-User nicht löschen
            }
            $user->delete();
            $deleted++;
        }

        return redirect()->route('adusers.index')
            ->with('success', "{$deleted} Benutzer gelöscht.");
    }
}
