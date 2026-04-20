<?php

namespace App\Http\Controllers;

use App\Mail\AbteilungNeueSoftwareMail;
use App\Mail\AbteilungRevisionProposalMail;
use App\Models\Abteilung;
use App\Models\AbteilungRevisionProposal;
use App\Models\AbteilungRevisionSettings;
use App\Models\Applikation;
use App\Modules\AdUsers\Models\AdUser;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AbteilungRevisionController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    // ─── Start ────────────────────────────────────────────────────────────────

    public function show(string $token)
    {
        $abteilung = $this->findAbteilung($token);
        $apps      = $abteilung->applikationen()->orderBy('name')->get();

        if ($apps->isEmpty()) {
            return redirect()->route('abteilung-revision.neue-app', $token);
        }

        $reviewed = AbteilungRevisionProposal::where('abteilung_revision_token', $token)
            ->pluck('applikation_id')->toArray();

        $next = $apps->first(fn($a) => !in_array($a->id, $reviewed));

        if (!$next) {
            return redirect()->route('abteilung-revision.neue-app', $token);
        }

        return redirect()->route('abteilung-revision.app', [$token, $next->id]);
    }

    // ─── Einzelne App ─────────────────────────────────────────────────────────

    public function showApp(string $token, int $appId)
    {
        $abteilung = $this->findAbteilung($token);
        $app       = Applikation::with(['adminUser', 'verantwortlichAdUser', 'abteilung', 'servers'])->findOrFail($appId);
        $adUsers   = AdUser::aktiv()->orderBy('anzeigename')->get();
        [$current, $total, $prevId, $nextId] = $this->progress($token, $abteilung, $appId);

        return view('revision.abteilung_app', compact(
            'token', 'abteilung', 'app', 'adUsers',
            'current', 'total', 'prevId', 'nextId'
        ));
    }

    public function submitApp(string $token, int $appId, Request $request)
    {
        $abteilung = $this->findAbteilung($token);
        $app       = Applikation::with(['adminUser', 'verantwortlichAdUser'])->findOrFail($appId);

        // Bereits bearbeitet → zur nächsten
        if (AbteilungRevisionProposal::where('abteilung_revision_token', $token)
                ->where('applikation_id', $appId)->exists()) {
            return $this->redirectToNext($token, $abteilung, $appId);
        }

        if ($request->boolean('skip')) {
            AbteilungRevisionProposal::create([
                'abteilung_revision_token' => $token,
                'applikation_id'           => $appId,
                'original_data'            => [],
                'skipped'                  => true,
            ]);
            return $this->redirectToNext($token, $abteilung, $appId);
        }

        $validated = $request->validate([
            'einsatzzweck'              => ['nullable', 'string', 'max:3000'],
            'ansprechpartner'           => ['nullable', 'string', 'max:255'],
            'verantwortlich_ad_user_id' => ['nullable', 'integer', 'exists:adusers,id'],
            'confidentiality'           => ['required', 'in:A,B,C'],
            'integrity'                 => ['required', 'in:A,B,C'],
            'availability'              => ['required', 'in:A,B,C'],
            'reason'                    => ['nullable', 'string', 'max:1000'],
            'kommentar'                 => ['nullable', 'string', 'max:3000'],
            'nicht_vorhanden'           => ['nullable', 'boolean'],
        ]);

        $schutzbedarfGeaendert = $validated['confidentiality'] !== $app->confidentiality
            || $validated['integrity']      !== $app->integrity
            || $validated['availability']   !== $app->availability;

        if ($schutzbedarfGeaendert && empty($validated['reason'])) {
            return back()->withErrors(['reason' => 'Bei Änderung des Schutzbedarfs ist eine Begründung erforderlich.'])
                ->withInput();
        }

        $nichtVorhanden = $request->boolean('nicht_vorhanden');

        $original = [
            'einsatzzweck'              => $app->einsatzzweck,
            'ansprechpartner'           => $app->ansprechpartner,
            'verantwortlich_ad_user_id' => $app->verantwortlich_ad_user_id,
            'verantwortlich_name'       => $app->verantwortlichAdUser?->anzeigename,
            'confidentiality'           => $app->confidentiality,
            'integrity'                 => $app->integrity,
            'availability'              => $app->availability,
        ];

        $proposed = [
            'einsatzzweck'              => $validated['einsatzzweck'],
            'ansprechpartner'           => $validated['ansprechpartner'],
            'verantwortlich_ad_user_id' => $validated['verantwortlich_ad_user_id'],
            'verantwortlich_name'       => $validated['verantwortlich_ad_user_id']
                ? AdUser::find($validated['verantwortlich_ad_user_id'])?->anzeigename
                : null,
            'confidentiality'           => $validated['confidentiality'],
            'integrity'                 => $validated['integrity'],
            'availability'              => $validated['availability'],
        ];

        $hasChanges = $original !== $proposed || $nichtVorhanden || !empty($validated['kommentar']);

        $proposal = AbteilungRevisionProposal::create([
            'abteilung_revision_token' => $token,
            'applikation_id'           => $appId,
            'original_data'            => $original,
            'proposed_data'            => $hasChanges ? $proposed : null,
            'reason'                   => $validated['reason'] ?? null,
            'kommentar'                => $validated['kommentar'] ?? null,
            'nicht_vorhanden'          => $nichtVorhanden,
            'approval_token'           => $hasChanges ? Str::random(64) : null,
            'skipped'                  => false,
        ]);

        // IT-Admin benachrichtigen wenn Änderungen vorhanden
        if ($hasChanges && $app->adminUser?->email) {
            try {
                Mail::to($app->adminUser->email)
                    ->send(new AbteilungRevisionProposalMail($proposal, $app, $abteilung));
            } catch (\Exception) {}
        }

        return $this->redirectToNext($token, $abteilung, $appId);
    }

    // ─── Neue App vorschlagen ─────────────────────────────────────────────────

    public function showNewApp(string $token)
    {
        $abteilung = $this->findAbteilung($token);
        return view('revision.abteilung_neue_app', compact('token', 'abteilung'));
    }

    public function submitNewApp(string $token, Request $request)
    {
        $abteilung = $this->findAbteilung($token);

        if ($request->boolean('skip')) {
            return redirect()->route('abteilung-revision.fertig', $token);
        }

        $validated = $request->validate([
            'apps'                => ['required', 'array', 'min:1'],
            'apps.*.name'         => ['required', 'string', 'max:255'],
            'apps.*.einsatzzweck' => ['nullable', 'string', 'max:500'],
            'apps.*.hersteller'   => ['nullable', 'string', 'max:255'],
        ]);

        $apps = collect($validated['apps'])->filter(fn($a) => !empty($a['name']));

        if ($apps->isNotEmpty()) {
            $settings = AbteilungRevisionSettings::getSingleton();
            try {
                Mail::to($settings->new_app_email)
                    ->send(new AbteilungNeueSoftwareMail($apps, $abteilung));
            } catch (\Exception) {}
        }

        return redirect()->route('abteilung-revision.fertig', $token);
    }

    // ─── Fertig ───────────────────────────────────────────────────────────────

    public function done(string $token)
    {
        $abteilung = $this->findAbteilung($token);

        $abteilung->revision_completed_at = now();
        $abteilung->revision_token        = Str::random(64);
        $abteilung->save();

        try {
            $this->auditLogger->log('Abteilung', 'Abteilungsrevision abgeschlossen', [
                'id' => $abteilung->id, 'name' => $abteilung->name,
            ]);
        } catch (\Exception) {}

        return view('revision.abteilung_fertig', compact('abteilung'));
    }

    // ─── IT-Admin Genehmigung ─────────────────────────────────────────────────

    public function approve(string $approvalToken)
    {
        $proposal = AbteilungRevisionProposal::where('approval_token', $approvalToken)
            ->with(['applikation.adminUser', 'applikation.verantwortlichAdUser'])
            ->firstOrFail();

        $alreadyApproved = $proposal->approved_at !== null;

        if (!$alreadyApproved && $proposal->proposed_data) {
            $app  = $proposal->applikation;
            $data = $proposal->proposed_data;

            $app->einsatzzweck    = $data['einsatzzweck']    ?? $app->einsatzzweck;
            $app->ansprechpartner = $data['ansprechpartner'] ?? $app->ansprechpartner;
            $app->confidentiality = $data['confidentiality'] ?? $app->confidentiality;
            $app->integrity       = $data['integrity']       ?? $app->integrity;
            $app->availability    = $data['availability']    ?? $app->availability;

            if (!empty($data['verantwortlich_ad_user_id'])) {
                $app->verantwortlich_ad_user_id = $data['verantwortlich_ad_user_id'];
            }

            $app->save();

            $proposal->approved_at = now();
            $proposal->save();
        }

        return view('revision.abteilung_approve', compact('proposal', 'alreadyApproved'));
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function findAbteilung(string $token): Abteilung
    {
        return Abteilung::where('revision_token', $token)
            ->with(['vorgesetzter', 'stellvertreter'])
            ->firstOrFail();
    }

    private function progress(string $token, Abteilung $abteilung, int $currentAppId): array
    {
        $apps     = $abteilung->applikationen()->orderBy('name')->get();
        $ids      = $apps->pluck('id')->toArray();
        $idx      = array_search($currentAppId, $ids);
        $total    = count($ids);
        $current  = $idx !== false ? $idx + 1 : 1;
        $prevId   = $idx > 0 ? $ids[$idx - 1] : null;
        $nextId   = ($idx !== false && $idx < $total - 1) ? $ids[$idx + 1] : null;

        return [$current, $total, $prevId, $nextId];
    }

    private function redirectToNext(string $token, Abteilung $abteilung, int $currentAppId)
    {
        $apps     = $abteilung->applikationen()->orderBy('name')->pluck('id')->toArray();
        $reviewed = AbteilungRevisionProposal::where('abteilung_revision_token', $token)
            ->pluck('applikation_id')->toArray();

        $next = collect($apps)->first(fn($id) => !in_array($id, $reviewed));

        if ($next) {
            return redirect()->route('abteilung-revision.app', [$token, $next]);
        }

        return redirect()->route('abteilung-revision.neue-app', $token);
    }
}
