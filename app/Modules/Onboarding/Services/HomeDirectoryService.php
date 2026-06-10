<?php

namespace App\Modules\Onboarding\Services;

use App\Modules\Onboarding\Models\OnboardingSettings;

/**
 * Legt das Heimatverzeichnis eines neuen Benutzers auf einem Windows/SMB-Fileserver an
 * und setzt die NTFS-Berechtigungen (Benutzer erhält Full Control).
 *
 * Verwendet smbclient (Ordner erstellen) + smbcacls (ACL setzen) – beide im samba-client-Paket.
 */
class HomeDirectoryService
{
    private OnboardingSettings $settings;

    public function __construct()
    {
        $this->settings = OnboardingSettings::getSingleton();
    }

    public function isConfigured(): bool
    {
        return $this->settings->hasSmbCredentials();
    }

    /**
     * Erstellt den Heimatordner und setzt den Benutzer als vollberechtigten Eigentümer.
     *
     * @param  string $uncPath        Vollständiger UNC-Pfad, z.B. \\lra.lan\dfs\User\mustermannm
     * @param  string $samaccountname sAMAccountName des neuen Benutzers
     * @param  string $domain         NetBIOS-Domain, z.B. LRA
     * @return array{success: bool, output: string, error: string}
     */
    public function createDirectory(string $uncPath, string $samaccountname, string $domain): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'output' => '', 'error' => 'SMB-Zugangsdaten nicht konfiguriert (Onboarding-Einstellungen).'];
        }

        $parsed = $this->parseUncPath($uncPath);
        if (!$parsed) {
            return ['success' => false, 'output' => '', 'error' => "Ungültiger UNC-Pfad: {$uncPath}"];
        }

        ['server' => $server, 'share' => $share, 'folder' => $folder] = $parsed;

        // Schritt 1: Ordner anlegen
        $mkResult = $this->smbMkdir($server, $share, $folder);
        if (!$mkResult['success']) {
            // Bereits vorhanden ist kein Fehler
            if (!str_contains(strtolower($mkResult['error']), 'already exist')
             && !str_contains(strtolower($mkResult['error']), 'nt_status_object_name_collision')) {
                return $mkResult;
            }
        }

        // Schritt 2: ACL setzen – Benutzer erhält Full Control (OI|CI = vererbt auf Inhalte)
        $aclResult = $this->smbSetAcl($server, $share, $folder, $domain, $samaccountname);

        return [
            'success' => true, // Ordner existiert; ACL-Fehler werden geloggt aber nicht als fatal gewertet
            'output'  => trim(($mkResult['output'] ?? '') . "\n" . ($aclResult['output'] ?? '')),
            'error'   => $aclResult['success'] ? '' : 'ACL-Fehler (Ordner vorhanden): ' . $aclResult['error'],
        ];
    }

    /**
     * Testet ob die SMB-Verbindung mit den gespeicherten Zugangsdaten funktioniert.
     * @return array{success: bool, message: string}
     */
    public function testConnection(string $uncPath): array
    {
        $parsed = $this->parseUncPath($uncPath);
        if (!$parsed) {
            return ['success' => false, 'message' => "Ungültiger UNC-Pfad: {$uncPath}"];
        }

        ['server' => $server, 'share' => $share] = $parsed;

        if (PHP_OS_FAMILY === 'Windows') {
            $path = "\\\\{$server}\\{$share}";
            return ['success' => @is_dir($path), 'message' => @is_dir($path) ? "OK: {$path} erreichbar." : "Nicht erreichbar: {$path}"];
        }

        $cmd  = 'smbclient ' . escapeshellarg("//{$server}/{$share}") . ' ' . $this->authArgs() . ' -c ' . escapeshellarg('ls') . ' 2>&1';
        exec($cmd, $out, $ret);
        $output = trim(implode("\n", $out));

        $ok = $ret === 0 && !$this->outputHasError($output);
        return [
            'success' => $ok,
            'message' => $ok
                ? "OK: Verbindung zu //{$server}/{$share} erfolgreich."
                : "Fehler: " . $output,
        ];
    }

    // ─── private ─────────────────────────────────────────────────────────────

    /**
     * Zerlegt \\server\share\sub\path\folder in seine Bestandteile.
     * Gibt ['server', 'share', 'folder'] zurück, wobei folder der vollständige
     * Pfad innerhalb des Shares ist (z.B. "User/mustermannm").
     */
    private function parseUncPath(string $uncPath): ?array
    {
        // Normalisiere Backslashes → Forward Slashes, entferne führende //
        $path  = ltrim(str_replace('\\', '/', $uncPath), '/');
        $parts = array_filter(explode('/', $path));
        $parts = array_values($parts);

        if (count($parts) < 3) {
            return null;
        }

        $server = $parts[0];
        $share  = $parts[1];
        $folder = implode('/', array_slice($parts, 2));

        return compact('server', 'share', 'folder');
    }

    /** Erstellt das Verzeichnis über smbclient. */
    private function smbMkdir(string $server, string $share, string $folder): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $path = "\\\\{$server}\\{$share}\\" . str_replace('/', '\\', $folder);
            $ok   = @mkdir($path, 0777, true);
            return ['success' => $ok, 'output' => $ok ? "Ordner {$path} erstellt." : '', 'error' => $ok ? '' : error_get_last()['message'] ?? 'mkdir fehlgeschlagen'];
        }

        // smbclient mkdir unterstützt keine Pfade mit Unterordnern direkt.
        // Lösung: zuerst in das übergeordnete Verzeichnis wechseln, dann nur den letzten
        // Ordner anlegen. Bei einfachen Pfaden (kein Unterordner) entfällt das cd.
        $parts  = explode('/', $folder);
        $leaf   = array_pop($parts);
        $smbCmd = count($parts) > 0
            ? 'cd ' . implode('/', $parts) . '; mkdir ' . $leaf
            : 'mkdir ' . $leaf;

        $cmd = 'smbclient ' . escapeshellarg("//{$server}/{$share}") . ' ' . $this->authArgs()
             . ' -c ' . escapeshellarg($smbCmd) . ' 2>&1';

        \Illuminate\Support\Facades\Log::debug("HomeDirectoryService smbMkdir: {$cmd}");
        exec($cmd, $out, $ret);
        $output = trim(implode("\n", $out));
        \Illuminate\Support\Facades\Log::debug("HomeDirectoryService smbMkdir result (exit={$ret}): {$output}");

        // smbclient gibt häufig Exit-Code 0 zurück auch wenn der interne Befehl
        // fehlschlug. Daher die Ausgabe zusätzlich auf NT_STATUS-Fehler prüfen.
        if ($this->outputHasError($output)) {
            return ['success' => false, 'output' => '', 'error' => $output];
        }

        if ($ret !== 0) {
            return ['success' => false, 'output' => '', 'error' => $output ?: "smbclient Exit-Code {$ret}"];
        }

        // Doppelt absichern: prüfen ob der Ordner danach wirklich existiert.
        $exists = $this->smbExists($server, $share, $folder);
        if (!$exists) {
            return ['success' => false, 'output' => '', 'error' => "smbclient meldete Erfolg, aber /{$folder} ist auf //{$server}/{$share} nicht auffindbar. Ausgabe: {$output}"];
        }

        return ['success' => true, 'output' => "Ordner /{$folder} auf //{$server}/{$share} erstellt.", 'error' => ''];
    }

    /**
     * Prüft ob ein Pfad auf dem Share existiert (ls-Test).
     * Gibt true zurück wenn der Ordner vorhanden ist.
     */
    private function smbExists(string $server, string $share, string $folder): bool
    {
        $cmd = 'smbclient ' . escapeshellarg("//{$server}/{$share}") . ' ' . $this->authArgs()
             . ' -c ' . escapeshellarg("ls {$folder}") . ' 2>&1';

        exec($cmd, $out, $ret);
        $output = trim(implode("\n", $out));
        \Illuminate\Support\Facades\Log::debug("HomeDirectoryService smbExists (exit={$ret}): {$output}");

        // Wenn ls kein NT_STATUS-Fehler liefert und exit 0 ist → Ordner existiert
        return $ret === 0 && !$this->outputHasError($output);
    }

    /**
     * Gibt true zurück wenn die smbclient-Ausgabe einen echten Fehler enthält
     * (NT_STATUS_* außer OBJECT_NAME_COLLISION = Ordner bereits vorhanden).
     */
    private function outputHasError(string $output): bool
    {
        $lower = strtolower($output);
        if (!str_contains($lower, 'nt_status_')) {
            return false;
        }
        // COLLISION bedeutet "Ordner existiert bereits" – kein Fehler
        return !str_contains($lower, 'nt_status_object_name_collision');
    }

    /** Setzt Full-Control-ACL auf das Verzeichnis via smbcacls. */
    private function smbSetAcl(string $server, string $share, string $folder, string $domain, string $user): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: icacls
            $path = "\\\\{$server}\\{$share}\\" . str_replace('/', '\\', $folder);
            $cmd  = 'icacls ' . escapeshellarg($path) . ' /grant ' . escapeshellarg("{$domain}\\{$user}:(OI)(CI)F") . ' /T 2>&1';
            exec($cmd, $out, $ret);
            return ['success' => $ret === 0, 'output' => implode("\n", $out), 'error' => $ret !== 0 ? implode("\n", $out) : ''];
        }

        // Linux: smbcacls – prüfen ob vorhanden
        if (!$this->commandExists('smbcacls')) {
            return ['success' => false, 'output' => '', 'error' => 'smbcacls nicht gefunden (apt install smbclient).'];
        }

        // ACE: DOMAIN\user bekommt ALLOWED/OI|CI/FULL (Object Inherit + Container Inherit)
        $ace = "{$domain}\\{$user}:ALLOWED/OI|CI/FULL";
        $cmd = 'smbcacls ' . escapeshellarg("//{$server}/{$share}") . ' ' . escapeshellarg($folder)
             . ' ' . $this->authArgs()
             . ' --add ' . escapeshellarg($ace) . ' 2>&1';

        \Illuminate\Support\Facades\Log::debug("HomeDirectoryService smbSetAcl: {$cmd}");
        exec($cmd, $out, $ret);
        $output = trim(implode("\n", $out));
        \Illuminate\Support\Facades\Log::debug("HomeDirectoryService smbSetAcl result (exit={$ret}): {$output}");

        $failed = $ret !== 0 || $this->outputHasError($output);
        return [
            'success' => !$failed,
            'output'  => !$failed ? "ACL {$ace} gesetzt." : '',
            'error'   => $failed ? $output : '',
        ];
    }

    /** Baut den Authentifizierungsteil für smbclient/smbcacls. */
    private function authArgs(): string
    {
        $user = $this->settings->smb_user;
        $pass = $this->settings->smb_password ?? '';
        return '-U ' . escapeshellarg("{$user}%{$pass}");
    }

    private function commandExists(string $cmd): bool
    {
        $result = shell_exec('command -v ' . escapeshellarg($cmd) . ' 2>/dev/null');
        return !empty(trim((string) $result));
    }
}
