<?php

namespace App\Http\Controllers;

use App\Mail\AbteilungRevisionMail;
use App\Models\Abteilung;
use App\Modules\AdUsers\Models\AdUser;
use App\Modules\AdUsers\Services\LdapConnectionService;
use App\Modules\Onboarding\Models\OnboardingVorlage;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AbteilungController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index()
    {
        $this->authorize('abteilungen.view');

        $abteilungen = Abteilung::with([
            'children.children.children.vorgesetzter',
            'children.children.children.stellvertreter',
            'children.children.vorgesetzter',
            'children.children.stellvertreter',
            'children.vorgesetzter',
            'children.stellvertreter',
            'vorgesetzter',
            'stellvertreter',
            'parent',
        ])->roots()->get();

        return view('abteilungen.index', compact('abteilungen'));
    }

    /** Detailansicht einer OE mit Benutzern und Soll/Ist-Abgleich gegen die Vorlage. */
    public function show(Abteilung $abteilung)
    {
        $this->authorize('abteilungen.view');

        $abteilung->load(['vorgesetzter', 'stellvertreter', 'parent']);

        // Vorlage (1:1 zur OE)
        $vorlage = OnboardingVorlage::with(['gruppen', 'vorgesetzter'])
            ->where('abteilung_id', $abteilung->id)
            ->first();

        $templateGroups = $vorlage
            ? $vorlage->gruppen->map(fn($g) => ['dn' => $g->ad_group_dn, 'name' => $g->ad_group_name])->values()
            : collect();
        $templateSupervisorDn   = $vorlage?->vorgesetzter?->distinguished_name;
        $templateSupervisorName = $vorlage?->vorgesetzter?->anzeigename_or_name;

        // Benutzer in dieser OU (direkte Mitglieder)
        $users = collect();
        if ($abteilung->ad_path) {
            $users = AdUser::where('distinguished_name', 'like', '%,' . $abteilung->ad_path)
                ->where('ad_vorhanden', true)
                ->orderBy('nachname')->orderBy('vorname')
                ->get();
        }

        $tplSupLower = $templateSupervisorDn ? strtolower(trim($templateSupervisorDn)) : null;

        $userInfos = $users->map(function (AdUser $u) use ($templateGroups, $tplSupLower) {
            $raw = $u->raw_data ?? [];

            // Gruppen-DNs des Benutzers (lowercase)
            $memberOf = $raw['memberof'] ?? [];
            $cnt = (int) ($memberOf['count'] ?? 0);
            $userGroupDns = [];
            for ($i = 0; $i < $cnt; $i++) {
                if ($g = $memberOf[$i] ?? '') {
                    $userGroupDns[] = strtolower(trim($g));
                }
            }

            // Fehlende Vorlagen-Gruppen
            $missing = $templateGroups
                ->filter(fn($g) => !in_array(strtolower(trim($g['dn'])), $userGroupDns, true))
                ->values();

            // Vorgesetzter-Abweichung (der OU-Leiter selbst zählt nicht als Abweichung)
            $managerDn  = $raw['manager'][0] ?? null;
            $istLeiter  = $tplSupLower && strtolower(trim($u->distinguished_name ?? '')) === $tplSupLower;
            $supMismatch = false;
            if ($tplSupLower && !$istLeiter) {
                $supMismatch = strtolower(trim((string) $managerDn)) !== $tplSupLower;
            }

            return [
                'user'                => $u,
                'missing_groups'      => $missing,
                'manager_name'        => $managerDn ? $this->cnAusDn($managerDn) : null,
                'supervisor_mismatch' => $supMismatch,
                'has_issues'          => $missing->isNotEmpty() || $supMismatch,
            ];
        });

        $issueCount = $userInfos->where('has_issues', true)->count();

        return view('abteilungen.show', compact(
            'abteilung', 'vorlage', 'templateGroups',
            'templateSupervisorName', 'templateSupervisorDn',
            'userInfos', 'issueCount'
        ));
    }

    /** Extrahiert den CN aus einem Distinguished Name. */
    private function cnAusDn(string $dn): string
    {
        if (preg_match('/^CN=([^,]+)/i', $dn, $m)) {
            return str_replace('\\,', ',', $m[1]);
        }
        return $dn;
    }

    public function create()
    {
        $this->authorize('abteilungen.create');

        $allAbteilungen = Abteilung::orderBy('sort_order')->orderBy('name')->get();
        $adUsers        = AdUser::aktiv()->orderBy('anzeigename')->get();

        return view('abteilungen.create', compact('allAbteilungen', 'adUsers'));
    }

    public function store(Request $request)
    {
        $this->authorize('abteilungen.create');

        $validated = $this->validateAbteilung($request);

        $abteilung = Abteilung::create($validated);

        $this->auditLogger->log('Abteilung', 'Abteilung erstellt', [
            'id'   => $abteilung->id,
            'name' => $abteilung->name,
        ]);

        return redirect()->route('abteilungen.index')
                         ->with('success', 'Abteilung erfolgreich gespeichert.');
    }

    public function edit(Abteilung $abteilung)
    {
        $this->authorize('abteilungen.edit');

        $excludeIds     = $abteilung->allChildren()->pluck('id')->push($abteilung->id)->toArray();
        $allAbteilungen = Abteilung::whereNotIn('id', $excludeIds)
                                   ->orderBy('sort_order')
                                   ->orderBy('name')
                                   ->get();
        $adUsers        = AdUser::aktiv()->orderBy('anzeigename')->get();

        return view('abteilungen.edit', compact('abteilung', 'allAbteilungen', 'adUsers'));
    }

    public function update(Request $request, Abteilung $abteilung)
    {
        $this->authorize('abteilungen.edit');

        $validated = $this->validateAbteilung($request);

        $abteilung->update($validated);

        $this->auditLogger->log('Abteilung', 'Abteilung aktualisiert', [
            'id'   => $abteilung->id,
            'name' => $abteilung->name,
        ]);

        return redirect()->route('abteilungen.index')
                         ->with('success', 'Abteilung erfolgreich aktualisiert.');
    }

    public function destroy(Abteilung $abteilung)
    {
        $this->authorize('abteilungen.delete');

        $data = ['id' => $abteilung->id, 'name' => $abteilung->name];
        $abteilung->delete();

        $this->auditLogger->log('Abteilung', 'Abteilung gelöscht', $data);

        return redirect()->route('abteilungen.index')
                         ->with('success', 'Abteilung gelöscht.');
    }

    public function sendRevisionMailTest(Abteilung $abteilung)
    {
        $this->authorize('abteilungen.edit');

        $token = $abteilung->ensureRevisionToken();
        $abteilung->revision_notified_at = now();
        $abteilung->save();

        $recipient = Auth::user()->email;

        try {
            Mail::to($recipient)->send(new AbteilungRevisionMail($abteilung, Auth::user()->name));
        } catch (\Exception $e) {
            return redirect()->route('abteilungen.edit', $abteilung)
                ->with('error', 'Mailversand fehlgeschlagen: ' . $e->getMessage());
        }

        return redirect()->route('abteilungen.edit', $abteilung)
            ->with('success', "Test-Revisionsmail an {$recipient} gesendet.");
    }

    /**
     * AD-Mitarbeiterzahlen für alle OEs mit hinterlegtem AD-Pfad aktualisieren.
     */
    public function refreshAdCounts()
    {
        $this->authorize('abteilungen.edit');

        $all         = Abteilung::orderBy('sort_order')->orderBy('name')->get();
        $withPath    = $all->filter(fn($a) => !empty($a->ad_path));
        $missingPath = $all->filter(fn($a) => empty($a->ad_path));

        $updated      = 0;
        $failedOes    = [];

        if ($withPath->isNotEmpty()) {
            try {
                $ldap = new LdapConnectionService();

                foreach ($withPath as $abt) {
                    try {
                        $users = $ldap->searchWithBaseDn(
                            $abt->ad_path,
                            '(&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))',
                            ['samaccountname']
                        );
                        $abt->ad_member_count            = $users->count();
                        $abt->ad_member_count_updated_at = now();
                        $abt->saveQuietly();
                        $updated++;
                    } catch (\Exception $e) {
                        Log::warning("AD-Zählung fehlgeschlagen für OE {$abt->id} ({$abt->name}): " . $e->getMessage());
                        $failedOes[] = $abt->anzeigename;
                    }
                }
            } catch (\Exception $e) {
                return back()->with('error', 'AD-Verbindung fehlgeschlagen: ' . $e->getMessage());
            }
        }

        return back()
            ->with('ad_refresh_updated', $updated)
            ->with('ad_refresh_missing', $missingPath->pluck('anzeigename')->all())
            ->with('ad_refresh_failed', $failedOes);
    }

    private function validateAbteilung(Request $request): array
    {
        return $request->validate([
            'name'                       => ['required', 'string', 'max:255'],
            'kurzzeichen'                => ['nullable', 'string', 'max:20'],
            'kuerzel'                    => ['nullable', 'string', 'max:20'],
            'ad_path'                    => ['nullable', 'string', 'max:500'],
            'parent_id'                  => ['nullable', 'integer', 'exists:abteilungen,id'],
            'sort_order'                 => ['nullable', 'integer', 'min:0'],
            'vorgesetzter_ad_user_id'    => ['nullable', 'integer', 'exists:adusers,id'],
            'stellvertreter_ad_user_id'  => ['nullable', 'integer', 'exists:adusers,id'],
            'revision_date'              => ['nullable', 'date'],
        ]);
    }
}
