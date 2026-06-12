<?php

namespace App\Modules\Baramundi\Console\Commands;

use App\Modules\Baramundi\Models\BaraSettings;
use App\Modules\Baramundi\Models\WatchedPackage;
use App\Modules\Baramundi\Services\SmbScannerService;
use Illuminate\Console\Command;

/**
 * Diagnose-Command: liest den Baramundi-Share eines Pakets aus und zeigt
 * alle Rohdaten, die der Scan-Algorithmus zur Statusbestimmung nutzt.
 *
 * Verwendung:
 *   php artisan bara:diagnose                   – alle aktiven Pakete
 *   php artisan bara:diagnose --package=3       – nur Paket mit ID 3
 *   php artisan bara:diagnose --package=3 --raw – zusätzlich rohe smbclient-Ausgabe
 */
class BaraDiagnoseCommand extends Command
{
    protected $signature = 'bara:diagnose
                            {--package= : Nur ein bestimmtes Paket prüfen (ID)}
                            {--raw      : Rohe smbclient/Dateisystem-Ausgabe anzeigen}';

    protected $description = 'Diagnose: zeigt Ordnerinhalte und Datei-Erkennung für Baramundi-Pakete';

    public function __construct(private readonly SmbScannerService $scanner)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $settings = BaraSettings::getSingleton();

        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║       Baramundi SMB-Diagnose                        ║');
        $this->info('╚══════════════════════════════════════════════════════╝');

        // SMB-Konfiguration anzeigen
        $this->info('');
        $this->line('<fg=cyan>SMB-Konfiguration:</>');
        $this->line('  Credentials konfiguriert: ' . ($settings->hasSmbCredentials() ? '<fg=green>Ja</>' : '<fg=red>Nein</>'));
        if ($settings->hasSmbCredentials()) {
            $this->line('  Domain:   ' . ($settings->smb_domain ?: '(leer)'));
            $this->line('  Benutzer: ' . $settings->smb_username);
            $this->line('  Passwort: ' . str_repeat('*', min(8, strlen($settings->smb_password ?? ''))) . ' (verborgen)');
        }
        $this->line('  OS:       ' . PHP_OS);
        $this->line('  Methode:  ' . (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'Native PHP (Windows)' : 'smbclient (Linux)'));

        $query = WatchedPackage::where('enabled', true);
        if ($id = $this->option('package')) {
            $query->where('id', (int) $id);
        }
        $packages = $query->get();

        if ($packages->isEmpty()) {
            $this->warn('Keine aktiven Pakete gefunden.');
            return 0;
        }

        foreach ($packages as $pkg) {
            $this->diagnosePaket($pkg, $settings);
        }

