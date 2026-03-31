<?php

namespace App\Modules\Backup\Http\Controllers;

use App\Modules\Backup\Models\BackupSettings;
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
            'schedule_time'   => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'retention_count' => ['required', 'integer', 'min:1', 'max:365'],
            'backup_db'       => ['nullable', 'boolean'],
            'backup_files'    => ['nullable', 'boolean'],
        ]);

        $settings = BackupSettings::getSingleton();
        $settings->fill([
            'schedule_time'   => $request->schedule_time,
            'retention_count' => $request->retention_count,
            'backup_db'       => $request->boolean('backup_db'),
            'backup_files'    => $request->boolean('backup_files'),
        ]);
        $settings->save();

        return redirect()->route('backup.settings')
            ->with('success', 'Einstellungen gespeichert. Der neue Zeitplan wird beim nächsten Cache-Rebuild aktiv.');
    }
}
