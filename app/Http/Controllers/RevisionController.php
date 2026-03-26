<?php

namespace App\Http\Controllers;

use App\Mail\RevisionCompletedMail;
use App\Mail\VerantwortlicherAssignedMail;
use App\Models\Applikation;
use App\Models\Dienstleister;
use App\Models\User;
use App\Modules\AdUsers\Models\AdUser;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RevisionController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function show(string $token)
    {
        $app = Applikation::where('revision_token', $token)
            ->with(['adminUser', 'verantwortlichAdUser', 'abteilung'])
            ->firstOrFail();

        $alreadyDone = $app->revision_completed_at
            && $app->revision_notified_at
            && $app->revision_completed_at->gt($app->revision_notified_at);

        $users        = User::where('is_active', true)->orderBy('name')->get();
        $adUsers      = AdUser::aktiv()->orderBy('anzeigename')->get();
        $dienstleister = Dienstleister::where('status', '!=', 'gesperrt')->orderBy('firmenname')->get();

        return view('revision.show', compact('app', 'alreadyDone', 'users', 'adUsers', 'dienstleister'));
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
            $users        = User::where('is_active', true)->orderBy('name')->get();
            $adUsers      = AdUser::aktiv()->orderBy('anzeigename')->get();
            $dienstleister = Dienstleister::where('status', '!=', 'gesperrt')->orderBy('firmenname')->get();
            return view('revision.show', compact('app', 'users', 'adUsers', 'dienstleister') + ['alreadyDone' => true]);
        }

        $validated = $request->validate([
            'app_aktiv'                     => ['required', 'in:ja,nein'],
            'app_aktiv_notiz'               => ['nullable', 'string', 'max:1000'],
            'admin_korrekt'                 => ['required', 'in:ja,nein'],
            'new_admin_user_id'             => ['nullable', 'integer', 'exists:users,id'],
            'verantwortlich_korrekt'        => ['required', 'in:ja,nein'],
            'new_verantwortlich_ad_user_id' => ['nullable', 'integer', 'exists:adusers,id'],
            'doc_aktuell'                   => ['required', 'in:ja,nein'],
            'doc_url'                       => ['nullable', 'string', 'max:500'],
            'lieferant_korrekt'             => ['required', 'in:ja,nein'],
            'new_dienstleister_id'          => ['nullable', 'integer', 'exists:dienstleister,id'],
            'new_ansprechpartner'           => ['nullable', 'string', 'max:255'],
            'anmerkungen'                   => ['nullable', 'string', 'max:2000'],
        ]);

        $changes = [];

        // --- Admin geändert ---
        if ($validated['admin_korrekt'] === 'nein' && !empty($validated['new_admin_user_id'])) {
            $newAdmin = User::find($validated['new_admin_user_id']);
            $changes['admin_alt'] = $app->adminUser?->name;
            $changes['admin_neu'] = $newAdmin->name;
            $app->admin_user_id   = $newAdmin->id;
        }

        // --- Verfahrensverantwortlicher geändert ---
        if ($validated['verantwortlich_korrekt'] === 'nein' && !empty($validated['new_verantwortlich_ad_user_id'])) {
            $newVerantwortlich = AdUser::find($validated['new_verantwortlich_ad_user_id']);
            $changes['verantwortlich_alt']      = $app->verantwortlichAdUser?->anzeigenameOrName ?? $app->verantwortlich_sg;
            $changes['verantwortlich_neu']      = $newVerantwortlich->anzeigenameOrName;
            $app->verantwortlich_ad_user_id     = $newVerantwortlich->id;
            $app->verantwortlich_sg             = null;

            // Benachrichtigung an den neuen Verfahrensverantwortlichen
            if (!empty($newVerantwortlich->email)) {
                try {
                    Mail::to($newVerantwortlich->email)->send(new VerantwortlicherAssignedMail($app, $newVerantwortlich));
                } catch (\Exception) {
                    // Mailversand optional – Fehler nicht fatal
                }
            }
        }

        // --- Dokumentations-URL geändert ---
        if ($validated['doc_aktuell'] === 'nein' && !empty($validated['doc_url'])) {
            $changes['doc_url_alt'] = $app->doc_url;
            $changes['doc_url_neu'] = $validated['doc_url'];
            $app->doc_url           = $validated['doc_url'];
        }

        // --- Lieferant/Hersteller geändert ---
        if ($validated['lieferant_korrekt'] === 'nein' && !empty($validated['new_dienstleister_id'])) {
            $dienstleister             = Dienstleister::find($validated['new_dienstleister_id']);
            $changes['hersteller_alt'] = $app->hersteller;
            $changes['hersteller_neu'] = $dienstleister->firmenname;
            $app->hersteller           = $dienstleister->firmenname;

            if (!empty($validated['new_ansprechpartner'])) {
                $changes['ansprechpartner_alt'] = $app->ansprechpartner;
                $changes['ansprechpartner_neu'] = $validated['new_ansprechpartner'];
                $app->ansprechpartner           = $validated['new_ansprechpartner'];
            }
        }

        // --- Revision abschließen ---
        $app->revision_completed_at = now();
        $app->revision_token        = Str::random(64);
        $app->revision_date         = $app->revision_date->addYear();
        $app->revision_notified_at  = null;
        $app->save();

        // --- AuditLog ---
        $answers = [
            'app_aktiv'              => $validated['app_aktiv'],
            'app_aktiv_notiz'        => $validated['app_aktiv_notiz'] ?? null,
            'admin_korrekt'          => $validated['admin_korrekt'],
            'verantwortlich_korrekt' => $validated['verantwortlich_korrekt'],
            'doc_aktuell'            => $validated['doc_aktuell'],
            'lieferant_korrekt'      => $validated['lieferant_korrekt'],
            'anmerkungen'            => $validated['anmerkungen'] ?? null,
        ];

        try {
            $this->auditLogger->log('Applikation', 'Revision abgeschlossen', [
                'id'      => $app->id,
                'name'    => $app->name,
                'answers' => $answers,
                'changes' => $changes,
            ]);
        } catch (\Exception) {
            // AuditLog ohne Auth nicht möglich
        }

        // --- Benachrichtigung an Revisions-E-Mail-Adresse ---
        $notifyEmail = config('revision.notify_email');
        if (!empty($notifyEmail)) {
            try {
                Mail::to($notifyEmail)->send(new RevisionCompletedMail($app, $answers, $changes));
            } catch (\Exception) {
                // Mailversand optional
            }
        }

        return view('revision.done', compact('app'));
    }
}
