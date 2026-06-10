<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Modules\Onboarding\Models\OnboardingSettings;
use App\Modules\Onboarding\Services\AdProvisioningService;
use App\Modules\Onboarding\Services\ExchangeMailboxService;
use App\Modules\Onboarding\Services\HomeDirectoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OnboardingSettingsController extends Controller
{
    public function index()
    {
        $settings = OnboardingSettings::getSingleton();
        return view('onboarding::settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'ldap_write_bind_dn'       => ['nullable', 'string', 'max:500'],
            'ldap_write_bind_password' => ['nullable', 'string', 'max:500'],
            'group_search_base_dn'     => ['nullable', 'string', 'max:1000'],
            'exchange_url'             => ['nullable', 'url', 'max:500'],
            'exchange_user'            => ['nullable', 'string', 'max:255'],
            'exchange_password'        => ['nullable', 'string', 'max:500'],
            'exchange_auth'            => ['nullable', 'in:Negotiate,Basic,Kerberos,NTLM'],
            'exchange_mailbox_db'      => ['nullable', 'string', 'max:255'],
            'smb_user'                 => ['nullable', 'string', 'max:255'],
            'smb_password'             => ['nullable', 'string', 'max:500'],
            'temp_password'            => ['nullable', 'string', 'max:255'],
            'welcome_mail_subject'     => ['required', 'string', 'max:255'],
            'welcome_mail_body'        => ['nullable', 'string'],
            'supervisor_mail_subject'  => ['required', 'string', 'max:255'],
            'supervisor_mail_body'     => ['nullable', 'string'],
            'default_samaccountname_pattern'     => ['nullable', 'string', 'max:255'],
            'default_upn_pattern'                => ['nullable', 'string', 'max:500'],
            'default_profilpfad_pattern'         => ['nullable', 'string', 'max:500'],
            'default_heimatverzeichnis_pattern'  => ['nullable', 'string', 'max:500'],
            'default_heimatverzeichnis_laufwerk' => ['nullable', 'string', 'max:3'],
            'default_anmeldeskript'              => ['nullable', 'string', 'max:255'],
        ]);

        $settings = OnboardingSettings::getSingleton();

        $data = $request->only([
            'ldap_write_bind_dn',
            'group_search_base_dn',
            'exchange_url',
            'exchange_user',
            'exchange_auth',
            'exchange_mailbox_db',
            'smb_user',
            'welcome_mail_subject',
            'welcome_mail_body',
            'supervisor_mail_subject',
            'supervisor_mail_body',
            'default_samaccountname_pattern',
            'default_upn_pattern',
            'default_profilpfad_pattern',
            'default_heimatverzeichnis_pattern',
            'default_heimatverzeichnis_laufwerk',
            'default_anmeldeskript',
        ]);

        if ($request->filled('ldap_write_bind_password')) {
            $data['ldap_write_bind_password'] = $request->input('ldap_write_bind_password');
        }
        if ($request->filled('exchange_password')) {
            $data['exchange_password'] = $request->input('exchange_password');
        }
        if ($request->filled('smb_password')) {
            $data['smb_password'] = $request->input('smb_password');
        }
        if ($request->filled('temp_password')) {
            $data['temp_password'] = $request->input('temp_password');
        }

        $settings->fill($data)->save();

        return redirect()->route('onboarding.settings')
            ->with('success', 'Einstellungen gespeichert.');
    }

    /** AJAX: Verbindungstest für den Write-Account */
    public function testConnection(AdProvisioningService $provisioner): JsonResponse
    {
        $result = $provisioner->testWriteConnection();
        return response()->json($result);
    }

    /** AJAX: Exchange-Verbindung testen (pwsh vorhanden + PSSession funktioniert) */
    public function testExchange(): JsonResponse
    {
        $exchange = new ExchangeMailboxService();
        if (!$exchange->isConfigured()) {
            return response()->json(['ok' => false, 'message' => 'Exchange nicht vollständig konfiguriert (URL, Benutzer und Passwort erforderlich).']);
        }
        // Testet nur ob pwsh verfügbar ist + eine Verbindung möglich wäre
        $result = $exchange->testConnection();
        return response()->json(['ok' => $result['success'], 'message' => $result['message']]);
    }

    /** AJAX: SMB-Verbindung testen */
    public function testSmb(): JsonResponse
    {
        $svc = new HomeDirectoryService();
        if (!$svc->isConfigured()) {
            return response()->json(['ok' => false, 'message' => 'SMB-Zugangsdaten nicht konfiguriert.']);
        }

        $settings = OnboardingSettings::getSingleton();
        // Test-Pfad aus der ersten Vorlage mit Heimatverzeichnis-Muster ableiten
        $vorlage = \App\Modules\Onboarding\Models\OnboardingVorlage::whereNotNull('heimatverzeichnis_pattern')
            ->where('heimatverzeichnis_pattern', '!=', '')
            ->first();

        if (!$vorlage) {
            return response()->json(['ok' => false, 'message' => 'Keine Vorlage mit Heimatverzeichnis-Muster gefunden. Bitte erst eine Vorlage anlegen.']);
        }

        // Nur den Share-Pfad testen (ohne Benutzernamen-Ordner)
        $pattern = $vorlage->heimatverzeichnis_pattern;
        $parts   = array_filter(explode('\\', ltrim(str_replace('/', '\\', $pattern), '\\')));
        $parts   = array_values($parts);
        if (count($parts) < 2) {
            return response()->json(['ok' => false, 'message' => 'Ungültiges Heimatverzeichnis-Muster in der Vorlage.']);
        }
        $testPath = '\\\\' . $parts[0] . '\\' . $parts[1];

        $result = $svc->testConnection($testPath . '\\test');
        return response()->json(['ok' => $result['success'], 'message' => $result['message']]);
    }

    /** AJAX: Gruppen in der konfigurierten Suchbasis zählen */
    public function testGroupSearch(AdProvisioningService $provisioner): JsonResponse
    {
        try {
            $result = $provisioner->countGroups();
            return response()->json([
                'ok'      => true,
                'message' => "{$result['total']} Gruppen in {$result['base_dn']} gefunden",
                'security'     => $result['security'],
                'distribution' => $result['distribution'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }
}
