<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Modules\Onboarding\Models\OnboardingSettings;
use App\Modules\Onboarding\Services\AdProvisioningService;
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
            'welcome_mail_subject'     => ['required', 'string', 'max:255'],
            'welcome_mail_body'        => ['nullable', 'string'],
            'supervisor_mail_subject'  => ['required', 'string', 'max:255'],
            'supervisor_mail_body'     => ['nullable', 'string'],
        ]);

        $settings = OnboardingSettings::getSingleton();

        $data = $request->only([
            'ldap_write_bind_dn',
            'group_search_base_dn',
            'welcome_mail_subject',
            'welcome_mail_body',
            'supervisor_mail_subject',
            'supervisor_mail_body',
        ]);

        if ($request->filled('ldap_write_bind_password')) {
            $data['ldap_write_bind_password'] = $request->input('ldap_write_bind_password');
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
