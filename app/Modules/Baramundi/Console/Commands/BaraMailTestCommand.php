<?php

namespace App\Modules\Baramundi\Console\Commands;

use App\Modules\Baramundi\Mail\FileProvidedMail;
use App\Modules\Baramundi\Mail\NewVersionDetectedMail;
use App\Modules\Baramundi\Models\BaraSettings;
use App\Modules\Baramundi\Models\WatchedPackage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Sendet alle Baramundi-E-Mail-Typen mit Testdaten.
 *
 *   php artisan bara:mail-test
 *   php artisan bara:mail-test --to=admin@example.com
 *   php artisan bara:mail-test --to=admin@example.com --package=1
 */
class BaraMailTestCommand extends Command
{
    protected $signature = 'bara:mail-test
                            {--to=      : Empfänger-Adresse (Standard: notification_email aus Einstellungen)}
                            {--package= : Echtes Paket als Basis (ID); sonst werden Fake-Daten verwendet}';

    protected $description = 'Sendet alle Baramundi-E-Mail-Typen zu Testzwecken';

    public function handle(): int
    {
        $settings = BaraSettings::getSingleton();
        $to       = $this->option('to') ?: $settings->notification_email;

        if (!$to) {
            $this->error('Kein Empfänger. Bitte --to=... angeben oder notification_email in den Einstellungen hinterlegen.');
            return 1;
        }

        // Paket-Daten bestimmen
        if ($pkgId = $this->option('package')) {
            $pkg = WatchedPackage::find((int) $pkgId);
            if (!$pkg) {
                $this->error("Paket mit ID {$pkgId} nicht gefunden.");
                return 1;
            }
            $version = $pkg->last_known_version ?: '1.0.0-test';
        } else {
            // Fake-Paket für den Test
            $pkg = new WatchedPackage([
                'name'        => 'Testpaket (bara:mail-test)',
                'server_name' => 'Bara-01.lra.lan',
                'share_path'  => 'dip$\\ManagedSoftware\\source\\TestPaket\\1.x-x64',
                'notes'       => 'Dies ist eine automatisch generierte Test-E-Mail.',
            ]);
            $version = '1.23.4-x64';
        }

        $mails = [
            [
                'label' => 'Neue Version erkannt  (neue Version entdeckt, Datei fehlt noch)',
                'mail'  => new NewVersionDetectedMail($pkg, $version),
            ],
            [
                'label' => 'Installationsdatei bereitgestellt  (Datei kopiert, Status → OK)',
                'mail'  => new FileProvidedMail($pkg, $version),
            ],
        ];

        $count = count($mails);

        $this->info('');
        $this->info("Sende {$count} Test-Mail(s) an: {$to}");
        $this->line("Paket:   {$pkg->name}");
        $this->line("Version: {$version}");
        $this->info('');

        $errors = 0;
        foreach ($mails as $item) {
            $this->line("  → {$item['label']} …");
            try {
                Mail::to($to)->send($item['mail']);
                $this->line('    <fg=green>✓ gesendet</>');
            } catch (\Throwable $e) {
                $this->error("    ✗ Fehler: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->info('');
        if ($errors === 0) {
            $this->info("<fg=green>Alle {$count} Mails erfolgreich gesendet.</>");
        } else {
            $this->warn("{$errors} von {$count} Mails fehlgeschlagen.");
        }

        return $errors > 0 ? 1 : 0;
    }
}
