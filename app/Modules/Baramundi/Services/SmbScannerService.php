<?php

namespace App\Modules\Baramundi\Services;

use App\Modules\Baramundi\Models\WatchedPackage;

class SmbScannerService
{
    /**
     * Prüft ob der UNC-Pfad des Pakets erreichbar ist.
     * Gibt false zurück ohne Exception, wenn nicht erreichbar.
     */
    public function isReachable(WatchedPackage $pkg): bool
    {
        return @is_dir($pkg->getUncPath());
    }

    /**
     * Listet alle unmittelbaren Unterverzeichnisse des UNC-Pfads auf.
     * Gibt nur Ordnernamen zurück (keine vollständigen Pfade).
     *
     * @throws \RuntimeException wenn der Pfad nicht geöffnet werden kann.
     */
    public function listSubdirectories(WatchedPackage $pkg): array
    {
        $path = $pkg->getUncPath();

        $handle = @opendir($path);

        if ($handle === false) {
            throw new \RuntimeException(
                "UNC-Pfad nicht erreichbar oder kein Zugriff: {$path}"
            );
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
     * Ermittelt die höchste semantische Version aus einer Liste von Ordnernamen.
     * Ordner, die nicht wie eine Version aussehen, werden ignoriert.
     * Gibt null zurück wenn keine gültige Version gefunden wurde.
     */
    public function findNewestVersion(array $folderNames): ?string
    {
        $versions = array_filter($folderNames, fn(string $n) => $this->looksLikeVersion($n));

        if (empty($versions)) {
            return null;
        }

        usort($versions, fn($a, $b) => version_compare($a, $b));

        return end($versions);
    }

    /**
     * Prüft ob newVersion wirklich neuer als knownVersion ist.
     * null als knownVersion gilt immer als "neue Version erkannt".
     */
    public function isNewerThan(string $newVersion, ?string $knownVersion): bool
    {
        if ($knownVersion === null) {
            return true;
        }
        return version_compare($newVersion, $knownVersion, '>');
    }

    /**
     * Testet ob ein UNC-Pfad erreichbar ist (für den Einstellungs-Test-Endpunkt).
     * Gibt ['ok' => bool, 'message' => string] zurück.
     */
    public function testPath(string $uncPath): array
    {
        if (!str_starts_with($uncPath, '\\\\')) {
            return ['ok' => false, 'message' => 'Ungültiger UNC-Pfad. Muss mit \\\\ beginnen.'];
        }

        if (@is_dir($uncPath)) {
            return ['ok' => true, 'message' => "Pfad erreichbar: {$uncPath}"];
        }

        return ['ok' => false, 'message' => "Pfad nicht erreichbar oder kein Zugriff: {$uncPath}"];
    }

    /**
     * Authentifiziert eine SMB-Freigabe via "net use" mit Benutzername und Passwort.
     * Wird am Scan-Start aufgerufen wenn Zugangsdaten konfiguriert sind.
     *
     * @param string $serverPath  UNC-Server-Root, z.B. \\Bara-01 (ohne Unterordner)
     * @param string $username    Benutzername (ggf. mit Domäne: DOMAIN\user)
     * @param string $password    Passwort im Klartext
     * @return array{ok: bool, message: string}
     */
    public function netUseConnect(string $serverPath, string $username, string $password): array
    {
        if (!str_starts_with($serverPath, '\\\\')) {
            return ['ok' => false, 'message' => 'Ungültiger Server-Pfad.'];
        }

        // Zuerst alte Verbindung trennen (Fehler ignorieren)
        exec('net use ' . escapeshellarg($serverPath) . ' /delete /yes 2>nul');

        $cmd = sprintf(
            'net use %s %s /user:%s /persistent:no 2>&1',
            escapeshellarg($serverPath),
            escapeshellarg($password),
            escapeshellarg($username)
        );

        exec($cmd, $out, $ret);
        $output = implode(' ', $out);

        return [
            'ok'      => ($ret === 0),
            'message' => $ret === 0
                ? "Verbindung hergestellt: {$serverPath}"
                : "net use fehlgeschlagen (Exit {$ret}): {$output}",
        ];
    }

    /**
     * Trennt eine per "net use" hergestellte Verbindung wieder.
     */
    public function netUseDisconnect(string $serverPath): void
    {
        exec('net use ' . escapeshellarg($serverPath) . ' /delete /yes 2>nul');
    }

    /**
     * Extrahiert den Server-Root aus einem UNC-Pfad: \\server\share → \\server\share
     * (nur Server + erste Freigabe, für "net use")
     */
    public function getShareRoot(string $uncPath): string
    {
        // \\server\share\sub\path → \\server\share
        $parts = explode('\\', ltrim($uncPath, '\\'));
        if (count($parts) >= 2) {
            return '\\\\' . $parts[0] . '\\' . $parts[1];
        }
        return $uncPath;
    }

    /**
     * Prüft ob ein Ordnername wie eine Versionsnummer aussieht.
     * Erwartet: mindestens zwei durch Punkte getrennte Zahlengruppen.
     * Beispiele: 15.68.5, 8.0.451, 2026.1.0 → true
     * Beispiele: 15.x-x64, README, backup_old → false
     */
    private function looksLikeVersion(string $name): bool
    {
        return (bool) preg_match('/^\d[\d.]*\.\d+$/', $name);
    }
}
