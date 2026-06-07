<?php

namespace App\Modules\AdUsers\Http\Controllers;

use App\Modules\AdUsers\Models\AdUser;
use App\Modules\AdUsers\Models\AdUserGroupChangeLog;
use App\Modules\AdUsers\Models\AdUserSettings;
use App\Modules\AdUsers\Models\OffboardingRecord;
use App\Modules\Onboarding\Models\OnboardingRecord;
use App\Modules\Onboarding\Services\AdProvisioningService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AdUserController extends Controller
{
    public function __construct(
        private AuditLogger $auditLogger,
        private AdProvisioningService $provisioner,
    ) {}

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

        $perPage = in_array((int) $request->get('per_page', 25), [25, 50, 100, 250]) ? (int) $request->get('per_page', 25) : 25;
        $users     = $query->paginate($perPage)->withQueryString();
        $canDelete = Auth::user()->can('adusers.delete');

        return view('adusers::index', compact('users', 'settings', 'search', 'canDelete', 'offboardingSams', 'perPage'));
    }

    public function show(AdUser $user)
    {
        $groups = $this->extractGroups($user->raw_data ?? []);

        // Gruppentypen per LDAP ermitteln (nicht-kritisch, schlägt lautlos fehl)
        try {
            $groupTypes = $this->provisioner->getGroupTypes(array_column($groups, 'dn'));
            $groups = array_map(function ($g) use ($groupTypes) {
                $g['type'] = $groupTypes[strtolower($g['dn'])] ?? 'unknown';
                return $g;
            }, $groups);
        } catch (\Throwable) {
            $groups = array_map(fn($g) => array_merge($g, ['type' => 'unknown']), $groups);
        }

        $onboardingRecords = class_exists(OnboardingRecord::class)
            ? OnboardingRecord::with(['vorlage', 'createdBy'])
                ->where('samaccountname', $user->samaccountname)
                ->latest()
                ->get()
            : collect();

        $groupChangeLogs = AdUserGroupChangeLog::with(['performedBy', 'revertedBy'])
            ->where('samaccountname', $user->samaccountname)
            ->latest()
            ->get();

        return view('adusers::show', compact('user', 'groups', 'onboardingRecords', 'groupChangeLogs'));
    }

    /** AJAX: Benutzersuche für Vergleichs-Picker */
    public function searchJson(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 2) return response()->json([]);

        $users = AdUser::where(function ($query) use ($q) {
            $query->where('samaccountname', 'like', "%{$q}%")
                  ->orWhere('vorname',      'like', "%{$q}%")
                  ->orWhere('nachname',     'like', "%{$q}%")
                  ->orWhere('anzeigename',  'like', "%{$q}%")
                  ->orWhere('email',        'like', "%{$q}%");
        })->limit(10)->get();

        return response()->json($users->map(fn($u) => [
            'id'        => $u->id,
            'name'      => $u->anzeigename_or_name,
            'sam'       => $u->samaccountname,
            'abteilung' => $u->abteilung ?? '–',
        ]));
    }

    /** AJAX: Einzelvergleich zweier Benutzer */
    public function compareUser(AdUser $user, AdUser $target): JsonResponse
    {
        $labels = [
            'abteilung'          => 'Abteilung',
            'organisation'       => 'Organisation',
            'telefon'            => 'Telefon',
            'email'              => 'E-Mail',
            'distinguished_name' => 'OU / Pfad',
        ];

        $rawLabels = [
            'title'                       => 'Position / Titel',
            'description'                 => 'Beschreibung',
            'physicaldeliveryofficename'  => 'Büro',
            'mobile'                      => 'Mobilnummer',
            'manager'                     => 'Manager (DN)',
        ];

        $attributes = [];
        foreach ($labels as $key => $label) {
            $v1 = $user->{$key} ?? '';
            $v2 = $target->{$key} ?? '';
            $attributes[] = [
                'label' => $label,
                'user1' => $v1,
                'user2' => $v2,
                'diff'  => $v1 !== $v2,
            ];
        }

        $raw1 = $user->raw_data ?? [];
        $raw2 = $target->raw_data ?? [];
        foreach ($rawLabels as $key => $label) {
            $v1 = $raw1[$key][0] ?? '';
            $v2 = $raw2[$key][0] ?? '';
            if ($v1 || $v2) {
                $attributes[] = [
                    'label' => $label,
                    'user1' => $v1,
                    'user2' => $v2,
                    'diff'  => $v1 !== $v2,
                ];
            }
        }

        $groups1 = collect($this->extractGroups($raw1))->pluck('dn')->sort()->values()->toArray();
        $groups2 = collect($this->extractGroups($raw2))->pluck('dn')->sort()->values()->toArray();

        return response()->json([
            'target'      => ['id' => $target->id, 'name' => $target->anzeigename_or_name, 'sam' => $target->samaccountname],
            'attributes'  => $attributes,
            'groups' => [
                'only_user1' => array_values(array_diff($groups1, $groups2)),
                'only_user2' => array_values(array_diff($groups2, $groups1)),
                'common'     => array_values(array_intersect($groups1, $groups2)),
            ],
        ]);
    }

    /** AJAX: Gruppenvergleich mit allen Benutzern in derselben OU */
    public function compareOu(AdUser $user): JsonResponse
    {
        $dn = $user->distinguished_name;
        if (!$dn) {
            return response()->json(['error' => 'Kein Distinguished Name vorhanden.'], 422);
        }

        // OU extrahieren: alles nach dem ersten Komma
        $ouDn = substr($dn, (int)strpos($dn, ',') + 1);

        // Alle anderen Benutzer in derselben OU
        $ouUsers = AdUser::where('distinguished_name', 'like', '%,' . $ouDn)
            ->where('id', '!=', $user->id)
            ->where('ad_vorhanden', true)
            ->get();

        if ($ouUsers->isEmpty()) {
            return response()->json(['ou' => $ouDn, 'users_count' => 0, 'groups_analysis' => []]);
        }

        $myGroups  = collect($this->extractGroups($user->raw_data ?? []))->pluck('dn')->toArray();

        // Alle Gruppen aller OU-Mitglieder sammeln
        $groupCounts = []; // dn => [name, count]
        foreach ($ouUsers as $ou) {
            foreach ($this->extractGroups($ou->raw_data ?? []) as $g) {
                if (!isset($groupCounts[$g['dn']])) {
                    $groupCounts[$g['dn']] = ['name' => $g['name'], 'dn' => $g['dn'], 'count' => 0];
                }
                $groupCounts[$g['dn']]['count']++;
            }
        }

        $total = $ouUsers->count();
        $analysis = [];
        foreach ($groupCounts as $dn => $info) {
            $iHave = in_array($dn, $myGroups);
            $pct   = round($info['count'] / $total * 100);
            $analysis[] = [
                'name'    => $info['name'],
                'dn'      => $dn,
                'i_have'  => $iHave,
                'count'   => $info['count'],
                'total'   => $total,
                'pct'     => $pct,
                'notable' => (!$iHave && $pct >= 80) || ($iHave && $pct <= 20),
            ];
        }

        // Nur dieser Benutzer: Gruppen die kein anderer in der OU hat
        $myOnlyGroups = array_filter($myGroups, fn($d) => !isset($groupCounts[$d]));
        foreach ($myOnlyGroups as $dn) {
            preg_match('/^CN=([^,]+)/i', $dn, $m);
            $analysis[] = [
                'name'    => $m[1] ?? $dn,
                'dn'      => $dn,
                'i_have'  => true,
                'count'   => 0,
                'total'   => $total,
                'pct'     => 0,
                'notable' => true,
            ];
        }

        usort($analysis, fn($a, $b) => $b['pct'] <=> $a['pct'] ?: strcasecmp($a['name'], $b['name']));

        // OU-Benutzer mit Details + Gruppen-DNs (für Frontend-Filterung)
        $ouUsersData = $ouUsers->map(function ($u) {
            $raw      = $u->raw_data ?? [];
            $memberOf = $raw['memberof'] ?? [];
            $cnt      = (int)($memberOf['count'] ?? 0);
            $groupDns = [];
            for ($i = 0; $i < $cnt; $i++) {
                if ($g = $memberOf[$i] ?? '') $groupDns[] = strtolower($g);
            }
            return [
                'id'          => $u->id,
                'name'        => $u->anzeigename_or_name,
                'sam'         => $u->samaccountname,
                'email'       => $u->email,
                'abteilung'   => $u->abteilung,
                'title'       => $raw['title'][0] ?? null,
                'description' => $raw['description'][0] ?? null,
                'groups'      => $groupDns,
                'groups_count'=> $cnt,
            ];
        })->sortBy('name')->values()->toArray();

        // Aktueller Benutzer ebenfalls aufbereiten (für Mitgliedschaftsprüfung)
        $myRaw      = $user->raw_data ?? [];
        $myMemberOf = $myRaw['memberof'] ?? [];
        $myCnt      = (int)($myMemberOf['count'] ?? 0);
        $myGroupDns = [];
        for ($i = 0; $i < $myCnt; $i++) {
            if ($g = $myMemberOf[$i] ?? '') $myGroupDns[] = strtolower($g);
        }
        $currentUser = [
            'id'          => $user->id,
            'name'        => $user->anzeigename_or_name,
            'sam'         => $user->samaccountname,
            'email'       => $user->email,
            'abteilung'   => $user->abteilung,
            'title'       => $myRaw['title'][0] ?? null,
            'description' => $myRaw['description'][0] ?? null,
            'groups'      => $myGroupDns,
            'groups_count'=> $myCnt,
            'is_current'  => true,
        ];

        return response()->json([
            'ou'              => $ouDn,
            'users_count'     => $total,
            'groups_analysis' => $analysis,
            'ou_users'        => $ouUsersData,
            'current_user'    => $currentUser,
        ]);
    }

    // ─── private ─────────────────────────────────────────────────────────────

    private function extractGroups(array $rawData): array
    {
        $memberOf = $rawData['memberof'] ?? [];
        $groups   = [];
        $count    = (int)($memberOf['count'] ?? 0);
        for ($i = 0; $i < $count; $i++) {
            $dn = $memberOf[$i] ?? '';
            if (!$dn) continue;
            preg_match('/^CN=([^,]+)/i', $dn, $match);
            $groups[] = ['name' => $match[1] ?? $dn, 'dn' => $dn];
        }
        usort($groups, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        return $groups;
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
