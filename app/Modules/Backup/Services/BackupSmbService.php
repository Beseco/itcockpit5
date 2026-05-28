<?php

namespace App\Modules\Backup\Services;

use App\Modules\Backup\Models\BackupSettings;

class BackupSmbService
{
    /**
     * Packt das Backup-Verzeichnis zu einem .tar.gz und überträgt es via smbclient.
     * Die lokale Archiv-Datei wird nach erfolgreichem Upload gelöscht.
     */
    public function upload(string $backupDir, string $backupName, callable $log = null): void
    {
        $settings    = BackupSettings::getSingleton();
        $archiveName = "{$backupName}.tar.gz";
        $archivePath = storage_path("app/backups/{$archiveName}");

        // ── 1. Backup-Verzeichnis als einzelnes Archiv packen ────────────────
        exec(
            "tar -czf " . escapeshellarg($archivePath) .
            " -C " . escapeshellarg(dirname($backupDir)) .
            " " . escapeshellarg(basename($backupDir)) . " 2>&1",
            $tarOut, $tarRet
        );

        if ($tarRet !== 0 || !file_exists($archivePath)) {
            throw new \RuntimeException(
                "Archivierung für SMB fehlgeschlagen: " . implode("\n", $tarOut)
            );
        }

        // ── 2. Credentials in temporäre Datei schreiben ──────────────────────
        $credFile = tempnam(sys_get_temp_dir(), 'smb_cred_');
        $credContent = "username = {$settings->smb_username}\n"
                     . "password = {$settings->smb_password}\n";
        if ($settings->smb_domain) {
            $credContent .= "domain = {$settings->smb_domain}\n";
        }
        file_put_contents($credFile, $credContent);
        chmod($credFile, 0600);

        // ── 3. Upload via smbclient ───────────────────────────────────────────
        $server     = $settings->smb_server;
        $share      = $settings->smb_share;
        $remotePath = trim($settings->smb_path ?? '', '/\\');

        $cdPart  = $remotePath ? "mkdir \"{$remotePath}\"; cd \"{$remotePath}\"; " : '';
        $putPart = "put \"{$archivePath}\" \"{$archiveName}\"";

        exec(
            "smbclient //{$server}/{$share} -A " . escapeshellarg($credFile) .
            " -c " . escapeshellarg("{$cdPart}{$putPart}") . " 2>&1",
            $smbOut, $smbRet
        );

        unlink($credFile);
        @unlink($archivePath);

        if ($smbRet !== 0) {
            $errMsg = implode("\n", array_filter($smbOut));
            throw new \RuntimeException("SMB-Upload fehlgeschlagen: {$errMsg}");
        }

        $target = "//{$server}/{$share}" . ($remotePath ? "/{$remotePath}" : '');
        $log && $log("  ✓ SMB-Upload nach {$target}/{$archiveName}");
    }

    /**
     * Testet die SMB-Verbindung ohne eine Datei zu übertragen.
     * Gibt true zurück wenn erfolgreich, wirft RuntimeException bei Fehler.
     */
    public function testConnection(BackupSettings $settings): bool
    {
        $credFile = tempnam(sys_get_temp_dir(), 'smb_test_');
        $credContent = "username = {$settings->smb_username}\n"
                     . "password = {$settings->smb_password}\n";
        if ($settings->smb_domain) {
            $credContent .= "domain = {$settings->smb_domain}\n";
        }
        file_put_contents($credFile, $credContent);
        chmod($credFile, 0600);

        exec(
            "smbclient //{$settings->smb_server}/{$settings->smb_share}" .
            " -A " . escapeshellarg($credFile) .
            " -c 'ls' 2>&1",
            $out, $ret
        );

        unlink($credFile);

        if ($ret !== 0) {
            throw new \RuntimeException(implode("\n", array_filter($out)));
        }

        return true;
    }
}
