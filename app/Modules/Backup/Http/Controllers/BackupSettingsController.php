<?php

namespace App\Modules\Backup\Http\Controllers;

use App\Modules\Backup\Models\BackupSettings;
use App\Modules\Backup\Services\BackupSmbService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BackupSettingsController extends Controller
{
    public function index()
    {
        $settings = BackupSettings::getSingleton();
        return view('backup::settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'schedule_time'      => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'retention_count'    => ['required', 'integer', 'min:1', 'max:365'],
            'backup_db'          => ['nullable', 'boolean'],
            'backup_files'       => ['nullable', 'boolean'],
            'backup_exports'     => ['nullable', 'boolean'],
            'backup_exports_all' => ['nullable', 'boolean'],
            'smb_enabled'        => ['nullable', 'boolean'],
            'smb_server'         => ['nullable', 'string', 'max:255'],
            'smb_share'          => ['nullable', 'string', 'max:255'],
            'smb_domain'         => ['nullable', 'string', 'max:255'],
            'smb_username'       => ['nullable', 'string', 'max:255'],
            'smb_password'       => ['nullable', 'string', 'max:500'],
            'smb_path'           => ['nullable', 'string', 'max:500'],
        ]);

        $settings = BackupSettings::getSingleton();
        $data = [
            'schedule_time'      => $request->schedule_time,
            'retention_count'    => $request->retention_count,
            'backup_db'          => $request->boolean('backup_db'),
            'backup_files'       => $request->boolean('backup_files'),
            'backup_exports'     => $request->boolean('backup_exports'),
            'backup_exports_all' => $request->boolean('backup_exports_all'),
            'smb_enabled'        => $request->boolean('smb_enabled'),
            'smb_server'         => $request->smb_server,
            'smb_share'          => $request->smb_share,
            'smb_domain'         => $request->smb_domain,
            'smb_username'       => $request->smb_username,
            'smb_path'           => $request->smb_path,
        ];

        // Passwort nur überschreiben wenn neu eingegeben
        if ($request->filled('smb_password')) {
            $data['smb_password'] = $request->smb_password;
        }

        $settings->fill($data)->save();

        return redirect()->route('backup.settings')
            ->with('success', 'Einstellungen gespeichert.');
    }

    public function testSmb(Request $request): RedirectResponse
    {
        try {
            $settings = BackupSettings::getSingleton();
            app(BackupSmbService::class)->testConnection($settings);
            return redirect()->route('backup.settings')
                ->with('success', '✓ SMB-Verbindung erfolgreich: Zugriff auf //' . $settings->smb_server . '/' . $settings->smb_share . ' bestätigt.');
        } catch (\Throwable $e) {
            return redirect()->route('backup.settings')
                ->with('error', 'SMB-Verbindung fehlgeschlagen: ' . $e->getMessage());
        }
    }
}
