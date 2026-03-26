<?php

namespace App\Http\Controllers;

use App\Models\Applikation;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RevisionController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function show(string $token)
    {
        $app = Applikation::where('revision_token', $token)
            ->with(['adminUser', 'verantwortlichAdUser', 'abteilung'])
            ->firstOrFail();

        // Bereits abgeschlossen?
        $alreadyDone = $app->revision_completed_at
            && $app->revision_notified_at
            && $app->revision_completed_at->gt($app->revision_notified_at);

        return view('revision.show', compact('app', 'alreadyDone'));
    }

    public function submit(string $token, Request $request)
    {
        $app = Applikation::where('revision_token', $token)
            ->with(['adminUser', 'verantwortlichAdUser', 'abteilung'])
            ->firstOrFail();

        // Doppelsubmit verhindern
        if ($app->revision_completed_at
            && $app->revision_notified_at
            && $app->revision_completed_at->gt($app->revision_notified_at)) {
            return view('revision.show', ['app' => $app, 'alreadyDone' => true]);
        }

        $validated = $request->validate([
            'app_aktiv'              => ['required', 'in:ja,nein'],
            'app_aktiv_notiz'        => ['nullable', 'string', 'max:1000'],
            'admin_korrekt'          => ['required', 'in:ja,nein'],
            'admin_notiz'            => ['nullable', 'string', 'max:1000'],
            'verantwortlich_korrekt' => ['required', 'in:ja,nein'],
            'verantwortlich_notiz'   => ['nullable', 'string', 'max:1000'],
            'doc_aktuell'            => ['required', 'in:ja,nein'],
            'doc_url'                => ['nullable', 'string', 'max:500'],
            'lieferant_korrekt'      => ['required', 'in:ja,nein'],
            'lieferant_notiz'        => ['nullable', 'string', 'max:1000'],
            'anmerkungen'            => ['nullable', 'string', 'max:2000'],
        ]);

        $changes = [];

        // doc_url direkt aktualisieren wenn als veraltet markiert
        if ($validated['doc_aktuell'] === 'nein' && !empty($validated['doc_url'])) {
            $changes['doc_url_alt'] = $app->doc_url;
            $changes['doc_url_neu'] = $validated['doc_url'];
            $app->doc_url = $validated['doc_url'];
        }

        // Alle Revisionsantworten im AuditLog festhalten
        $auditPayload = [
            'id'                     => $app->id,
            'name'                   => $app->name,
            'app_aktiv'              => $validated['app_aktiv'],
            'app_aktiv_notiz'        => $validated['app_aktiv_notiz'] ?? null,
            'admin_korrekt'          => $validated['admin_korrekt'],
            'admin_notiz'            => $validated['admin_notiz'] ?? null,
            'verantwortlich_korrekt' => $validated['verantwortlich_korrekt'],
            'verantwortlich_notiz'   => $validated['verantwortlich_notiz'] ?? null,
            'doc_aktuell'            => $validated['doc_aktuell'],
            'lieferant_korrekt'      => $validated['lieferant_korrekt'],
            'lieferant_notiz'        => $validated['lieferant_notiz'] ?? null,
            'anmerkungen'            => $validated['anmerkungen'] ?? null,
        ] + $changes;

        // Revision abschließen: Token rotieren, Datum vorverschieben, Flags setzen
        $app->revision_completed_at = now();
        $app->revision_token        = Str::random(64); // alter Link ungültig
        $app->revision_date         = $app->revision_date->addYear();
        $app->revision_notified_at  = null; // für nächsten Zyklus zurücksetzen
        $app->save();

        $this->auditLogger->log('Applikation', 'Revision abgeschlossen', $auditPayload);

        return view('revision.done', compact('app'));
    }
}
