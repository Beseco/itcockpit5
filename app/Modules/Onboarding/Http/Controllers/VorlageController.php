<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Models\Abteilung;
use App\Modules\AdUsers\Models\AdUser;
use App\Modules\Onboarding\Models\OnboardingVorlage;
use App\Modules\Onboarding\Models\OnboardingVorlageGruppe;
use App\Modules\Onboarding\Services\AdProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class VorlageController extends Controller
{
    public function index()
    {
        // Baum wie bei den Organisationseinheiten aufbauen (Roots + Kinder).
        $abteilungen = Abteilung::with([
            'children.children.children.children',
            'children.children.children',
            'children.children',
            'children',
        ])->roots()->get();

        // Vorlagen je OE für schnellen Zugriff im Baum.
        $vorlagenByAbteilung = OnboardingVorlage::with('gruppen')->get()->keyBy('abteilung_id');

        // Eventuelle Altbestände ohne OE
        $standalone = OnboardingVorlage::whereNull('abteilung_id')->with('gruppen')->orderBy('name')->get();

        return view('onboarding::vorlagen.index', compact('abteilungen', 'vorlagenByAbteilung', 'standalone'));
    }

    public function edit(OnboardingVorlage $vorlage)
    {
        $abteilungen = Abteilung::orderBy('name')->get();
        $adUsers     = AdUser::aktiv()->orderBy('nachname')->get();

        return view('onboarding::vorlagen.edit', compact('vorlage', 'abteilungen', 'adUsers'));
    }

    public function update(Request $request, OnboardingVorlage $vorlage): RedirectResponse
    {
        $validated = $this->validateVorlage($request);

        $vorlage->update($validated);
        $this->syncGruppen($vorlage, $request->input('gruppen', []));

        return redirect()->route('onboarding.vorlagen.index')
            ->with('success', 'Vorlage "' . $vorlage->name . '" wurde gespeichert.');
    }

    /**
     * AJAX: Liest die Benutzer in der OU der gewählten Abteilung aus und schlägt
     * für die Standardfelder (Adresse, Firma, Büro, Rufnummer-Präfix) den jeweils
     * häufigsten Wert vor.
     */
    public function ouSuggestions(Request $request, \App\Modules\AdUsers\Services\LdapConnectionService $ldap)
    {
        $abteilung = Abteilung::find($request->integer('abteilung_id'));
        if (!$abteilung || empty($abteilung->ad_path)) {
            return response()->json(['count' => 0, 'suggestions' => (object) []]);
        }

        $attrs = [
            'streetaddress', 'postalcode', 'l', 'company', 'department',
            'physicaldeliveryofficename', 'telephonenumber', 'facsimiletelephonenumber',
            'memberof', 'manager',
        ];

        try {
            $users = $ldap->searchWithBaseDn(
                $abteilung->ad_path,
                '(&(objectClass=user)(objectCategory=person))',
                $attrs,
                true, // nur direkte Benutzer dieser OU, keine Unter-OUs
            );
        } catch (\Throwable $e) {
            return response()->json(['count' => 0, 'suggestions' => (object) [], 'error' => $e->getMessage()]);
        }

        // Direkte Attribut-Übernahme (häufigster Wert)
        $direct = [
            'strasse'      => 'streetaddress',
            'plz'          => 'postalcode',
            'ort'          => 'l',
            'firma'        => 'company',
            'abteilung_ad' => 'department',
            'buero'        => 'physicaldeliveryofficename',
        ];

        $suggestions = [];
        foreach ($direct as $field => $ldapAttr) {
            $werte = $users->map(fn($u) => $u[$ldapAttr][0] ?? null)
                ->filter(fn($v) => $v !== null && trim((string) $v) !== '');
            if ($top = $this->haeufigsterWert($werte)) {
                $suggestions[$field] = $top;
            }
        }

        // Rufnummer-Präfix: letzte 2 Ziffern abschneiden, häufigsten Präfix + "XX"
        $praefixe = $users->map(function ($u) {
            $nr = $u['telephonenumber'][0] ?? null;
            $nr = $nr !== null ? trim((string) $nr) : '';
            return strlen($nr) > 2 ? substr($nr, 0, -2) . 'XX' : null;
        })->filter();
        if ($top = $this->haeufigsterWert($praefixe)) {
            $suggestions['rufnummer_praefix'] = $top;
        }

        // Fax: OU teilt sich meist eine feste Nummer → alle vorkommenden
        // vollständigen Faxnummern als Vorschläge anbieten (kein Präfix/XX).
        $faxZaehlung = $users->map(fn($u) => $u['facsimiletelephonenumber'][0] ?? null)
            ->filter(fn($v) => $v !== null && trim((string) $v) !== '')
            ->countBy(fn($v) => trim((string) $v))
            ->sortDesc();
        $faxListe = [];
        foreach ($faxZaehlung as $value => $cnt) {
            $faxListe[] = ['value' => (string) $value, 'count' => $cnt];
        }
        if ($faxListe) {
            $suggestions['fax_numbers'] = $faxListe;
        }

        // Gruppen: memberOf aller OU-Benutzer aggregieren, häufigste zuerst
        $groupCounts = [];
        foreach ($users as $u) {
            $mo = $u['memberof'] ?? null;
            if (!is_array($mo)) {
                continue;
            }
            $n = $mo['count'] ?? 0;
            for ($i = 0; $i < $n; $i++) {
                $dn = $mo[$i] ?? null;
                if (!$dn) {
                    continue;
                }
                $groupCounts[$dn] = ($groupCounts[$dn] ?? 0) + 1;
            }
        }
        arsort($groupCounts);

        $groups = [];
        foreach (array_slice($groupCounts, 0, 15, true) as $dn => $cnt) {
            $groups[] = ['dn' => $dn, 'name' => $this->cnAusDn($dn), 'count' => $cnt];
        }
        $suggestions['groups'] = $groups;

        // Vorgesetzter: häufigster manager-DN → passenden AdUser suchen
        $managerWerte = $users->map(fn($u) => $u['manager'][0] ?? null)
            ->filter(fn($v) => $v !== null && trim((string) $v) !== '');
        if ($topManager = $this->haeufigsterWert($managerWerte)) {
            $adUser = AdUser::whereRaw('LOWER(distinguished_name) = ?', [strtolower($topManager['value'])])->first();
            if ($adUser) {
                $suggestions['vorgesetzter'] = [
                    'value' => $adUser->id,
                    'label' => $adUser->anzeigename_or_name . ' (' . $adUser->samaccountname . ')',
                    'count' => $topManager['count'],
                ];
            }
        }

        return response()->json([
            'count'       => $users->count(),
            'suggestions' => $suggestions ?: (object) [],
        ]);
    }

    /** Extrahiert den CN (Anzeigename) aus einem Distinguished Name. */
    private function cnAusDn(string $dn): string
    {
        if (preg_match('/^CN=([^,]+)/i', $dn, $m)) {
            // Escapte Kommata in AD-DNs wiederherstellen
            return str_replace('\\,', ',', $m[1]);
        }
        return $dn;
    }

    /** Ermittelt den häufigsten Wert einer Collection. Gibt ['value','count'] oder null zurück. */
    private function haeufigsterWert(\Illuminate\Support\Collection $werte): ?array
    {
        if ($werte->isEmpty()) {
            return null;
        }
        $zaehlung = $werte->countBy(fn($v) => (string) $v)->sortDesc();

        return ['value' => (string) $zaehlung->keys()->first(), 'count' => $zaehlung->first()];
    }

    /** AJAX: AD-Gruppen suchen */
    public function searchGroups(Request $request, AdProvisioningService $provisioner)
    {
        $query = $request->input('q', '');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        return response()->json($provisioner->searchGroups($query));
    }

    // ─── private ─────────────────────────────────────────────────────────────

    private function validateVorlage(Request $request): array
    {
        $data = $request->validate([
            'name'                    => ['required', 'string', 'max:255'],
            'beschreibung'            => ['nullable', 'string'],
            'abteilung_id'            => ['nullable', 'exists:abteilungen,id'],
            'samaccountname_pattern'  => ['nullable', 'string', 'max:255'],
            'upn_pattern'             => ['nullable', 'string', 'max:500'],
            'rufnummer_praefix'       => ['nullable', 'string', 'max:50'],
            'fax_praefix'             => ['nullable', 'string', 'max:50'],
            'strasse'                 => ['nullable', 'string', 'max:255'],
            'plz'                     => ['nullable', 'string', 'max:20'],
            'ort'                     => ['nullable', 'string', 'max:100'],
            'profilpfad_pattern'      => ['nullable', 'string', 'max:500'],
            'heimatverzeichnis_pattern'  => ['nullable', 'string', 'max:500'],
            'heimatverzeichnis_laufwerk' => ['nullable', 'string', 'max:3'],
            'anmeldeskript'           => ['nullable', 'string', 'max:255'],
            'laufwerke'               => ['nullable', 'json'],
            'abteilung_ad'            => ['nullable', 'string', 'max:255'],
            'ad_beschreibung'         => ['nullable', 'string', 'max:1024'],
            'buero'                   => ['nullable', 'string', 'max:255'],
            'firma'                   => ['nullable', 'string', 'max:255'],
            'vorgesetzter_ad_user_id' => ['nullable', 'exists:adusers,id'],
            'welcome_mail_override'   => ['nullable', 'string'],
            'supervisor_mail_override' => ['nullable', 'string'],
            'is_active'               => ['nullable', 'boolean'],
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['laufwerke']  = $request->filled('laufwerke')
            ? json_decode($request->input('laufwerke'), true)
            : null;

        return $data;
    }

    private function syncGruppen(OnboardingVorlage $vorlage, array $gruppenRaw): void
    {
        $vorlage->gruppen()->delete();

        foreach ($gruppenRaw as $g) {
            if (empty($g['dn'])) continue;
            OnboardingVorlageGruppe::create([
                'vorlage_id'   => $vorlage->id,
                'ad_group_dn'  => $g['dn'],
                'ad_group_name' => $g['name'] ?? $g['dn'],
            ]);
        }
    }
}
