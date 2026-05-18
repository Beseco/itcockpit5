<?php

namespace App\Modules\Schulen\Http\Controllers;

use App\Modules\Schulen\Models\Schule;
use App\Modules\Schulen\Models\SchulenKontakt;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class KontaktController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function store(Request $request, Schule $schule)
    {
        $validated = $this->validateKontakt($request);
        $validated['schule_id'] = $schule->id;

        $kontakt = SchulenKontakt::create($validated);

        $this->auditLogger->logModuleAction('Schulen', 'Kontakt hinzugefügt', [
            'schule_id' => $schule->id,
            'kontakt'   => $kontakt->vollname(),
        ]);

        return redirect()->route('schulen.show', $schule)
            ->with('success', 'Kontakt "' . $kontakt->vollname() . '" hinzugefügt.');
    }

    public function update(Request $request, Schule $schule, SchulenKontakt $kontakt)
    {
        abort_if($kontakt->schule_id !== $schule->id, 404);

        $validated = $this->validateKontakt($request);
        $kontakt->update($validated);

        $this->auditLogger->logModuleAction('Schulen', 'Kontakt aktualisiert', [
            'schule_id' => $schule->id,
            'kontakt'   => $kontakt->vollname(),
        ]);

        return redirect()->route('schulen.show', $schule)
            ->with('success', 'Kontakt aktualisiert.');
    }

    public function destroy(Schule $schule, SchulenKontakt $kontakt)
    {
        abort_if($kontakt->schule_id !== $schule->id, 404);

        $name = $kontakt->vollname();
        $kontakt->delete();

        $this->auditLogger->logModuleAction('Schulen', 'Kontakt gelöscht', [
            'schule_id' => $schule->id,
            'name'      => $name,
        ]);

        return redirect()->route('schulen.show', $schule)
            ->with('success', 'Kontakt "' . $name . '" gelöscht.');
    }

    private function validateKontakt(Request $request): array
    {
        return $request->validate([
            'rolle'    => ['required', 'in:rektor,konrektor,sekretaerin,systembetreuer,sonstige'],
            'vorname'  => ['required', 'string', 'max:255'],
            'nachname' => ['required', 'string', 'max:255'],
            'telefon'  => ['nullable', 'string', 'max:100'],
            'email'    => ['nullable', 'email', 'max:255'],
            'notizen'  => ['nullable', 'string', 'max:500'],
        ]);
    }
}
