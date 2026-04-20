<?php

namespace App\Modules\AdUsers\Http\Controllers;

use App\Mail\OffboardingConfirmationMail;
use App\Mail\OffboardingConfirmedAdminMail;
use App\Modules\AdUsers\Models\AdUser;
use App\Modules\AdUsers\Models\OffboardingRecord;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class OffboardingController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    // ─── Liste ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $filterStatus = $request->get('filter_status', '');
        $search       = $request->get('search', '');

        $query = OffboardingRecord::with(['aduser', 'anleger'])
            ->orderBy('datum_ausscheiden', 'desc');

        if ($filterStatus !== '') {
            $query->where('status', $filterStatus);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('vorname', 'like', "%{$search}%")
                  ->orWhere('nachname', 'like', "%{$search}%")
                  ->orWhere('samaccountname', 'like', "%{$search}%")
                  ->orWhere('abteilung', 'like', "%{$search}%");
            });
        }

        $perPage = in_array((int) $request->get('per_page', 25), [25, 50, 100, 250]) ? (int) $request->get('per_page', 25) : 25;
        $records = $query->paginate($perPage)->withQueryString();

        return view('adusers::offboarding.index', compact('records', 'filterStatus', 'search', 'perPage'));
    }

    // ─── Anlegen ──────────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $aduser = null;
        if ($request->has('aduser')) {
            $aduser = AdUser::find($request->get('aduser'));
        }

        // Prüfen ob bereits ein aktiver Vorgang existiert
        if ($aduser) {
            $existing = OffboardingRecord::where('samaccountname', $aduser->samaccountname)
                ->whereNotIn('status', ['abgeschlossen'])
                ->first();
            if ($existing) {
                return redirect()->route('adusers.offboarding.show', $existing)
                    ->with('error', 'Für diesen Benutzer läuft bereits ein Offboarding-Vorgang.');
            }
        }

        return view('adusers::offboarding.create', compact('aduser'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'aduser_id'          => ['nullable', 'integer', 'exists:adusers,id'],
            'vorname'            => ['required', 'string', 'max:100'],
            'nachname'           => ['required', 'string', 'max:100'],
            'samaccountname'     => ['required', 'string', 'max:100'],
            'personalnummer'     => ['nullable', 'string', 'max:50'],
            'abteilung'          => ['nullable', 'string', 'max:100'],
            'email_bestaetigung' => ['nullable', 'email', 'max:255'],
            'datum_ausscheiden'  => ['required', 'date'],
            'bemerkungen'        => ['nullable', 'string'],
            'personalmeldung_pdf'=> ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        // Doppelten Vorgang verhindern
        $samaccountname = $validated['samaccountname'] ?? '';
        if ($samaccountname) {
            $existing = OffboardingRecord::where('samaccountname', $samaccountname)
                ->whereNotIn('status', ['abgeschlossen'])
                ->first();
            if ($existing) {
                return redirect()->route('adusers.offboarding.show', $existing)
                    ->with('error', 'Für diesen Benutzer läuft bereits ein Offboarding-Vorgang.');
            }
        }

        // PDF separat aus Request holen (nicht im validated-Array)
        $pdfFile = $request->file('personalmeldung_pdf');
        unset($validated['personalmeldung_pdf']);

        $validated['anleger_user_id'] = Auth::id();
        $validated['anleger_name']    = Auth::user()->name;
        $validated['status']          = 'ausstehend';

        if ($pdfFile) {
            $validated['personalmeldung_pdf']      = file_get_contents($pdfFile->getRealPath());
            $validated['personalmeldung_pdf_name'] = $pdfFile->getClientOriginalName();
        }

        $record = OffboardingRecord::create($validated);

        $this->auditLogger->logModuleAction('Offboarding', 'Vorgang angelegt', [
            'id'   => $record->id,
            'name' => $record->voller_name,
        ]);

        // Weiterleitung zur Show-Seite mit Option E-Mail zu senden
        return redirect()->route('adusers.offboarding.show', $record)
            ->with('ask_send_email', true)
            ->with('success', 'Offboarding-Vorgang angelegt.');
    }

    // ─── Anzeigen ─────────────────────────────────────────────────────────────

    public function show(OffboardingRecord $record)
    {
        return view('adusers::offboarding.show', compact('record'));
    }

    // ─── E-Mail senden ────────────────────────────────────────────────────────

    public function sendEmail(OffboardingRecord $record)
    {
        if (empty($record->email_bestaetigung)) {
            return back()->with('error', 'Keine E-Mail-Adresse hinterlegt.');
        }

        $record->generateToken();
        $record->status                     = 'bestaetigung_angefragt';
        $record->bestaetigung_angefragt_at  = now();
        $record->save();

        Mail::to($record->email_bestaetigung)->send(new OffboardingConfirmationMail($record));

        $this->auditLogger->logModuleAction('Offboarding', 'Bestätigungsmail gesendet', [
            'id'    => $record->id,
            'email' => $record->email_bestaetigung,
        ]);

        return back()->with('success', 'Bestätigungsmail wurde gesendet.');
    }

    // ─── Konten gelöscht markieren ────────────────────────────────────────────

    public function markDeleted(Request $request, OffboardingRecord $record)
    {
        $request->validate([
            'datum_geloescht' => ['required', 'date'],
        ]);

        $record->update([
            'datum_geloescht' => $request->datum_geloescht,
            'geloescht_von'   => Auth::user()->name,
            'status'          => 'abgeschlossen',
        ]);

        $this->auditLogger->logModuleAction('Offboarding', 'Konten gelöscht markiert', [
            'id'   => $record->id,
            'name' => $record->voller_name,
        ]);

        return back()->with('success', 'Vorgang als abgeschlossen markiert.');
    }

    // ─── PDF-Upload ───────────────────────────────────────────────────────────

    public function upload(Request $request, OffboardingRecord $record)
    {
        $request->validate([
            'type' => ['required', 'in:personalmeldung,bestaetigung'],
            'pdf'  => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $type    = $request->type;
        $content = file_get_contents($request->file('pdf')->getRealPath());
        $name    = $request->file('pdf')->getClientOriginalName();

        if ($type === 'personalmeldung') {
            $record->update([
                'personalmeldung_pdf'      => $content,
                'personalmeldung_pdf_name' => $name,
            ]);
        } else {
            $record->update([
                'bestaetigung_pdf'      => $content,
                'bestaetigung_pdf_name' => $name,
            ]);
        }

        return back()->with('success', 'PDF wurde hochgeladen.');
    }

    // ─── PDF-Download ─────────────────────────────────────────────────────────

    public function download(OffboardingRecord $record, string $type)
    {
        abort_unless(in_array($type, ['personalmeldung', 'bestaetigung']), 404);

        return $record->getPdfResponse($type);
    }

    // ─── Löschen ──────────────────────────────────────────────────────────────

    public function destroy(OffboardingRecord $record)
    {
        $name = $record->voller_name;
        $record->delete();

        $this->auditLogger->logModuleAction('Offboarding', 'Vorgang gelöscht', ['name' => $name]);

        return redirect()->route('adusers.offboarding.index')
            ->with('success', 'Vorgang "' . $name . '" wurde gelöscht.');
    }

    // ─── Admin-Bestätigungen: Deaktivierung + Löschung (kein Auth) ──────────

    public function adminDeaktivierungShow(string $token)
    {
        $record = OffboardingRecord::where('deaktivierung_token', $token)->firstOrFail();
        return view('adusers::offboarding.admin_confirm', [
            'record' => $record,
            'type'   => 'deaktivierung',
            'done'   => $record->deaktivierung_bestaetigt_at !== null,
        ]);
    }

    public function adminDeaktivierungSubmit(Request $request, string $token)
    {
        $record = OffboardingRecord::where('deaktivierung_token', $token)->firstOrFail();

        if ($record->deaktivierung_bestaetigt_at) {
            return view('adusers::offboarding.admin_confirm', [
                'record' => $record, 'type' => 'deaktivierung', 'done' => true,
            ]);
        }

        $request->validate(['bestaetigt_von' => ['required', 'string', 'max:200']]);

        $record->update([
            'deaktivierung_bestaetigt_at'  => now(),
            'deaktivierung_bestaetigt_von' => $request->bestaetigt_von,
        ]);

        $this->auditLogger->logModuleAction('Offboarding', 'Deaktivierung bestätigt', [
            'id' => $record->id, 'name' => $record->voller_name,
        ]);

        return view('adusers::offboarding.admin_confirm', [
            'record' => $record, 'type' => 'deaktivierung', 'done' => false, 'justDone' => true,
        ]);
    }

    public function adminLoeschungShow(string $token)
    {
        $record = OffboardingRecord::where('loeschung_token', $token)->firstOrFail();
        return view('adusers::offboarding.admin_confirm', [
            'record' => $record,
            'type'   => 'loeschung',
            'done'   => $record->loeschung_bestaetigt_at !== null,
        ]);
    }

    public function adminLoeschungSubmit(Request $request, string $token)
    {
        $record = OffboardingRecord::where('loeschung_token', $token)->firstOrFail();

        if ($record->loeschung_bestaetigt_at) {
            return view('adusers::offboarding.admin_confirm', [
                'record' => $record, 'type' => 'loeschung', 'done' => true,
            ]);
        }

        $request->validate(['bestaetigt_von' => ['required', 'string', 'max:200']]);

        $record->update([
            'loeschung_bestaetigt_at'  => now(),
            'loeschung_bestaetigt_von' => $request->bestaetigt_von,
            'datum_geloescht'          => today(),
            'geloescht_von'            => $request->bestaetigt_von,
            'status'                   => 'abgeschlossen',
        ]);

        $this->auditLogger->logModuleAction('Offboarding', 'Löschung bestätigt', [
            'id' => $record->id, 'name' => $record->voller_name,
        ]);

        return view('adusers::offboarding.admin_confirm', [
            'record' => $record, 'type' => 'loeschung', 'done' => false, 'justDone' => true,
        ]);
    }

    // ─── Öffentliche Bestätigungsseite (kein Auth) ───────────────────────────

    public function confirmShow(string $token)
    {
        $record = OffboardingRecord::where('bestaetigungstoken', $token)->firstOrFail();

        $alreadyDone = $record->bestaetigung_erhalten_at !== null;

        return view('adusers::offboarding.confirm', compact('record', 'alreadyDone'));
    }

    public function confirmSubmit(Request $request, string $token)
    {
        $record = OffboardingRecord::where('bestaetigungstoken', $token)->firstOrFail();

        if ($record->bestaetigung_erhalten_at !== null) {
            return view('adusers::offboarding.confirm', [
                'record'      => $record,
                'alreadyDone' => true,
            ]);
        }

        $request->validate([
            'bestaetigung_name' => ['required', 'string', 'max:200'],
            'alle_bestaetigt'   => ['required', 'in:1'],
        ]);

        $record->update([
            'bestaetigung_erhalten_at' => now(),
            'bestaetigung_name'        => $request->bestaetigung_name,
            'bestaetigung_ip'          => $request->ip(),
            'status'                   => 'bestaetigt',
        ]);

        // PDF der digitalen Bestätigung generieren und in DB speichern
        try {
            $pdf = app('dompdf.wrapper')
                ->loadView('adusers::offboarding.bestaetigung_pdf', ['record' => $record->fresh()])
                ->setPaper('a4', 'portrait');
            $record->update([
                'bestaetigung_pdf'      => $pdf->output(),
                'bestaetigung_pdf_name' => 'bestaetigung_digital_' . $record->id . '.pdf',
            ]);
        } catch (\Exception $e) {
            // PDF-Fehler soll Bestätigung nicht blockieren
        }

        // Admin benachrichtigen
        if ($record->anleger && $record->anleger->email) {
            try {
                Mail::to($record->anleger->email)->send(new OffboardingConfirmedAdminMail($record));
            } catch (\Exception) {
                // Mail-Fehler soll Bestätigung nicht blockieren
            }
        }

        return view('adusers::offboarding.confirm', [
            'record'      => $record,
            'alreadyDone' => false,
            'justDone'    => true,
        ]);
    }
}
