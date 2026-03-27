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

        $records = $query->paginate(25)->withQueryString();

        return view('adusers::offboarding.index', compact('records', 'filterStatus', 'search'));
    }

    // ─── Anlegen ──────────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $aduser = null;
        if ($request->has('aduser')) {
            $aduser = AdUser::find($request->get('aduser'));
        }

        return view('adusers::offboarding.create', compact('aduser'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'aduser_id'        => ['nullable', 'integer', 'exists:adusers,id'],
            'vorname'          => ['required', 'string', 'max:100'],
            'nachname'         => ['required', 'string', 'max:100'],
            'samaccountname'   => ['required', 'string', 'max:100'],
            'personalnummer'   => ['nullable', 'string', 'max:50'],
            'abteilung'        => ['nullable', 'string', 'max:100'],
            'email_bestaetigung' => ['nullable', 'email', 'max:255'],
            'datum_ausscheiden'=> ['required', 'date'],
            'bemerkungen'      => ['nullable', 'string'],
        ]);

        $validated['anleger_user_id'] = Auth::id();
        $validated['anleger_name']    = Auth::user()->name;
        $validated['status']          = 'ausstehend';

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
        ]);

        $record->update([
            'bestaetigung_erhalten_at' => now(),
            'bestaetigung_name'        => $request->bestaetigung_name,
            'bestaetigung_ip'          => $request->ip(),
            'status'                   => 'bestaetigt',
        ]);

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
