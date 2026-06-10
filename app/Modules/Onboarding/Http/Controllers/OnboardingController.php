<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Models\AuditLog;
use App\Modules\AdUsers\Models\AdUser;
use App\Modules\AdUsers\Services\LdapConnectionService;
use App\Modules\Onboarding\Mail\OnboardingAdminSetupMail;
use App\Modules\Onboarding\Models\OnboardingRecord;
use App\Modules\Onboarding\Models\OnboardingVorlage;
use App\Modules\Onboarding\Services\AdProvisioningService;
use App\Modules\Onboarding\Services\PhoneNumberService;
use App\Modules\Onboarding\Services\UsernameGeneratorService;
use App\Services\PasswordGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly AdProvisioningService  $provisioner,
        private readonly UsernameGeneratorService $usernameGen,
        private readonly PhoneNumberService     $phoneService,
    ) {}

    /** Dashboard: letzte Onboarding-Vorgänge */
    public function index()
    {
        $records  = OnboardingRecord::with(['vorlage', 'createdBy'])
            ->latest()
            ->paginate(20);
        $vorlagen = OnboardingVorlage::where('is_active', true)->orderBy('name')->get();

        return view('onboarding::index', compact('records', 'vorlagen'));
    }

    /** Formular: Vorlage + Personendaten eingeben */
    public function create(Request $request)
    {
        $vorlageId = $request->input('vorlage_id');
        $vorlagen  = OnboardingVorlage::where('is_active', true)->with('abteilung')->orderBy('name')->get();
        $vorlage   = $vorlageId ? OnboardingVorlage::with(['abteilung', 'gruppen', 'vorgesetzter'])->find($vorlageId) : null;

        return view('onboarding::onboarding.create', compact('vorlagen', 'vorlage'));
    }

    /** AJAX: Vorschau Benutzername + Rufnummer ermitteln */
    public function preview(Request $request)
    {
        $request->validate([
            'vorlage_id' => ['required', 'exists:onboarding_vorlagen,id'],
            'vorname'    => ['required', 'string'],
            'nachname'   => ['required', 'string'],
        ]);

        $vorlage = OnboardingVorlage::findOrFail($request->integer('vorlage_id'));
        $ldap    = app(LdapConnectionService::class);

        $samRaw      = $this->usernameGen->resolvePattern($vorlage->samaccountname_pattern, $request->input('vorname'), $request->input('nachname'));
        $upnRaw      = $this->usernameGen->resolvePattern($vorlage->upn_pattern,           $request->input('vorname'), $request->input('nachname'));
        $samResult   = $this->usernameGen->findAvailable($samRaw, $request->input('vorname'), $request->input('nachname'), $ldap);
        $rufnummer   = $vorlage->rufnummer_praefix ? $this->phoneService->findNextFree($vorlage->rufnummer_praefix, $ldap) : null;
        $fax         = $vorlage->fax_praefix       ? $this->phoneService->findNextFree($vorlage->fax_praefix, $ldap)       : null;

        return response()->json([
            'samaccountname' => $samResult['samaccountname'],
            'alternatives'   => $samResult['alternatives'],
            'upn'            => $upnRaw,
            'rufnummer'      => $rufnummer,
            'fax'            => $fax,
            'buero'          => $vorlage->buero,
        ]);
    }

    /** Benutzer anlegen */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'vorlage_id'     => ['required', 'exists:onboarding_vorlagen,id'],
            'vorname'        => ['required', 'string', 'max:100'],
            'nachname'       => ['required', 'string', 'max:100'],
            'samaccountname' => ['required', 'string', 'max:20'],
            'upn'            => ['required', 'email', 'max:255'],
            'rufnummer'      => ['nullable', 'string', 'max:50'],
            'mobile'         => ['nullable', 'string', 'max:50'],
            'fax'            => ['nullable', 'string', 'max:50'],
            'buero'          => ['nullable', 'string', 'max:255'],
            'vorgesetzter_dn' => ['nullable', 'string', 'max:1000'],
        ]);

        $vorlage  = OnboardingVorlage::with(['abteilung', 'gruppen'])->findOrFail($request->integer('vorlage_id'));

        $obSettings = \App\Modules\Onboarding\Models\OnboardingSettings::getSingleton();
        $password   = $obSettings->temp_password ?: (new PasswordGeneratorService())->generate();

        $data = array_merge($request->only(['vorname', 'nachname', 'samaccountname', 'upn', 'rufnummer', 'mobile', 'fax', 'buero', 'vorgesetzter_dn']), [
            'password'                   => $password,
            'profilpfad'                 => $this->resolvePattern($vorlage->profilpfad_pattern, $request),
            'heimatverzeichnis'          => $this->resolvePattern($vorlage->heimatverzeichnis_pattern, $request),
            'heimatverzeichnis_laufwerk' => $vorlage->heimatverzeichnis_laufwerk ?: null,
        ]);

        $todoToken = Str::random(48);

        $record = OnboardingRecord::create([
            'vorlage_id'          => $vorlage->id,
            'created_by_user_id'  => auth()->id(),
            'vorname'             => $request->input('vorname'),
            'nachname'            => $request->input('nachname'),
            'samaccountname'      => $request->input('samaccountname'),
            'upn'                 => $request->input('upn'),
            'rufnummer'           => $request->input('rufnummer'),
            'status'              => 'ausstehend',
            'phase'               => 'setup',
            'todo_token'          => $todoToken,
            'todos'               => [],
        ]);

        try {
            $result = $this->provisioner->createUser($vorlage, $data);

            $warnings = array_filter([
                $result['password_warning'] ?? null,
                $result['homedir_warning']  ?? null,
            ]);
            $record->update([
                'distinguished_name'     => $result['distinguished_name'],
                'ad_attributes_snapshot' => \Illuminate\Support\Arr::except($data, ['password']),
                'status'                 => 'erfolgreich',
                'error_message'          => $warnings ? implode(' | ', $warnings) : null,
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'module'  => 'onboarding',
                'action'  => 'user_created',
                'payload' => ['samaccountname' => $record->samaccountname, 'upn' => $record->upn],
            ]);

            // Admin-Mail mit temporärem Passwort + Todo-Link
            $todoUrl = route('onboarding.todo.show', $todoToken);
            $adminEmail = auth()->user()->email;
            try {
                Mail::to($adminEmail)->send(new OnboardingAdminSetupMail($record, $password, $todoUrl));
            } catch (\Throwable) {
                // Mail-Fehler soll Onboarding nicht abbrechen
            }

        } catch (\Throwable $e) {
            $record->update(['status' => 'fehler', 'error_message' => $e->getMessage()]);

            return redirect()->route('onboarding.records.show', $record)
                ->with('error', 'Fehler beim Anlegen: ' . $e->getMessage());
        }

        // Temporäres Passwort nur als Session-Flash – niemals in DB
        session()->flash('onboarding_password', $password);
        session()->flash('onboarding_todo_token', $todoToken);

        return redirect()->route('onboarding.records.show', $record);
    }

    // ─── private ─────────────────────────────────────────────────────────────

    private function resolvePattern(?string $pattern, Request $request): ?string
    {
        if (!$pattern) return null;

        return str_replace(
            ['%benutzername%', '%vorname%', '%nachname%'],
            [$request->input('samaccountname'), $request->input('vorname'), $request->input('nachname')],
            $pattern
        );
    }
}
