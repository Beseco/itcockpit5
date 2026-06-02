<?php

namespace App\Modules\Baramundi\Services;

use App\Modules\Baramundi\Models\BaraSettings;
use App\Modules\Baramundi\Models\WatchedPackage;

class SmbScannerService
{
    // ──────────────────────────────────────────────────────────────────────────
    // Öffentliche API
    // ──────────────────────────────────────────────────────────────────────────

    public function isReachable(WatchedPackage $pkg): bool
    {
        if ($this->isWindows()) {
            return @is_dir($pkg->getUncPath());
        }

        ['server' => $server, 'share' => $share, 'subpath' => $subpath] = $this->parseUncPath($pkg->getUncPath());
        $cmd = $this->buildSmbclientCmd($server, $share, '-c ' . escapeshellarg('ls ' . str_replace('\\', '/', $subpath)));
        exec($cmd . ' 2>&1', $out, $ret);
        return $ret === 0;
    }

    /**
     * @throws \RuntimeException wenn der Pfad nicht gelesen werden kann.
     */
    public function listSubdirectories(WatchedPackage $pkg): array
    {
        if ($this->isWindows()) {
            return $this->listSubdirectoriesWindows($pkg->getUncPath());
        }

        ['server' => $server, 'share' => $share, 'subpath' => $subpath] = $this->parseUncPath($pkg->getUncPath());
        $smbPath = $subpath ? str_replace('\\', '/', $subpath) . '/*' : '*';
        $cmd = $this->buildSmbclientCmd($server, $share, '-c ' . escapeshellarg("ls {$smbPath}"));
        exec($cmd . ' 2>&1', $out, $ret);

        if ($ret !== 0) {
            $error = $this->extractSmbError($out);
            throw new \RuntimeException("SMB-Fehler: {$error}");
        }

        return $this->parseSmbclientLs($out);
    }

    public function findNewestVersion(array $folderNames): ?string
    {
        $versions = array_filter($folderNames, fn(string $n) => $this->looksLikeVersion($n));

        if (empty($versions)) {
            return null;
        }

        usort($versions, fn($a, $b) => version_compare($a, $b));

        return end($versions);
    }

    public function isNewerThan(string $newVersion, ?string $knownVersion): bool
    {
        if ($knownVersion === null) {
            return true;
        }
        return version_compare($newVersion, $knownVersion, '>');
    }

    /**
     * Testet einen UNC-Pfad mit den gespeicherten Zugangsdaten.
     * Gibt ['ok' => bool, 'message' => string] zurück.
     */
    public function testPath(string $uncPath): array
    {
        if (!str_starts_with($uncPath, '\\\\')) {
            return ['ok' => false, 'message' => 'Ungültiger UNC-Pfad. Muss mit \\\\ beginnen.'];
        }

        if ($this->isWindows()) {
            if (@is_dir($uncPath)) {
                return ['ok' => true, 'message' => "Pfad erreichbar: {$uncPath}"];
            }
            return ['ok' => false, 'message' => "Pfad nicht erreichbar oder kein Zugriff: {$uncPath}"];
        }

        // Linux: smbclient
        ['server' => $server, 'share' => $share, 'subpath' => $subpath] = $this->parseUncPath($uncPath);
        $smbPath = $subpath ? str_replace('\\', '/', $subpath) : '';
        $cmd = $this->buildSmbclientCmd($server, $share, '-c ' . escapeshellarg("ls {$smbPath}"));
        exec($cmd . ' 2>&1', $out, $ret);

        if ($ret === 0) {
            return ['ok' => true, 'message' => "Pfad erreichbar: {$uncPath}"];
        }

        $error = $this->extractSmbError($out);
        return ['ok' => false, 'message' => "Nicht erreichbar: {$error}"];
    }

    /**
     * Windows: Verbindung per "net use" herstellen.
     * Linux: Verbindung testen (Credentials sind per smbclient immer inline).
     */
    public function netUseConnect(string $serverPath, string $username, string $password): array
    {
        if ($this->isWindows()) {
            if (!str_starts_with($serverPath, '\\\\')) {
                return ['ok' => false, 'message' => 'Ungültiger Server-Pfad.'];
            }
            exec('net use ' . escapeshellarg($serverPath) . ' /delete /yes 2>nul');
            $cmd = sprintf(
                'net use %s %s /user:%s /persistent:no 2>&1',
                escapeshellarg($serverPath),
                escapeshellarg($password),
                escapeshellarg($username)
            );
            exec($cmd, $out, $ret);
            return [
                'ok'      => ($ret === 0),
                'message' => $ret === 0
                    ? "Verbindung hergestellt: {$serverPath}"
                    : "net use fehlgeschlagen (Exit {$ret}): " . implode(' ', $out),
            ];
        }

        // Linux: Verbindungstest via smbclient
        ['server' => $server, 'share' => $share] = $this->parseUncPath($serverPath);
        $userArg = str_contains($username, '\\') ? $username : $username;
        $cmd = 'smbclient ' . escapeshellarg("//{$server}/{$share}")
            . ' -U ' . escapeshellarg("{$username}%{$password}")
            . ' -c ' . escapeshellarg('ls') . ' 2>&1';
        exec($cmd, $out, $ret);
        $msg = $ret === 0
            ? "Verbindung erfolgreich: //{$server}/{$share}"
            : "Verbindung fehlgeschlagen: " . $this->extractSmbError($out);
        return ['ok' => $ret === 0, 'message' => $msg];
    }

