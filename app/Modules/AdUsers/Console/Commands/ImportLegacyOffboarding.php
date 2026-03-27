<?php

namespace App\Modules\AdUsers\Console\Commands;

use App\Modules\AdUsers\Models\AdUser;
use App\Modules\AdUsers\Models\OffboardingRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyOffboarding extends Command
{
    protected $signature = 'adusers:import-offboarding
                            {--db= : Name der Quelldatenbank, falls abweichend (z.B. ticketsystem_db1)}
                            {--pdf-path=C:\xampp\htdocs\itcockpit\old\uploads : Pfad zum alten Uploads-Verzeichnis}
                            {--dry-run : Nur anzeigen, nichts speichern}';

    protected $description = 'Importiert Altdaten aus isis12_ausscheiden in offboarding_records';

    public function handle(): int
    {
        $pdfPath  = rtrim($this->option('pdf-path'), '/\\');
        $dryRun   = $this->option('dry-run');
        $dbOption = $this->option('db');

        // Tabellenname mit optionalem DB-Prefix: database.table
        $tableName = $dbOption
            ? "`{$dbOption}`.`isis12_ausscheiden`"
            : 'isis12_ausscheiden';

        // Prüfen ob Quelltabelle existiert
        if (!$this->tableExists($tableName)) {
            $this->error("Tabelle {$tableName} nicht gefunden.");
            if (!$dbOption) {
                $this->line('');
                $this->line('Falls die Altdaten in einer anderen Datenbank liegen, Option --db angeben:');
                $this->line('  php artisan adusers:import-offboarding --db=ticketsystem_db1');
            }
            return Command::FAILURE;
        }

        $rows = DB::statement("SET FOREIGN_KEY_CHECKS=0") || true;
        $rows = DB::table(DB::raw($tableName))->orderBy('id')->get();

        if ($rows->isEmpty()) {
            $this->info('Keine Datensätze in isis12_ausscheiden gefunden.');
            return Command::SUCCESS;
        }

        $this->info("Gefunden: {$rows->count()} Datensätze");

        $imported  = 0;
        $skipped   = 0;
        $pdfLoaded = 0;

        foreach ($rows as $row) {
            // Bereits importiert?
            if (OffboardingRecord::where('legacy_id', $row->id)->exists()) {
                $skipped++;
                continue;
            }

            // Name aufteilen (letztes Wort = Nachname, Rest = Vorname)
            $nameParts = explode(' ', trim($row->name ?? ''));
            $nachname  = array_pop($nameParts);
            $vorname   = implode(' ', $nameParts) ?: $nachname;

            // Datum parsen (dd.mm.yyyy → Y-m-d)
            $datumAusscheiden = $this->parseGermanDate($row->datumas ?? '');
            $datumGeloescht   = $this->parseGermanDate($row->datumdel ?? '');

            if (!$datumAusscheiden) {
                $this->warn("Überspringe ID {$row->id}: ungültiges Datum '{$row->datumas}'");
                $skipped++;
                continue;
            }

            // Status bestimmen
            $status = $datumGeloescht ? 'abgeschlossen' : 'ausstehend';

            $data = [
                'vorname'          => $vorname,
                'nachname'         => $nachname,
                'samaccountname'   => strtolower(str_replace(' ', '.', trim($row->name ?? ''))),
                'abteilung'        => $row->sg ?? null,
                'anleger_name'     => $row->anleger ?? 'Import',
                'datum_ausscheiden'=> $datumAusscheiden,
                'datum_geloescht'  => $datumGeloescht,
                'geloescht_von'    => $row->geloeschtvon ?? null,
                'status'           => $status,
                'legacy_id'        => $row->id,
                'imported_at'      => now(),
                'created_at'       => now(),
                'updated_at'       => now(),
            ];

            // AdUser per Name verknüpfen (Heuristik)
            $adUser = AdUser::where('vorname', $vorname)
                ->where('nachname', $nachname)
                ->first();
            if ($adUser) {
                $data['aduser_id']        = $adUser->id;
                $data['samaccountname']   = $adUser->samaccountname;
                $data['email_bestaetigung'] = $adUser->email;
            }

            // PDFs laden
            if (!empty($row->personalmeldung)) {
                $file = $pdfPath . DIRECTORY_SEPARATOR . $row->personalmeldung;
                if (file_exists($file)) {
                    $data['personalmeldung_pdf']      = file_get_contents($file);
                    $data['personalmeldung_pdf_name'] = basename($file);
                    $pdfLoaded++;
                }
            }
            if (!empty($row->bestaetigung)) {
                $file = $pdfPath . DIRECTORY_SEPARATOR . $row->bestaetigung;
                if (file_exists($file)) {
                    $data['bestaetigung_pdf']      = file_get_contents($file);
                    $data['bestaetigung_pdf_name'] = basename($file);
                    $pdfLoaded++;
                }
            }

            if ($dryRun) {
                $this->line("  DRY: {$vorname} {$nachname} ({$datumAusscheiden}) – {$status}");
            } else {
                OffboardingRecord::create($data);
            }

            $imported++;
        }

        $this->info("✓ Importiert: {$imported}");
        $this->info("✓ PDFs geladen: {$pdfLoaded}");
        if ($skipped > 0) {
            $this->warn("  Übersprungen: {$skipped}");
        }
        if ($dryRun) {
            $this->warn('Dry-Run: Keine Änderungen gespeichert.');
        }

        return Command::SUCCESS;
    }

    private function parseGermanDate(string $value): ?string
    {
        $value = trim($value);
        if (empty($value)) {
            return null;
        }

        // dd.mm.yyyy oder d.m.yyyy (vierstelliges Jahr)
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $value, $m)) {
            $date = \DateTime::createFromFormat('j.n.Y', $value);
            return $date ? $date->format('Y-m-d') : null;
        }

        // dd.mm.yy oder d.m.yy (zweistelliges Jahr → 20xx)
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{2})$/', $value, $m)) {
            $year = (int)$m[3] + 2000;
            $date = \DateTime::createFromFormat('j.n.Y', "{$m[1]}.{$m[2]}.{$year}");
            return $date ? $date->format('Y-m-d') : null;
        }

        // Fallback: Y-m-d
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return null;
    }

    private function tableExists(string $table): bool
    {
        try {
            DB::table(DB::raw($table))->limit(1)->get();
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
