<?php

namespace App\Modules\Baramundi\Http\Controllers;

use App\Modules\Baramundi\Models\BaraSettings;
use App\Modules\Baramundi\Services\SmbScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BaraSettingsController extends Controller
{
    public function __construct(private readonly SmbScannerService $scanner) {}

    public function index()
    {
        $settings = BaraSettings::getSingleton();
        return view('baramundi::settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'scan_interval_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'email_on_smb_error'    => ['nullable', 'boolean'],
            'notification_email'    => ['nullable', 'email', 'max:255'],
            'smb_domain'            => ['nullable', 'string', 'max:255'],
            'smb_username'          => ['nullable', 'string', 'max:255'],
            'smb_password'          => ['nullable', 'string', 'max:500'],
        ]);

        $settings = BaraSettings::getSingleton();

        $data = [
            'scan_interval_minutes' => $request->integer('scan_interval_minutes'),
            'email_on_smb_error'    => $request->boolean('email_on_smb_error'),
            'notification_email'    => $request->input('notification_email') ?: null,
            'smb_domain'            => $request->input('smb_domain') ?: null,
            'smb_username'          => $request->input('smb_username') ?: null,
        ];

        // Passwort nur überschreiben wenn neu eingegeben; smb_clear leert es explizit
        if ($request->boolean('smb_clear')) {
            $data['smb_password'] = null;
        } elseif ($request->filled('smb_password')) {
            $data['smb_password'] = $request->input('smb_password');
        }

        $settings->fill($data)->save();

        return redirect()->route('baramundi.settings')
            ->with('success', 'Einstellungen gespeichert. Das neue Scan-Intervall wird nach einem Neustart des Webservers aktiv.');
    }

    public function testSmb(Request $request): JsonResponse
    {
        $request->validate([
            'unc_path' => ['required', 'string', 'max:1000'],
        ]);

        $result = $this->scanner->testPath($request->input('unc_path'));

        return response()->json($result);
    }
}
