<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Modules\Onboarding\Mail\OnboardingMailVerifyMail;
use App\Modules\Onboarding\Mail\OnboardingSupervisorMail;
use App\Modules\Onboarding\Mail\OnboardingWelcomeMail;
use App\Modules\Onboarding\Models\OnboardingRecord;
use App\Modules\Onboarding\Models\OnboardingSettings;
use App\Modules\Onboarding\Services\AdProvisioningService;
use Illuminate\Support\Facades\Log;
use App\Services\PasswordGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OnboardingTodoController extends Controller
{
    /** Todo-Liste anzeigen (auth erforderlich) */
    public function show(string $token)
    {
        $record = OnboardingRecord::where('todo_token', $token)
            ->with(['vorlage', 'createdBy'])
            ->firstOrFail();

        return view('onboarding::todo.show', compact('record'));
    }

    /** Todo-Punkt umschalten (AJAX, auth erforderlich) */
    public function checkItem(Request $request, string $token): JsonResponse
    {
        $record = OnboardingRecord::where('todo_token', $token)->firstOrFail();
        $key    = $request->input('key');

        if (!array_key_exists($key, OnboardingRecord::TODOS)) {
            return response()->json(['ok' => false, 'message' => 'Unbekannter Todo-Schlüssel.'], 422);
        }

        $todos = $record->todos ?? [];

        if (in_array($key, $todos, true)) {
            $todos = array_values(array_diff($todos, [$key]));
        } else {
            $todos[] = $key;
        }

        $wasChecked = in_array($key, $todos, true);
        $record->update(['todos' => $todos]);
        $record->refresh();

        // E-Laufwerk abgehakt → homeDirectory/homeDrive automatisch aus AD-Profil entfernen
        if ($key === 'e_laufwerk' && $wasChecked && $record->distinguished_name) {
            try {
                (new AdProvisioningService())->clearHomeDirectory($record->distinguished_name);
            } catch (\Throwable $e) {
                Log::warning("Onboarding: clearHomeDirectory fehlgeschlagen für {$record->distinguished_name}: " . $e->getMessage());
            }
        }

        return response()->json([
            'ok'       => true,
            'todos'    => $todos,
            'allDone'  => $record->allTodosDone(),
            'verified' => $record->mail_verified_at !== null,
        ]);
    }

    /** Test-Mail an neuen Benutzer senden (AJAX, auth erforderlich) */
    public function sendMailTest(string $token): JsonResponse
    {
        $record = OnboardingRecord::where('todo_token', $token)->firstOrFail();

        $mailToken = Str::random(48);
        $record->update(['mail_test_token' => $mailToken, 'mail_verified_at' => null]);

        $verifyUrl = route('onboarding.todo.verify-mail', [
            'token'      => $token,
            'mailToken'  => $mailToken,
        ]);

        try {
            Mail::to($record->upn)->send(new OnboardingMailVerifyMail($record, $verifyUrl));
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Mail-Versand fehlgeschlagen: ' . $e->getMessage()]);
        }

        return response()->json(['ok' => true, 'message' => "Test-Mail an {$record->upn} versendet."]);
    }

    /** Verifizierungs-Link aus der Test-Mail (kein Login erforderlich) */
    public function verifyMail(string $token, string $mailToken)
    {
        $record = OnboardingRecord::where('todo_token', $token)
            ->where('mail_test_token', $mailToken)
            ->firstOrFail();

        $record->update(['mail_verified_at' => now()]);

        return view('onboarding::todo.verified', compact('record'));
    }

    /** Mail-Verifikationsstatus abrufen (AJAX, auth erforderlich) */
    public function status(string $token): JsonResponse
    {
        $record = OnboardingRecord::where('todo_token', $token)->firstOrFail();
        return response()->json([
            'verified' => $record->mail_verified_at !== null,
            'todos'    => $record->todos ?? [],
            'allDone'  => $record->allTodosDone(),
        ]);
    }

    /** Onboarding abschließen: finales Passwort setzen + Mails versenden */
    public function complete(string $token): RedirectResponse
    {
        $record = OnboardingRecord::where('todo_token', $token)
            ->with(['vorlage.vorgesetzter'])
            ->firstOrFail();

        if (!$record->allTodosDone()) {
            return back()->with('error', 'Bitte zuerst alle Aufgaben abhaken.');
        }

        if (!$record->mail_verified_at) {
            return back()->with('error', 'Bitte zuerst die E-Mail-Funktion bestätigen (Test-Mail senden und Link anklicken).');
        }

        if ($record->phase === 'completed') {
            return back()->with('info', 'Dieser Onboarding-Vorgang ist bereits abgeschlossen.');
        }

        // Finales Passwort generieren + im AD setzen (mit Änderungspflicht)
        $finalPassword = (new PasswordGeneratorService())->generate();

        try {
            (new AdProvisioningService())->setFinalPassword($record->distinguished_name, $finalPassword);
        } catch (\Throwable $e) {
            return back()->with('error', 'Passwort konnte nicht gesetzt werden: ' . $e->getMessage());
        }

        // Begrüßungs- und Vorgesetzten-Mail versenden
        $this->sendFinalMails($record, $finalPassword);

        $record->update([
            'phase'        => 'completed',
            'completed_at' => now(),
        ]);

        session()->flash('final_password', $finalPassword);

        return redirect()->route('onboarding.todo.completed', $token);
    }

    /** Abschluss-Seite (finales Passwort einmalig anzeigen) */
    public function completed(string $token)
    {
        $record        = OnboardingRecord::where('todo_token', $token)->with(['vorlage', 'createdBy'])->firstOrFail();
        $finalPassword = session('final_password');

        return view('onboarding::todo.completed', compact('record', 'finalPassword'));
    }

    // ─── private ─────────────────────────────────────────────────────────────

    private function sendFinalMails(OnboardingRecord $record, string $password): void
    {
        $obSettings = OnboardingSettings::getSingleton();
        $vorlage    = $record->vorlage;
        $snapshot   = $record->ad_attributes_snapshot ?? [];

        $vars = [
            '%vorname%'      => $record->vorname,
            '%nachname%'     => $record->nachname,
            '%benutzername%' => $record->samaccountname,
            '%upn%'          => $record->upn,
            '%rufnummer%'    => $record->rufnummer ?? '–',
            '%mobil%'        => $snapshot['mobile'] ?? '–',
            '%buero%'        => $snapshot['buero'] ?? '–',
            '%passwort%'     => $password,
        ];

        // Begrüßungsmail an neuen User
        try {
            $subject = $obSettings->welcome_mail_subject;
            $body    = strtr($vorlage?->welcome_mail_override ?: $obSettings->welcome_mail_body, $vars);
            Mail::to($record->upn)->send(new OnboardingWelcomeMail($subject, $body));
            $record->update(['welcome_mail_sent_at' => now()]);
        } catch (\Throwable) {}

        // Info-Mail an Vorgesetzten
        try {
            $vorgesetzterEmail = $vorlage?->vorgesetzter?->email;
            if ($vorgesetzterEmail) {
                $subject = strtr($obSettings->supervisor_mail_subject, $vars);
                $body    = strtr($vorlage?->supervisor_mail_override ?: $obSettings->supervisor_mail_body, $vars);
                Mail::to($vorgesetzterEmail)->send(new OnboardingSupervisorMail($subject, $body, $record));
                $record->update(['supervisor_mail_sent_at' => now()]);
            }
        } catch (\Throwable) {}
    }
}
