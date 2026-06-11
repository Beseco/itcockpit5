<?php

namespace App\Modules\Baramundi\Console\Commands;

use App\Modules\Baramundi\Mail\FileProvidedMail;
use App\Modules\Baramundi\Mail\NewVersionDetectedMail;
use App\Modules\Baramundi\Models\BaraEvent;
use App\Modules\Baramundi\Models\BaraSettings;
use App\Modules\Baramundi\Models\WatchedPackage;
use App\Modules\Baramundi\Services\DownloaderRegistry;
use App\Modules\Baramundi\Services\SmbScannerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BaraScanCommand extends Command
{
    protected $signature = 'bara:scan
                            {--package= : Nur ein bestimmtes Paket scannen (ID)}
                            {--force    : Auch ohne abgelaufenes Intervall ausführen}
                            {--no-download : Keinen Download auslösen (für manuelle Scans per AJAX)}';

    protected $description = 'Scannt Baramundi UNC-Pfade auf neue Softwareversionen';

    public function __construct(
        private readonly SmbScannerService $scanner,
        private readonly DownloaderRegistry $downloaders,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $lock = Cache::lock('bara:scan', 600);

        if (!$lock->get()) {
            $this->warn('Scan bereits aktiv – übersprungen.');
            return 0;
        }

        try {
            return $this->runScan();
        } finally {
            $lock->release();
        }
    }

    private function runScan(): int
    {
        $settings = BaraSettings::getSingleton();

        // Windows: net use für alle Server-Roots vorab herstellen.
        // Linux: smbclient übergibt Credentials pro Aufruf inline – kein Vorab-Connect nötig.
        $connectedRoots = [];
        if ($settings->hasSmbCredentials() && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $connectedRoots = $this->connectSmbShares($settings);
        }

        try {
            $query = WatchedPackage::where('enabled', true);

            if ($id = $this->option('package')) {
                $query->where('id', (int) $id);
            }

            $packages = $query->get();

            if ($packages->isEmpty()) {
                $this->info('Keine aktiven Pakete zum Scannen.');
                return 0;
            }

            $this->info("Scanne {$packages->count()} Paket(e)...");

            foreach ($packages as $pkg) {
                $this->scanPackage($pkg, $settings);
            }

            $this->info('Scan abgeschlossen.');
            return 0;
        } finally {
            // Verbindungen wieder trennen
            foreach ($connectedRoots as $root) {
                $this->scanner->netUseDisconnect($root);
            }
        }
    }

    /**
     * Stellt net-use-Verbindungen zu allen einzigartigen Server-Shares der aktiven Pakete her.
     * Gibt die Liste der verbundenen Roots zurück (für spätere Trennung).
     */
    private function connectSmbShares(BaraSettings $settings): array
    {
        $packages = WatchedPackage::where('enabled', true)->get();
        $roots    = [];

        foreach ($packages as $pkg) {
            $root = $this->scanner->getShareRoot($pkg->getUncPath());
            if (!in_array($root, $roots, true)) {
                $roots[] = $root;
            }
        }

        $user     = $settings->smb_domain
            ? $settings->smb_domain . '\\' . $settings->smb_username
            : $settings->smb_username;

        $connected = [];
        foreach ($roots as $root) {
            $result = $this->scanner->netUseConnect($root, $user, $settings->smb_password);
            if ($result['ok']) {
                $this->line("  SMB verbunden: {$root}");
                $connected[] = $root;
            } else {
                $this->warn("  SMB-Verbindung fehlgeschlagen für {$root}: {$result['message']}");
                Log::warning('Baramundi: net use fehlgeschlagen', [
                    'root'    => $root,
                    'message' => $result['message'],
                ]);
            }
        }

        return $connected;
    }

    private function scanPackage(WatchedPackage $pkg, BaraSettings $settings): void
    {
        $this->line("  → {$pkg->name} ({$pkg->getUncPath()})");

        if (!$this->scanner->isReachable($pkg)) {
            $this->warn("    UNC nicht erreichbar: {$pkg->getUncPath()}");
            $pkg->update(['status' => 'smb_unreachable', 'last_scan' => now()]);
            $this->logEvent($pkg, 'smb_unreachable', null,
                "UNC-Pfad nicht erreichbar: {$pkg->getUncPath()}");
            Log::warning('Baramundi: SMB nicht erreichbar', [
                'package' => $pkg->name,
                'path'    => $pkg->getUncPath(),
            ]);
            return;
        }

        try {
            $subdirs = $this->scanner->listSubdirectories($pkg);
        } catch (\RuntimeException $e) {
            $this->error("    Fehler beim Lesen: {$e->getMessage()}");
            $pkg->update(['status' => 'smb_unreachable', 'last_scan' => now()]);
            $this->logEvent($pkg, 'smb_unreachable', null, $e->getMessage());
            return;
        }

        $pkg->update(['last_scan' => now()]);

        $newest = $this->scanner->findNewestVersion($subdirs);

        if ($newest === null) {
            $this->line("    Keine Versionsordner gefunden.");
            if ($pkg->status === 'smb_unreachable') {
                $pkg->update(['status' => 'ok']);
            }
            return;
        }

        $this->line("    Neueste Version: {$newest}");

        // ── Pfad A: Neue (unbekannte) Version entdeckt ──────────────────────────
        if ($this->scanner->isNewerThan($newest, $pkg->last_known_version)) {
            $prev = $pkg->last_known_version ?? 'unbekannt';
            $hasFile = $this->scanner->hasNonEmptyFile($pkg, $newest);
            $this->info("    NEUE VERSION: {$newest} (vorher: {$prev})" . ($hasFile ? ' – Datei vorhanden' : ' – Datei fehlt noch'));

            if ($hasFile) {
                // Direkt bereit: Datei bereits im neuen Ordner.
                $wasAwaitingFile = $pkg->status === 'new_version';
                $pkg->update([
                    'last_known_version' => $newest,
                    'last_detected'      => now(),
                    'status'             => 'ok',
                ]);
                $this->logEvent($pkg, 'file_provided', $newest,
                    "Version {$newest} erkannt und Installationsdatei sofort vorhanden.");
                // E-Mail nur wenn vorher new_version (vermeidet Doppelmail bei erstem Scan)
                if ($wasAwaitingFile) {
                    $this->sendMail($pkg, $settings, new FileProvidedMail($pkg, $newest), $newest);
                }
            } else {
                // Neuer Ordner, Datei noch 0 KB – Admin muss Datei kopieren.
                $alreadyKnownAsNew = $pkg->status === 'new_version'
                    && $pkg->last_known_version === $newest;
                $pkg->update([
                    'last_known_version' => $newest,
                    'last_detected'      => now(),
                    'status'             => 'new_version',
                ]);
                $this->logEvent($pkg, 'version_detected', $newest,
                    "Neue Version erkannt: {$newest} in {$pkg->getUncPath()}\\{$newest} – Installationsdatei fehlt noch.");
                // E-Mail nur einmalig (nicht bei jedem Scan-Durchlauf)
                if (!$alreadyKnownAsNew) {
                    $this->sendMail($pkg, $settings, new NewVersionDetectedMail($pkg, $newest), $newest);
                }
            }

            if (!$this->option('no-download') && $pkg->download_type !== 'none') {
                $this->triggerDownload($pkg, $newest);
            }
            return;
        }

        // ── Pfad B: Bekannte Version – prüfen ob Datei inzwischen bereitgestellt ─
        $known = $pkg->last_known_version;
        $this->line("    Keine neue Version (bekannt: {$known}).");

        if ($pkg->status === 'new_version' && $known !== null) {
            $hasFile = $this->scanner->hasNonEmptyFile($pkg, $known);
            if ($hasFile) {
                $this->info("    Installationsdatei für {$known} jetzt vorhanden – Status → OK");
                $pkg->update(['status' => 'ok']);
                $this->logEvent($pkg, 'file_provided', $known,
                    "Installationsdatei für Version {$known} bereitgestellt.");
                $this->sendMail($pkg, $settings, new FileProvidedMail($pkg, $known), $known);
            } else {
                $this->line("    Datei für {$known} noch nicht vorhanden (0 KB).");
            }
            return;
        }

        if ($pkg->status === 'smb_unreachable') {
            $pkg->update(['status' => 'ok']);
        }
    }

    private function sendMail(WatchedPackage $pkg, BaraSettings $settings, \Illuminate\Mail\Mailable $mail, string $version): void
    {
        if (!$pkg->email_enabled || !$settings->notification_email) {
            return;
        }
        try {
            Mail::to($settings->notification_email)->send($mail);
            $this->line("    E-Mail gesendet an {$settings->notification_email}");
        } catch (\Throwable $e) {
            $this->warn("    E-Mail-Fehler: {$e->getMessage()}");
            Log::error('Baramundi: E-Mail-Versand fehlgeschlagen', [
                'package' => $pkg->name,
                'version' => $version,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function triggerDownload(WatchedPackage $pkg, string $version): void
    {
        $downloader = $this->downloaders->findFor($pkg);

        if ($downloader === null) {
            $this->warn("    Kein Downloader für Typ '{$pkg->download_type}' gefunden.");
            return;
        }

        $this->line("    Starte Download ({$pkg->download_type})...");
        $pkg->update(['status' => 'download_running']);
        $this->logEvent($pkg, 'download_started', $version, "Download gestartet für Version {$version}");

        try {
            $ok = $downloader->download($pkg, $version);

            $pkg->update(['status' => $ok ? 'download_ok' : 'download_failed']);
            $this->logEvent(
                $pkg,
                $ok ? 'download_ok' : 'download_failed',
                $version,
                $ok ? "Download abgeschlossen für Version {$version}" : "Download fehlgeschlagen für Version {$version}"
            );

            $ok
                ? $this->info("    Download erfolgreich.")
                : $this->error("    Download fehlgeschlagen.");
        } catch (\Throwable $e) {
            $pkg->update(['status' => 'download_failed']);
            $this->logEvent($pkg, 'download_failed', $version, $e->getMessage());
            $this->error("    Download-Exception: {$e->getMessage()}");
            Log::error('Baramundi: Download-Exception', [
                'package' => $pkg->name,
                'version' => $version,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function logEvent(WatchedPackage $pkg, string $eventType, ?string $version, string $message): void
    {
        BaraEvent::create([
            'package_id' => $pkg->id,
            'event_type' => $eventType,
            'version'    => $version,
            'message'    => $message,
        ]);
    }
}
