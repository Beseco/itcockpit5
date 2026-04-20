<?php

namespace App\Http\Controllers;

use App\Models\Abteilung;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AbteilungRevisionController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function show(string $token)
    {
        $abteilung = Abteilung::where('revision_token', $token)
            ->with(['vorgesetzter', 'stellvertreter', 'applikationen.adminUser', 'applikationen.verantwortlichAdUser'])
            ->firstOrFail();

        $alreadyDone = $abteilung->revision_completed_at
            && $abteilung->revision_notified_at
            && $abteilung->revision_completed_at->gt($abteilung->revision_notified_at);

        return view('revision.abteilung_show', compact('abteilung', 'alreadyDone', 'token'));
    }

    public function submit(string $token, Request $request)
    {
        $abteilung = Abteilung::where('revision_token', $token)
            ->with(['vorgesetzter', 'stellvertreter', 'applikationen.adminUser', 'applikationen.verantwortlichAdUser'])
            ->firstOrFail();

        if ($abteilung->revision_completed_at
            && $abteilung->revision_notified_at
            && $abteilung->revision_completed_at->gt($abteilung->revision_notified_at)) {
            return view('revision.abteilung_done', compact('abteilung'));
        }

        $validated = $request->validate([
            'feedback'            => ['nullable', 'array'],
            'feedback.*'          => ['nullable', 'string', 'max:2000'],
            'neue_software'       => ['nullable', 'array'],
            'neue_software.*.name'=> ['nullable', 'string', 'max:255'],
            'neue_software.*.zweck'=> ['nullable', 'string', 'max:500'],
            'anmerkungen'         => ['nullable', 'string', 'max:3000'],
        ]);

        $abteilung->revision_completed_at = now();
        $abteilung->revision_token        = Str::random(64);
        $abteilung->save();

        $notifyEmail = config('revision.notify_email');
        if (!empty($notifyEmail)) {
            try {
                $applikationen = $abteilung->applikationen;
                $feedback      = $validated['feedback'] ?? [];
                $neueSoftware  = collect($validated['neue_software'] ?? [])->filter(fn($s) => !empty($s['name']));
                $anmerkungen   = $validated['anmerkungen'] ?? null;

                $html = $this->buildFeedbackHtml($abteilung, $applikationen, $feedback, $neueSoftware, $anmerkungen);

                Mail::html($html, function ($msg) use ($notifyEmail, $abteilung) {
                    $msg->to($notifyEmail)
                        ->subject('[Abteilungsrevision] ' . $abteilung->anzeigename . ' – Rückmeldung eingegangen');
                });
            } catch (\Exception) {
                // Mailversand optional
            }
        }

        try {
            $this->auditLogger->log('Abteilung', 'Abteilungsrevision abgeschlossen', [
                'id'   => $abteilung->id,
                'name' => $abteilung->name,
            ]);
        } catch (\Exception) {}

        return view('revision.abteilung_done', compact('abteilung'));
    }

    private function buildFeedbackHtml(Abteilung $abteilung, $applikationen, array $feedback, $neueSoftware, ?string $anmerkungen): string
    {
        $datum = now()->format('d.m.Y, H:i');
        $rows = '';
        foreach ($applikationen as $app) {
            $notiz = trim($feedback[$app->id] ?? '');
            if ($notiz === '') continue;
            $rows .= '<tr><td style="padding:6px 8px;border-bottom:1px solid #e5e7eb;font-weight:600;">'
                . htmlspecialchars($app->name) . '</td>'
                . '<td style="padding:6px 8px;border-bottom:1px solid #e5e7eb;">'
                . nl2br(htmlspecialchars($notiz)) . '</td></tr>';
        }

        $neuRows = '';
        foreach ($neueSoftware as $s) {
            $neuRows .= '<li><strong>' . htmlspecialchars($s['name']) . '</strong>'
                . (trim($s['zweck'] ?? '') ? ': ' . htmlspecialchars($s['zweck']) : '') . '</li>';
        }

        $anm = $anmerkungen ? '<p>' . nl2br(htmlspecialchars($anmerkungen)) . '</p>' : '<p style="color:#9ca3af;">—</p>';

        return <<<HTML
<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;font-size:14px;color:#374151;background:#f3f4f6;padding:32px 0;">
<table width="600" align="center" style="background:#fff;border-radius:8px;border:1px solid #e5e7eb;">
<tr><td style="background:#4f46e5;border-radius:8px 8px 0 0;padding:20px 28px;">
  <span style="font-size:11px;color:#c7d2fe;text-transform:uppercase;letter-spacing:1px;">IT Cockpit · Abteilungsrevision</span>
  <h1 style="margin:6px 0 0;font-size:18px;color:#fff;">{$abteilung->anzeigename}</h1>
  <p style="margin:4px 0 0;font-size:12px;color:#c7d2fe;">Rückmeldung eingegangen am {$datum}</p>
</td></tr>
<tr><td style="padding:24px 28px;">
  <h3 style="margin:0 0 12px;font-size:14px;font-weight:700;color:#1f2937;">Anmerkungen zu Applikationen</h3>
  <table width="100%" style="border-collapse:collapse;margin-bottom:20px;">
    <thead><tr>
      <th style="text-align:left;padding:6px 8px;background:#f9fafb;border-bottom:2px solid #e5e7eb;font-size:12px;width:40%;">Applikation</th>
      <th style="text-align:left;padding:6px 8px;background:#f9fafb;border-bottom:2px solid #e5e7eb;font-size:12px;">Anmerkung</th>
    </tr></thead>
    <tbody>{$rows}</tbody>
  </table>
  <h3 style="margin:0 0 8px;font-size:14px;font-weight:700;color:#1f2937;">Neue Software-Vorschläge</h3>
  <ul style="margin:0 0 20px;padding-left:20px;">{$neuRows}</ul>
  <h3 style="margin:0 0 8px;font-size:14px;font-weight:700;color:#1f2937;">Allgemeine Anmerkungen</h3>
  {$anm}
</td></tr>
<tr><td style="background:#f9fafb;border-top:1px solid #e5e7eb;border-radius:0 0 8px 8px;padding:12px 28px;font-size:12px;color:#9ca3af;">
  Automatisch generiert vom IT Cockpit
</td></tr>
</table></body></html>
HTML;
    }
}