        return 0;
    }

    private function diagnosePaket(WatchedPackage $pkg, BaraSettings $settings): void
    {
        $this->info('');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("Paket: {$pkg->name}  (ID: {$pkg->id})");
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $this->line('<fg=cyan>Datenbank-Status:</>');
        $this->line("  Status:          {$pkg->status}  ({$pkg->getStatusLabel()})");
        $this->line("  Letzte Version:  " . ($pkg->last_known_version ?? '(keine)'));
        $this->line("  Letzter Scan:    " . ($pkg->last_scan?->format('d.m.Y H:i:s') ?? '(noch nie)'));
        $this->line("  Erkannt am:      " . ($pkg->last_detected?->format('d.m.Y H:i:s') ?? '(noch nie)'));
        $this->line("  E-Mail aktiv:    " . ($pkg->email_enabled ? 'Ja' : 'Nein'));
        $this->line("  Download-Typ:    {$pkg->download_type}");

        $this->line('');
        $this->line('<fg=cyan>UNC-Pfad:</>');
        $this->line("  {$pkg->getUncPath()}");

        // ── 1. Erreichbarkeit ─────────────────────────────────────────────────
        $this->line('');
        $this->line('<fg=cyan>[Schritt 1] Erreichbarkeit prüfen …</>');
        $reachable = $this->scanner->isReachable($pkg);
        if ($reachable) {
            $this->line('  <fg=green>✓ UNC-Pfad erreichbar</>');
        } else {
            $this->error('  ✗ UNC-Pfad NICHT erreichbar – weitere Schritte übersprungen.');
            $this->line('  Tipp: SMB-Zugangsdaten in den Baramundi-Einstellungen prüfen.');
            return;
        }

        // ── 2. Unterverzeichnisse (Versionsordner) ────────────────────────────
        $this->line('');
        $this->line('<fg=cyan>[Schritt 2] Versionsordner auflesen …</>');
        try {
            $subdirs = $this->scanner->listSubdirectories($pkg);
        } catch (\RuntimeException $e) {
            $this->error("  ✗ Fehler beim Lesen: {$e->getMessage()}");
            return;
        }

        if (empty($subdirs)) {
            $this->warn('  Keine Unterordner gefunden.');
            return;
        }

        $this->line("  Alle Unterordner (" . count($subdirs) . "):");
        foreach ($subdirs as $dir) {
            $looksLike = $this->looksLikeVersion($dir);
            $marker    = $looksLike ? '<fg=green>✓ Version</>' : '<fg=gray>  (kein Version-Pattern)</>';
            $this->line("    {$marker}  {$dir}");
        }

        // ── 3. Neueste Version ermitteln ──────────────────────────────────────
        $this->line('');
        $this->line('<fg=cyan>[Schritt 3] Neueste Version ermitteln …</>');
        $newest = $this->scanner->findNewestVersion($subdirs);
        if ($newest === null) {
            $this->warn('  Keine gültige Versionsnummer in den Ordnernamen gefunden.');
            $this->line('  Regex-Pattern: /^\d[\d.]*\.\d+(-[a-zA-Z0-9]+)?$/');
            return;
        }
        $this->line("  Neueste Version: <fg=yellow>{$newest}</>");
        $this->line("  Bekannte Version (DB): <fg=yellow>" . ($pkg->last_known_version ?? '(keine)') . "</>");
        $isNewer = $this->scanner->isNewerThan($newest, $pkg->last_known_version);
        $this->line("  version_compare → " . ($isNewer ? '<fg=blue>NEUE VERSION erkannt</>' : '<fg=green>Keine neue Version</>'));

        // ── 4. Inhalt des Versionsordners ─────────────────────────────────────
        $this->line('');
        $this->line("<fg=cyan>[Schritt 4] Inhalt des Versionsordners \"{$newest}\" …</>");
        $this->zeigeOrdnerInhalt($pkg, $newest);

        // ── 5. hasNonEmptyFile-Entscheidung ───────────────────────────────────
        $this->line('');
        $this->line('<fg=cyan>[Schritt 5] Datei-Größenprüfung (hasNonEmptyFile) …</>');
        $this->line('  Logik: Gibt es im Versionsordner mindestens eine Nicht-README-Datei > 0 Byte?');
        $hasFile = $this->scanner->hasNonEmptyFile($pkg, $newest);
        if ($hasFile) {
            $this->line("  Ergebnis: <fg=green>✓ Installationsdatei vorhanden</> → Status würde auf <fg=green>ok</> gesetzt");
        } else {
            $this->line("  Ergebnis: <fg=yellow>✗ Keine Installationsdatei (nur 0-KB-Dateien oder README)</> → Status bleibt <fg=yellow>new_version</>");
        }

        // ── 6. Falls bekannte Version != neueste: auch dort prüfen ────────────
        if (!$isNewer && $pkg->last_known_version && $pkg->status === 'new_version') {
            $this->line('');
            $this->line("<fg=cyan>[Schritt 6] Bekannte Version \"{$pkg->last_known_version}\" – Datei-Check (status=new_version) …</>");
            $this->zeigeOrdnerInhalt($pkg, $pkg->last_known_version);
            $hasFileKnown = $this->scanner->hasNonEmptyFile($pkg, $pkg->last_known_version);
            if ($hasFileKnown) {
                $this->line("  Ergebnis: <fg=green>✓ Datei vorhanden</> → Status würde auf <fg=green>ok</> gesetzt + E-Mail \"Bereitgestellt\"");
            } else {
                $this->line("  Ergebnis: <fg=yellow>✗ Datei noch nicht vorhanden</> → Status bleibt <fg=yellow>new_version</>");
            }
        }

        // ── 7. Was würde ein echter Scan jetzt tun? ───────────────────────────
        $this->line('');
        $this->line('<fg=cyan>[Zusammenfassung] Was würde bara:scan jetzt tun?</>');
        if ($isNewer) {
            if ($hasFile) {
                $this->line("  → Neue Version {$newest} erkannt, Datei vorhanden: <fg=green>status=ok</>, ggf. E-Mail \"Bereitgestellt\"");
            } else {
                $this->line("  → Neue Version {$newest} erkannt, Datei fehlt: <fg=yellow>status=new_version</>, E-Mail \"Neue Version erkannt\"");
            }
        } elseif ($pkg->status === 'new_version' && $pkg->last_known_version) {
            $hasFileKnown2 = $this->scanner->hasNonEmptyFile($pkg, $pkg->last_known_version);
            if ($hasFileKnown2) {
                $this->line("  → Datei für {$pkg->last_known_version} inzwischen vorhanden: <fg=green>status=ok</>, E-Mail \"Bereitgestellt\"");
            } else {
                $this->line("  → Datei für {$pkg->last_known_version} noch nicht vorhanden: <fg=yellow>status=new_version</> bleibt, keine Aktion");
            }
        } else {
            $this->line("  → Keine neue Version, Status bleibt: <fg=green>{$pkg->status}</>");
        }
    }

    private function zeigeOrdnerInhalt(WatchedPackage $pkg, string $version): void
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            $path   = rtrim($pkg->getUncPath(), '\\') . '\\' . $version;
            $handle = @opendir($path);
            if ($handle === false) {
                $this->warn("  Ordner nicht lesbar: {$path}");
                return;
            }
            $entries = [];
            while (($entry = readdir($handle)) !== false) {
                if ($entry === '.' || $entry === '..') continue;
                $full    = $path . DIRECTORY_SEPARATOR . $entry;
                $size    = @is_file($full) ? @filesize($full) : null;
                $entries[] = ['name' => $entry, 'size' => $size, 'isDir' => @is_dir($full)];
            }
            closedir($handle);
        } else {
            $entries = $this->leseOrdnerLinux($pkg, $version);
        }

        if (empty($entries)) {
            $this->warn("  Ordner ist leer.");
            return;
        }

        $this->line("  Inhalt ({$version}):");
        foreach ($entries as $e) {
            if ($e['isDir'] ?? false) {
                $this->line("    <fg=cyan>[DIR]</>  {$e['name']}");
                continue;
            }
            $size    = $e['size'];
            $isReadme = str_starts_with(strtolower($e['name']), 'readme');
            if ($size === null) {
                $sizeStr = '? Byte';
                $color   = 'gray';
            } elseif ($size === 0) {
                $sizeStr = '0 Byte (Dummy)';
                $color   = 'yellow';
            } else {
                $sizeStr = $this->formatBytes($size);
                $color   = 'green';
            }
            $readmeNote = $isReadme ? ' <fg=gray>[README – wird ignoriert]</>' : '';
            $this->line("    <fg={$color}>{$sizeStr}</>  {$e['name']}{$readmeNote}");
        }

        if ($this->option('raw') && !$isWindows) {
            $this->line('');
            $this->line('  <fg=gray>── Rohe smbclient-Ausgabe ──────────────────────────</>');
            $this->zeigeRoheSmbAusgabe($pkg, $version);
        }
    }

    /**
     * Linux: smbclient ls aufrufen und Ausgabe für Diagnoseanzeige parsen.
     * Gibt Array mit ['name', 'size', 'isDir'] zurück.
     */
    private function leseOrdnerLinux(WatchedPackage $pkg, string $version): array
    {
        $settings = BaraSettings::getSingleton();
        $unc      = $pkg->getUncPath();
        $clean    = ltrim(str_replace('/', '\\', $unc), '\\');
        $parts    = explode('\\', $clean, 3);
        $server   = $parts[0] ?? '';
        $share    = $parts[1] ?? '';
        $subpath  = $parts[2] ?? '';
        $vPath    = ($subpath ? str_replace('\\', '/', $subpath) . '/' : '') . $version;

        $target = escapeshellarg("//{$server}/{$share}");
        if ($settings->hasSmbCredentials()) {
            $user    = $settings->smb_domain
                ? "{$settings->smb_domain}\\{$settings->smb_username}"
                : $settings->smb_username;
            $auth = '-U ' . escapeshellarg("{$user}%{$settings->smb_password}");
        } else {
            $auth = '-N';
        }

        $cmd = "smbclient {$target} {$auth} -c " . escapeshellarg("ls {$vPath}") . ' 2>&1';
        exec($cmd, $out, $ret);

        if ($this->option('raw')) {
            $this->line('');
            $this->line('  <fg=gray>── Rohe smbclient-Ausgabe (Exit-Code: ' . $ret . ') ──</>');
            foreach ($out as $line) {
                $this->line('  <fg=gray>' . htmlspecialchars($line) . '</>');
            }
        }

        if ($ret !== 0) {
            return [];
        }

        $entries = [];
        foreach ($out as $line) {
            if (!preg_match('/^\s{2}/', $line)) continue;
            if (str_contains($line, 'blocks of size')) continue;

            $content = substr($line, 2);
            $isDir   = (bool) preg_match('/\bD\b/', $line);

            if (preg_match('/^(.+?)\s{2,}[DAHNRS]*\s+(\d+)\s+/', $content, $m)) {
                $name = trim($m[1]);
                if ($name === '.' || $name === '..') continue;
                $entries[] = [
                    'name'  => $name,
                    'size'  => $isDir ? null : (int) $m[2],
                    'isDir' => $isDir,
                ];
            }
        }
        return $entries;
    }

    private function zeigeRoheSmbAusgabe(WatchedPackage $pkg, string $version): void
    {
        // Rohe Ausgabe wurde bereits in leseOrdnerLinux() ausgegeben wenn --raw.
        // Diese Methode ist ein No-op (Ausgabe passiert dort inline).
    }

    private function looksLikeVersion(string $name): bool
    {
        return (bool) preg_match('/^\d[\d.]*\.\d+(-[a-zA-Z0-9]+)?$/', $name);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) return round($bytes / (1024 ** 3), 2) . ' GB';
        if ($bytes >= 1024 * 1024)        return round($bytes / (1024 ** 2), 2) . ' MB';
        if ($bytes >= 1024)               return round($bytes / 1024, 2) . ' KB';
        return "{$bytes} Byte";
    }
}
