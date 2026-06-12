<?php

namespace App\Modules\Baramundi\Console\Commands;

use App\Modules\Baramundi\Models\BaraSettings;
use App\Modules\Baramundi\Models\WatchedPackage;
use App\Modules\Tickets\Models\TicketsSettings;
use App\Modules\Tickets\Services\ZammadService;
use Illuminate\Console\Command;

/**
 * Testet die Zammad-Integration: erstellt ein Test-Ticket und fügt einen
 * Follow-up-Artikel hinzu – simuliert den kompletten Baramundi-Ablauf.
 *
 *   php artisan bara:zammad-test
 *   php artisan bara:zammad-test --package=1
 */
class BaraZammadTestCommand extends Command
{
    protected $signature = 'bara:zammad-test
                            {--package= : Echtes Paket als Basis (ID); sonst Fake-Daten}';

    protected $description = 'Testet Zammad-Integration: erstellt Ticket + Follow-up-Notiz';

    public function handle(): int
    {
        $baraSettings   = BaraSettings::getSingleton();
        $zammadSettings = TicketsSettings::getSingleton();

        // ── Konfiguration prüfen ──────────────────────────────────────────────
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║       Baramundi Zammad-Test                         ║');
        $this->info('╚══════════════════════════════════════════════════════╝');
        $this->info('');

        $this->line('<fg=cyan>Zammad-Konfiguration:</>');
        $this->line('  Aktiviert:    ' . ($zammadSettings->enabled ? '<fg=green>Ja</>' : '<fg=red>Nein</>'));
        $this->line('  URL:          ' . ($zammadSettings->url ?: '<fg=red>(leer)</>'));
        $this->line('  Token:        ' . ($zammadSettings->api_token ? '<fg=green>gesetzt</>' : '<fg=red>(leer)</>'));
        $this->line('  Konfiguriert: ' . ($zammadSettings->isConfigured() ? '<fg=green>Ja</>' : '<fg=red>Nein</>'));
        $this->line('  Gruppe:       ' . ($baraSettings->zammad_group ?: '<fg=yellow>Users (Standard)</>'));

        if (!$zammadSettings->isConfigured()) {
            $this->error('');
            $this->error('Zammad ist nicht konfiguriert. Bitte im Tickets-Modul aktivieren.');
            return 1;
        }

        // ── Paket bestimmen ───────────────────────────────────────────────────
        if ($pkgId = $this->option('package')) {
            $pkg = WatchedPackage::find((int) $pkgId);
            if (!$pkg) {
                $this->error("Paket mit ID {$pkgId} nicht gefunden.");
                return 1;
            }
            $version = $pkg->last_known_version ?: '1.0.0-test';
        } else {
            $pkg = new WatchedPackage([
                'name'        => 'Testpaket (bara:zammad-test)',
                'server_name' => 'Bara-01.lra.lan',
                'share_path'  => 'dip$\\ManagedSoftware\\source\\TestPaket\\1.x-x64',
                'notes'       => 'Dies ist ein automatisch generierter Zammad-Test.',
            ]);
            $version = '1.23.4-x64';
        }

        $group = $baraSettings->zammad_group ?: 'Users';
        $title = "IT Cockpit · Baramundi: {$pkg->name} {$version}";

        $this->info('');
        $this->line("Paket:   {$pkg->name}");
        $this->line("Version: {$version}");
        $this->line("Gruppe:  {$group}");
        $this->line("Titel:   {$title}");

        $zammad = new ZammadService();

        // ── Schritt 1: Verbindungstest ────────────────────────────────────────
        $this->info('');
        $this->line('<fg=cyan>[Schritt 1] Verbindung testen …</>');
        $conn = $zammad->testConnection();
        if (!$conn['success']) {
            $this->error("  ✗ {$conn['message']}");
            return 1;
        }
        $this->line("  <fg=green>✓ {$conn['message']}</>");

        // ── Schritt 2: Ticket erstellen (simuliert "neue Version erkannt") ────
        $this->info('');
        $this->line('<fg=cyan>[Schritt 2] Ticket erstellen (simuliert „Neue Version erkannt") …</>');

        $ticketBody =
            "<b>Neue Version erkannt</b> [TEST]<br><br>" .
            "Paket: <b>{$pkg->name}</b><br>" .
            "Version: <b>{$version}</b><br>" .
            "Pfad: {$pkg->getUncPath()}\\{$version}\\<br><br>" .
            "Baramundi hat den Versionsordner angelegt. Die Installationsdatei muss " .
            "noch manuell in den Ordner kopiert werden.<br><br>" .
            "<em>Dies ist ein Testticket, erzeugt durch bara:zammad-test.</em>";

        $ticket = $zammad->createTicket($title, $ticketBody, $group);

        if (!$ticket || empty($ticket['id'])) {
            $this->error("  ✗ Ticket konnte nicht erstellt werden.");
            if ($ticket) {
                $this->line("  API-Antwort: " . json_encode($ticket, JSON_UNESCAPED_UNICODE));
            }
            return 1;
        }

        $ticketId     = (int) $ticket['id'];
        $ticketNumber = $ticket['number'] ?? '?';
        $this->line("  <fg=green>✓ Ticket #{$ticketNumber} (ID: {$ticketId}) erstellt.</>");

        // ── Schritt 3: Notiz hinzufügen (simuliert "Datei bereitgestellt") ────
        $this->info('');
        $this->line('<fg=cyan>[Schritt 3] Notiz hinzufügen (simuliert „Datei bereitgestellt") …</>');

        $articleBody =
            "<b>✓ Installationsdatei bereitgestellt</b> [TEST]<br><br>" .
            "Paket: <b>{$pkg->name}</b><br>" .
            "Version: <b>{$version}</b><br>" .
            "Pfad: {$pkg->getUncPath()}\\{$version}\\<br><br>" .
            "Im Versionsordner ist jetzt mindestens eine Installationsdatei (&gt;0 Byte) vorhanden. " .
            "Der Status wurde auf <b>OK</b> gesetzt.<br><br>" .
            "<em>Dies ist ein Testkommentar, erzeugt durch bara:zammad-test.</em>";

        $article = $zammad->addArticle($ticketId, $title, $articleBody);

        if (!$article) {
            $this->warn("  ✗ Artikel konnte nicht hinzugefügt werden.");
        } else {
            $this->line("  <fg=green>✓ Notiz (Artikel-ID: {$article['id']}) zu Ticket #{$ticketNumber} hinzugefügt.</>");
        }

        // ── Schritt 4: Titel-Suche testen ─────────────────────────────────────
        $this->info('');
        $this->line('<fg=cyan>[Schritt 4] Ticket per Titelsuche wiederfinden …</>');
        $foundId = $zammad->findTicketByTitle($title);
        if ($foundId) {
            $this->line("  <fg=green>✓ Ticket gefunden: ID {$foundId}</>");
        } else {
            $this->warn("  ✗ Ticket nicht per Titelsuche gefunden (möglicherweise noch nicht indiziert).");
        }

        // ── Ergebnis ──────────────────────────────────────────────────────────
        $this->info('');
        $this->info('<fg=green>Test abgeschlossen. Bitte Ticket im Zammad prüfen:</>');
        $this->line("  " . rtrim($zammadSettings->url, '/') . "/#ticket/zoom/{$ticketId}");
        $this->info('');

        return 0;
    }
}