    /** Windows: net use trennen. Linux: no-op. */
    public function netUseDisconnect(string $serverPath): void
    {
        if ($this->isWindows()) {
            exec('net use ' . escapeshellarg($serverPath) . ' /delete /yes 2>nul');
        }
        // Linux: Verbindung wird pro smbclient-Aufruf hergestellt, kein Cleanup nötig
    }

    public function getShareRoot(string $uncPath): string
    {
        $parts = explode('\\', ltrim($uncPath, '\\'));
        if (count($parts) >= 2) {
            return '\\\\' . $parts[0] . '\\' . $parts[1];
        }
        return $uncPath;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Interne Hilfsmethoden
    // ──────────────────────────────────────────────────────────────────────────

    private function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Zerlegt \\server\share\sub\path in seine Teile.
     */
    private function parseUncPath(string $uncPath): array
    {
        $clean = ltrim(str_replace('/', '\\', $uncPath), '\\');
        $parts = explode('\\', $clean, 3);
        return [
            'server'  => $parts[0] ?? '',
            'share'   => $parts[1] ?? '',
            'subpath' => $parts[2] ?? '',
        ];
    }

    /**
     * Baut den smbclient-Befehl mit den gespeicherten Zugangsdaten.
     */
    private function buildSmbclientCmd(string $server, string $share, string $extra = ''): string
    {
        $settings = BaraSettings::getSingleton();
        $target   = escapeshellarg("//{$server}/{$share}");

        if ($settings->hasSmbCredentials()) {
            $user    = $settings->smb_username;
            $pass    = $settings->smb_password;
            $userArg = $settings->smb_domain
                ? "{$settings->smb_domain}\\{$user}"
                : $user;
            $auth = '-U ' . escapeshellarg("{$userArg}%{$pass}");
        } else {
            $auth = '-N'; // Kein Passwort (Gastzugriff)
        }

        return trim("smbclient {$target} {$auth} {$extra}");
    }

    /**
     * Parst die Ausgabe von "smbclient ls" und gibt Verzeichnisnamen zurück.
     *
     * Smbclient-Ausgabeformat:
     *   "  name                           D        0  Mon Jun  2 14:23 2026"
     */
    private function parseSmbclientLs(array $lines): array
    {
        $dirs = [];
        foreach ($lines as $line) {
            // Nur Zeilen mit führenden Leerzeichen (Dateieinträge)
            if (!preg_match('/^\s{2}/', $line)) {
                continue;
            }
            // "blocks of size"-Zeile überspringen
            if (str_contains($line, 'blocks of size')) {
                continue;
            }
            // Nur Verzeichnisse (Attribut D)
            if (!preg_match('/\bD\b/', $line)) {
                continue;
            }
            // Name extrahieren: alles ab Position 2 bis zur Attribut-Spalte
            $content = substr($line, 2);
            if (preg_match('/^(.+?)\s{2,}[DAHNRS]+\s+\d+\s+/', $content, $m)) {
                $name = trim($m[1]);
                if ($name !== '.' && $name !== '..') {
                    $dirs[] = $name;
                }
            }
        }
        return $dirs;
    }

    /**
     * Listet Unterverzeichnisse per PHP-native-Dateifunktionen (Windows).
     */
    private function listSubdirectoriesWindows(string $path): array
    {
        $handle = @opendir($path);

        if ($handle === false) {
            throw new \RuntimeException("UNC-Pfad nicht erreichbar oder kein Zugriff: {$path}");
        }

        $dirs = [];
        while (($entry = readdir($handle)) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (@is_dir($path . DIRECTORY_SEPARATOR . $entry)) {
                $dirs[] = $entry;
            }
        }
        closedir($handle);

        return $dirs;
    }

    /**
     * Extrahiert die relevante Fehlermeldung aus smbclient-Ausgabe.
     */
    private function extractSmbError(array $lines): string
    {
        foreach ($lines as $line) {
            if (str_contains($line, 'NT_STATUS_') || str_contains($line, 'Error')) {
                return trim($line);
            }
        }
        return implode(' ', array_filter(array_map('trim', $lines)));
    }

    private function looksLikeVersion(string $name): bool
    {
        // Erlaubt: 15.78.3  |  15.78.3-x64  |  8.0.451  |  2026.1.0-arm64
        // Blockiert: 15.x-x64  |  README  |  backup_old
        return (bool) preg_match('/^\d[\d.]*\.\d+(-[a-zA-Z0-9]+)?$/', $name);
    }
}
