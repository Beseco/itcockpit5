<?php

namespace App\Modules\Backup\Console\Commands;

use App\Modules\Backup\Models\BackupSettings;
use Illuminate\Console\Command;

class BackupCreateCommand extends Command
{
    protected $signature   = 'backup:create';
    protected $description = 'Erstellt ein lokales Backup (Datenbank + Dateien)';

    public function handle(): int
    {
        $settings = BackupSettings::getSingleton();
        $name     = 'backup_' . now()->format('Y-m-d_His');
        $dir      = storage_path("app/backups/{$name}");

        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            $this->error("Backup-Verzeichnis konnte nicht erstellt werden: {$dir}");
            return 1;
        }

        $this->info("Starte Backup '{$name}'…");

        try {
            if ($settings->backup_db) {
                $this->info('  → Datenbank sichern…');
                $this->dumpDatabase($dir);
                $this->info('  ✓ Datenbank gesichert');
            }

            if ($settings->backup_files) {
                $this->info('  → Dateien sichern…');
                $this->backupFiles($dir);
                $this->info('  ✓ Dateien gesichert');
            }

            file_put_contents("{$dir}/info.json", json_encode([
                'name'       => $name,
                'created_at' => now()->toIso8601String(),
                'backup_db'  => $settings->backup_db,
                'backup_files' => $settings->backup_files,
            ], JSON_PRETTY_PRINT));

            $this->cleanupOldBackups($settings->retention_count);

            $this->info("Backup '{$name}' erfolgreich abgeschlossen.");
            return 0;

        } catch (\Throwable $e) {
            // Unvollständiges Backup-Verzeichnis entfernen
            $this->exec("rm -rf " . escapeshellarg($dir));
            $this->error('Backup fehlgeschlagen: ' . $e->getMessage());
            return 1;
        }
    }

    private function dumpDatabase(string $dir): void
    {
        $cfg      = config('database.connections.' . config('database.default'));
        $host     = $cfg['host'] ?? '127.0.0.1';
        $port     = $cfg['port'] ?? 3306;
        $database = $cfg['database'];
        $username = $cfg['username'];
        $password = $cfg['password'] ?? '';

        // Credentials in temporäre .my.cnf schreiben (vermeidet Shell-Expansion von Sonderzeichen)
        $mycnf = tempnam(sys_get_temp_dir(), 'bk_my_');
        file_put_contents($mycnf,
            "[client]\nuser={$username}\npassword={$password}\nhost={$host}\nport={$port}\n");
        chmod($mycnf, 0600);

        $outFile = "{$dir}/database.sql.gz";
        $db      = escapeshellarg($database);
        $errFile = tempnam(sys_get_temp_dir(), 'bk_err_');

        // Dump zuerst als .sql, dann gzippen – so kann der Exit-Code von mysqldump
        // korrekt geprüft werden (bei Pipe-Verkettung würde nur gzip's Code ankommen)
        $sqlFile = "{$dir}/database.sql";
        exec(
            "mysqldump --defaults-extra-file={$mycnf} --single-transaction --no-tablespaces {$db} > " . escapeshellarg($sqlFile) . " 2>{$errFile}",
            $output,
            $ret
        );

        unlink($mycnf);

        if ($ret !== 0 || !file_exists($sqlFile) || filesize($sqlFile) < 100) {
            $err = file_exists($errFile) ? trim(file_get_contents($errFile)) : 'unbekannt';
            @unlink($errFile);
            @unlink($sqlFile);
            throw new \RuntimeException("mysqldump fehlgeschlagen: {$err}");
        }

        @unlink($errFile);

        // Komprimieren
        exec("gzip -f " . escapeshellarg($sqlFile), $gzOut, $gzRet);
        // gzip benennt die Datei zu database.sql.gz um
        if ($gzRet !== 0 || !file_exists($outFile)) {
            throw new \RuntimeException("gzip fehlgeschlagen (Exit-Code {$gzRet}).");
        }
    }

    private function backupFiles(string $dir): void
    {
        $storageDir = storage_path('app/public');

        if (!is_dir($storageDir)) {
            $this->warn('  storage/app/public nicht gefunden – überspringe Datei-Backup.');
            return;
        }

        $outFile = escapeshellarg("{$dir}/storage.tar.gz");
        $srcBase = escapeshellarg(storage_path('app'));

        exec("tar -czf {$outFile} -C {$srcBase} public 2>/dev/null", $output, $ret);

        if ($ret !== 0) {
            throw new \RuntimeException("tar-Archivierung der Dateien fehlgeschlagen (Exit-Code {$ret}).");
        }
    }

    private function cleanupOldBackups(int $keep): void
    {
        $backupsDir = storage_path('app/backups');
        $dirs       = glob("{$backupsDir}/backup_*", GLOB_ONLYDIR);

        if (!$dirs) {
            return;
        }

        rsort($dirs); // neueste zuerst

        foreach (array_slice($dirs, $keep) as $old) {
            $this->exec("rm -rf " . escapeshellarg($old));
            $this->line('  Altes Backup gelöscht: ' . basename($old));
        }
    }

    /** Wrapper für exec() – erleichtert Mocking in Tests */
    private function exec(string $cmd): void
    {
        exec($cmd);
    }
}
