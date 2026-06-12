<?php

namespace App\Modules\Baramundi\Http\Controllers;

use App\Modules\Baramundi\Mail\NewVersionDetectedMail;
use App\Modules\Baramundi\Models\BaraSettings;
use App\Modules\Baramundi\Models\WatchedPackage;
use App\Modules\Baramundi\Services\SmbScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;

class BaraSettingsController extends Controller
{
    public function __construct(private readonly SmbScannerService $scanner) {}

    public function index()
    {
        $settings = BaraSettings::getSingleton();
        return view('baramundi::settings', compact('settings'));
    }

    /** Speichert nur Scan-Konfiguration + E-Mail – berührt SMB-Credentials nicht. */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'scan_interval_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'email_on_smb_error'    => ['nullable', 'boolean'],
            'notification_email'    => ['nullable', 'email', 'max:255'],
            'zammad_group'          => ['nullable', 'string', 'max:255'],
        ]);

        $settings = BaraSettings::getSingleton();
        $settings->fill([
            'scan_interval_minutes' => $request->integer('scan_interval_minutes'),
            'email_on_smb_error'    => $request->boolean('email_on_smb_error'),
            'notification_email'    => $request->input('notification_email') ?: null,
            'zammad_group'          => $request->input('zammad_group') ?: null,
        ])->save();

        return redirect()->route('baramundi.settings')
            ->with('success', 'Einstellungen gespeichert. Das neue Scan-Intervall wird nach einem Neustart des Webservers aktiv.');
    }

    /** Speichert nur SMB-Zugangsdaten – berührt Scan-Konfiguration nicht. */
    public function updateCredentials(Request $request): RedirectResponse
    {
        $request->validate([
            'smb_domain'   => ['nullable', 'string', 'max:255'],
            'smb_username' => ['nullable', 'string', 'max:255'],
            'smb_password' => ['nullable', 'string', 'max:500'],
        ]);

        $settings = BaraSettings::getSingleton();

        $data = [
            'smb_domain'   => $request->input('smb_domain') ?: null,
            'smb_username' => $request->input('smb_username') ?: null,
        ];

        if ($request->boolean('smb_clear')) {
            $data['smb_password'] = null;
            $data['smb_username'] = null;
            $data['smb_domain']   = null;
        } elseif ($request->filled('smb_password')) {
            $data['smb_password'] = $request->input('smb_password');
        }
        // Passwort leer gelassen → bestehendes Passwort beibehalten (kein Eintrag in $data)

        $settings->fill($data)->save();

        return redirect()->route('baramundi.settings')
            ->with('success', 'SMB-Zugangsdaten gespeichert.');
    }

    /** Sendet eine Test-E-Mail an die konfigurierte Benachrichtigungsadresse. */
    public function testMail(Request $request): JsonResponse
    {
        $settings = BaraSettings::getSingleton();

        if (!$settings->notification_email) {
            return response()->json([
                'ok'      => false,
                'message' => 'Keine Benachrichtigungs-E-Mail-Adresse konfiguriert.',
            ]);
        }

        // Beispielpaket für die Test-Mail zusammenbauen (kein DB-Eintrag nötig)
        $fakePkg = new WatchedPackage([
            'name'        => 'Testpaket (TeamViewer Host)',
            'server_name' => 'Bara-01',
            'share_path'  => 'dip$\\ManagedSoftware\\source\\TeamViewer\\TeamViewerHost\\15.x-x64',
        ]);

        try {
            Mail::to($settings->notification_email)
                ->send(new NewVersionDetectedMail($fakePkg, '15.99.0-x64'));

            return response()->json([
                'ok'      => true,
                'message' => 'Test-E-Mail gesendet an ' . $settings->notification_email,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Fehler: ' . $e->getMessage(),
            ]);
        }
    }

    public function testSmb(Request $request): JsonResponse
    {
        $request->validate([
            'unc_path' => ['required', 'string', 'max:1000'],
        ]);

        $uncPath  = $request->input('unc_path');
        $settings = BaraSettings::getSingleton();

        // Windows: net use vorab; Linux: smbclient nutzt Credentials inline via testPath
        $shareRoot = null;
        if ($settings->hasSmbCredentials() && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $shareRoot = $this->scanner->getShareRoot($uncPath);
            $user      = $settings->smb_domain
                ? $settings->smb_domain . '\\' . $settings->smb_username
                : $settings->smb_username;

            $conn = $this->scanner->netUseConnect($shareRoot, $user, $settings->smb_password);
            if (!$conn['ok']) {
                return response()->json(['ok' => false, 'message' => $conn['message']]);
            }
        }

        try {
            $result = $this->scanner->testPath($uncPath);
        } finally {
            if ($shareRoot) {
                $this->scanner->netUseDisconnect($shareRoot);
            }
        }

        return response()->json($result);
    }
}
