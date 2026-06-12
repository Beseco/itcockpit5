<?php

namespace App\Modules\Vertragsmanagement\Http\Controllers;

use App\Models\Dienstleister;
use App\Modules\Vertragsmanagement\Models\Vertrag;
use App\Modules\Vertragsmanagement\Models\VertragDokument;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VertragController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        $status = $request->get('status');
        $search = $request->get('search', '');

        $query = Vertrag::with('dienstleister')->orderBy('vertragsende');

        if (in_array($status, array_keys(Vertrag::STATUS), true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('dienstleister', fn ($d) => $d->where('firmenname', 'like', "%{$search}%"));
            });
        }

        $vertraege = $query->paginate(25)->withQueryString();

        return view('vertragsmanagement::index', compact('vertraege', 'status', 'search'));
    }

    public function create()
    {
        return view('vertragsmanagement::create', [
            'vertrag'        => null,
            'dienstleister'  => Dienstleister::orderBy('firmenname')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateVertrag($request);

        $vertrag = Vertrag::create($validated);
        $this->handleUploads($request, $vertrag);

        $this->auditLogger->logModuleAction('Vertragsmanagement', 'Vertrag erstellt', [
            'id'   => $vertrag->id,
            'name' => $vertrag->name,
        ]);

        return redirect()->route('vertragsmanagement.show', $vertrag)
            ->with('success', 'Vertrag "' . $vertrag->name . '" wurde angelegt.');
    }

    public function show(Vertrag $vertrag)
    {
        $vertrag->load(['dienstleister', 'dokumente.hochgeladenVon']);

        return view('vertragsmanagement::show', compact('vertrag'));
    }

    public function edit(Vertrag $vertrag)
    {
        $vertrag->load('dokumente');

        return view('vertragsmanagement::edit', [
            'vertrag'       => $vertrag,
            'dienstleister' => Dienstleister::orderBy('firmenname')->get(),
        ]);
    }

    public function update(Request $request, Vertrag $vertrag): RedirectResponse
    {
        $validated = $this->validateVertrag($request);

        $vertrag->update($validated);
        $this->handleUploads($request, $vertrag);

        $this->auditLogger->logModuleAction('Vertragsmanagement', 'Vertrag aktualisiert', [
            'id'   => $vertrag->id,
            'name' => $vertrag->name,
        ]);

        return redirect()->route('vertragsmanagement.show', $vertrag)
            ->with('success', 'Vertrag wurde aktualisiert.');
    }

    public function destroy(Vertrag $vertrag): RedirectResponse
    {
        $name = $vertrag->name;

        // Dokumente von der Disk entfernen
        foreach ($vertrag->dokumente as $dok) {
            Storage::disk('local')->delete($dok->pfad);
        }

        $vertrag->delete();

        $this->auditLogger->logModuleAction('Vertragsmanagement', 'Vertrag gelöscht', ['name' => $name]);

        return redirect()->route('vertragsmanagement.index')
            ->with('success', 'Vertrag "' . $name . '" wurde gelöscht.');
    }

    // ── Dokumente ────────────────────────────────────────────────────────────

    public function storeDokument(Request $request, Vertrag $vertrag): RedirectResponse
    {
        $request->validate([
            'dokumente'   => ['required', 'array'],
            'dokumente.*' => ['file', 'mimes:pdf', 'max:20480'],
        ]);

        $this->handleUploads($request, $vertrag);

        return redirect()->route('vertragsmanagement.show', $vertrag)
            ->with('success', 'Dokument(e) hochgeladen.');
    }

    public function downloadDokument(VertragDokument $dokument): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($dokument->pfad), 404);

        return Storage::disk('local')->download($dokument->pfad, $dokument->dateiname);
    }

    public function destroyDokument(VertragDokument $dokument): RedirectResponse
    {
        $vertragId = $dokument->vertrag_id;
        Storage::disk('local')->delete($dokument->pfad);
        $dokument->delete();

        return redirect()->route('vertragsmanagement.show', $vertragId)
            ->with('success', 'Dokument gelöscht.');
    }

    // ── Privat ────────────────────────────────────────────────────────────────

    private function validateVertrag(Request $request): array
    {
        return $request->validate([
            'name'                      => ['required', 'string', 'max:255'],
            'dienstleister_id'          => ['nullable', 'exists:dienstleister,id'],
            'vertragsbeginn'            => ['required', 'date'],
            'vertragsende'              => ['nullable', 'date', 'after_or_equal:vertragsbeginn'],
            'kuendigungsfrist_monate'   => ['nullable', 'integer', 'min:0', 'max:120'],
            'erinnerung_vorlauf_wochen' => ['required', 'integer', 'min:1', 'max:52'],
            'benachrichtigungs_email'   => ['nullable', 'email', 'max:255'],
            'status'                    => ['required', Rule::in(array_keys(Vertrag::STATUS))],
            'notizen'                   => ['nullable', 'string'],
            'dokumente'                 => ['nullable', 'array'],
            'dokumente.*'               => ['file', 'mimes:pdf', 'max:20480'],
        ]);
    }

    private function handleUploads(Request $request, Vertrag $vertrag): void
    {
        foreach ($request->file('dokumente', []) as $file) {
            if (!$file) {
                continue;
            }
            $pfad = $file->store("vertraege/{$vertrag->id}", 'local');

            $vertrag->dokumente()->create([
                'dateiname'       => $file->getClientOriginalName(),
                'pfad'            => $pfad,
                'groesse'         => $file->getSize(),
                'mime_type'       => $file->getClientMimeType(),
                'hochgeladen_von' => Auth::id(),
            ]);
        }
    }
}
