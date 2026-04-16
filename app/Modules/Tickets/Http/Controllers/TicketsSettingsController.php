<?php

namespace App\Modules\Tickets\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\TicketScoreMail;
use App\Models\User;
use App\Modules\Tickets\Models\TicketsSettings;
use App\Modules\Tickets\Services\ZammadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TicketsSettingsController extends Controller
{
    public function index()
    {
        $settings = TicketsSettings::getSingleton();

        return view('tickets::settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'url'       => ['required', 'url', 'max:500'],
            'api_token' => ['nullable', 'string', 'max:500'],
            'enabled'   => ['nullable'],
        ]);

        $settings = TicketsSettings::getSingleton();
        $settings->url     = $request->input('url');
        $settings->enabled = $request->boolean('enabled');

        // Token nur aktualisieren, wenn ein neuer Wert eingegeben wurde
        if ($request->filled('api_token')) {
            $settings->api_token = $request->input('api_token');
        }

        $settings->save();

        return redirect()->route('tickets.settings')
            ->with('success', 'Einstellungen wurden gespeichert.');
    }

    public function updateScoring(Request $request)
    {
        $request->validate([
            'email_enabled'   => ['nullable'],
            'email_threshold' => ['required', 'numeric', 'min:0'],
            'score_green_max' => ['required', 'numeric', 'min:0'],
            'score_red_min'   => ['required', 'numeric', 'min:0'],
        ]);

        $settings = TicketsSettings::getSingleton();
        $settings->email_enabled   = $request->boolean('email_enabled');
        $settings->email_threshold = $request->input('email_threshold');
        $settings->score_green_max = $request->input('score_green_max');
        $settings->score_red_min   = $request->input('score_red_min');
        $settings->save();

        return redirect()->route('tickets.settings')
            ->with('success', 'Scoring-Einstellungen wurden gespeichert.');
    }

    public function testConnection()
    {
        $service = new ZammadService();
        $result = $service->testConnection();

        return response()->json($result);
    }

    public function updateTestMail(Request $request)
    {
        $request->validate([
            'test_email'   => ['required', 'email', 'max:255'],
            'test_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $settings = TicketsSettings::getSingleton();
        $settings->test_email   = $request->input('test_email');
        $settings->test_user_id = $request->input('test_user_id');
        $settings->save();

        return redirect()->route('tickets.settings')
            ->with('success', 'Test-E-Mail-Einstellungen gespeichert.');
    }

    public function sendTestMail(Request $request)
    {
        $settings = TicketsSettings::getSingleton();

        if (!$settings->isConfigured()) {
            return redirect()->route('tickets.settings')
                ->with('error', 'Zammad ist nicht konfiguriert.');
        }

        $request->validate([
            'test_email'   => ['required', 'email', 'max:255'],
            'test_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $testEmail = $request->input('test_email');
        $user      = User::findOrFail($request->input('test_user_id'));

        // Einstellungen persistieren
        $settings->test_email   = $testEmail;
        $settings->test_user_id = $user->id;
        $settings->save();

        // Score für den gewählten User berechnen
        $service = new ZammadService();
        $tickets = $service->searchTickets(email: $user->email, includeClosed: false);

        $yellowTickets = $tickets->filter(fn($t) => ZammadService::getTicketColor($t) === 'yellow');
        $redTickets    = $tickets->filter(fn($t) => ZammadService::getTicketColor($t) === 'red');
        $score         = ($yellowTickets->count() * 0.5) + ($redTickets->count() * 1.0);

        try {
            Mail::to($testEmail)->send(
                new TicketScoreMail($user, $score, $yellowTickets, $redTickets, $settings)
            );
        } catch (\Exception $e) {
            return redirect()->route('tickets.settings')
                ->with('error', 'Mail konnte nicht gesendet werden: ' . $e->getMessage());
        }

        return redirect()->route('tickets.settings')
            ->with('success', "Test-Mail für „{$user->name}" (Score: " . number_format($score, 1) . ") wurde an {$testEmail} gesendet.");
    }
}
