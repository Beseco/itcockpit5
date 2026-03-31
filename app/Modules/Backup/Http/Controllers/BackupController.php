<?php

namespace App\Modules\Backup\Http\Controllers;

use App\Modules\Backup\Models\BackupSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function index()
    {
        $backups  = $this->listBackups();
        $settings = BackupSettings::getSingleton();

        return view('backup::index', compact('backups', 'settings'));
    }

    public function store(): RedirectResponse
    {
        try {
            Artisan::call('backup:create');
            $output = trim(Artisan::output());

            if (str_contains($output, 'fehlgeschlagen') || str_contains($output, 'ERROR')) {
                return back()->with('error', 'Backup fehlgeschlagen: ' . $output);
            }

            return back()->with('success', 'Backup wurde erfolgreich erstellt.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Backup fehlgeschlagen: ' . $e->getMessage());
        }
    }

    public function download(string $name, string $type): BinaryFileResponse
    {
        $this->validateBackupName($name);

        if (!in_array($type, ['db', 'files'])) {
            abort(404);
        }

        $dir  = storage_path("app/backups/{$name}");
        $file = $type === 'db'
            ? "{$dir}/database.sql.gz"
            : "{$dir}/storage.tar.gz";

        if (!file_exists($file)) {
            abort(404);
        }

        $filename = $type === 'db'
            ? "{$name}_database.sql.gz"
            : "{$name}_storage.tar.gz";

        return response()->download($file, $filename);
    }

    public function destroy(string $name): RedirectResponse
    {
        $this->validateBackupName($name);

        $dir = storage_path("app/backups/{$name}");

        if (!is_dir($dir)) {
            abort(404);
        }

        exec("rm -rf " . escapeshellarg($dir));

        return back()->with('success', "Backup '{$name}' wurde gelöscht.");
    }

    // ── Hilfsmethoden ────────────────────────────────────────────────────────

    private function listBackups(): array
    {
        $backupsDir = storage_path('app/backups');
        $backups    = [];

        if (!is_dir($backupsDir)) {
            return $backups;
        }

        $dirs = glob("{$backupsDir}/backup_*", GLOB_ONLYDIR) ?: [];
        rsort($dirs);

        foreach ($dirs as $dir) {
            $name = basename($dir);
            $info = [];

            if (file_exists("{$dir}/info.json")) {
                $info = json_decode(file_get_contents("{$dir}/info.json"), true) ?? [];
            }

            $dbFile    = "{$dir}/database.sql.gz";
            $filesFile = "{$dir}/storage.tar.gz";

            $backups[] = [
                'name'       => $name,
                'created_at' => isset($info['created_at'])
                    ? \Carbon\Carbon::parse($info['created_at'])
                    : null,
                'has_db'     => file_exists($dbFile),
                'has_files'  => file_exists($filesFile),
                'db_size'    => file_exists($dbFile)    ? filesize($dbFile)    : 0,
                'files_size' => file_exists($filesFile) ? filesize($filesFile) : 0,
            ];
        }

        return $backups;
    }

    private function validateBackupName(string $name): void
    {
        if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{6}$/', $name)) {
            abort(404);
        }
    }
}
