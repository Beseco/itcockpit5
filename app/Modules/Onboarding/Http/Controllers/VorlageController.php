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
        $vorlagen = OnboardingVorlage::with(['abteilung', 'gruppen'])
            ->orderBy('name')
            ->get();

        return view('onboarding::vorlagen.index', compact('vorlagen'));
    }

    public function create()
    {
        $abteilungen = Abteilung::orderBy('name')->get();
        $adUsers     = AdUser::aktiv()->orderBy('nachname')->get();

        return view('onboarding::vorlagen.create', compact('abteilungen', 'adUsers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateVorlage($request);

        $vorlage = OnboardingVorlage::create($validated);
        $this->syncGruppen($vorlage, $request->input('gruppen', []));

        return redirect()->route('onboarding.vorlagen.index')
            ->with('success', 'Vorlage "' . $vorlage->name . '" wurde angelegt.');
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

    public function destroy(OnboardingVorlage $vorlage): RedirectResponse
    {
        $name = $vorlage->name;
        $vorlage->delete();

        return redirect()->route('onboarding.vorlagen.index')
            ->with('success', 'Vorlage "' . $name . '" wurde gelöscht.');
    }

    /** Vorlage klonen */
    public function clone(OnboardingVorlage $vorlage): RedirectResponse
    {
        $kopie = $vorlage->replicate();
        $kopie->name = 'Kopie von ' . $vorlage->name;
        $kopie->is_active = false;
        $kopie->save();

        foreach ($vorlage->gruppen as $gruppe) {
            OnboardingVorlageGruppe::create([
                'vorlage_id'    => $kopie->id,
                'ad_group_dn'   => $gruppe->ad_group_dn,
                'ad_group_name' => $gruppe->ad_group_name,
            ]);
        }

        return redirect()->route('onboarding.vorlagen.edit', $kopie)
            ->with('success', 'Vorlage "' . $vorlage->name . '" wurde geklont. Bitte anpassen und aktivieren.');
    }

    /** Massen-Generator: Übersicht aller Abteilungen mit AD-Pfad */
    public function generate()
    {
        $belegteAbteilungen = OnboardingVorlage::whereNotNull('abteilung_id')->pluck('abteilung_id')->all();

        $abteilungen = Abteilung::whereNotNull('ad_path')
            ->where('ad_path', '!=', '')
            ->orderBy('name')
            ->get();

        return view('onboarding::vorlagen.generate', compact('abteilungen', 'belegteAbteilungen'));
    }

    /** Massen-Generator: Vorlagen für die ausgewählten Abteilungen anlegen */
    public function storeGenerated(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'abteilung_ids'   => ['required', 'array', 'min:1'],
            'abteilung_ids.*' => ['integer', 'exists:abteilungen,id'],
        ]);

        $abteilungen = Abteilung::whereIn('id', $validated['abteilung_ids'])->get();
        $erstellt    = 0;
        $uebersprungen = 0;

        foreach ($abteilungen as $abteilung) {
            // Bereits eine Vorlage für diese OU? → überspringen
            if (OnboardingVorlage::where('abteilung_id', $abteilung->id)->exists()) {
                $uebersprungen++;
                continue;
            }

            // Alle Muster-Felder bleiben leer → globale Vorgaben werden geerbt.
            OnboardingVorlage::create([
                'name'         => $abteilung->name,
                'abteilung_id' => $abteilung->id,
                'is_active'    => true,
            ]);
            $erstellt++;
        }

        $msg = "{$erstellt} Vorlage(n) erstellt.";
        if ($uebersprungen > 0) {
            $msg .= " {$uebersprungen} übersprungen (bereits vorhanden).";
        }

        return redirect()->route('onboarding.vorlagen.index')->with('success', $msg);
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
