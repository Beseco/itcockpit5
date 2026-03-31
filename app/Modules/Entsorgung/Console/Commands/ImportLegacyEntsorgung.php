<?php

namespace App\Modules\Entsorgung\Console\Commands;

use App\Modules\Entsorgung\Models\Entsorgung;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyEntsorgung extends Command
{
    protected $signature = 'entsorgung:import-legacy
                            {--db= : Name der Quelldatenbank, falls abweichend (z.B. itcockpit_old)}
                            {--dry-run : Nur anzeigen, nichts speichern}';

    protected $description = 'Importiert Altdaten aus der alten entsorgung-Tabelle in die neue entsorgungen-Tabelle';

    public function handle(): int
    {
        $dryRun   = $this->option('dry-run');
        $dbOption = $this->option('db');

        $tableName = $dbOption
            ? "`{$dbOption}`.`entsorgung`"
            : 'entsorgung';

        if (!$this->tableExists($tableName)) {
            $this->error("Tabelle {$tableName} nicht gefunden.");
            if (!$dbOption) {
                $this->line('');
                $this->line('Falls die Altdaten in einer anderen Datenbank liegen, Option --db angeben:');
                $this->line('  php artisan entsorgung:import-legacy --db=itcockpit_old');
            }
            return Command::FAILURE;
        }

        $rows = DB::table(DB::raw($tableName))->orderBy('id')->get();

        if ($rows->isEmpty()) {
            $this->info('Keine Datensätze in der alten entsorgung-Tabelle gefunden.');
            return Command::SUCCESS;
        }

        $this->info("Gefunden: {$rows->count()} Datensätze");

        $imported = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            // Bereits importiert? (anhand Inventar + Datum prüfen)
            $datum = $this->parseDatum($row->datum ?? 0);

            if (!$datum) {
                $this->warn("Überspringe ID {$row->id}: ungültiges Datum '{$row->datum}'");
                $skipped++;
                continue;
            }

            // Duplikat-Prüfung: selbes Inventar + selbes Datum
            if (Entsorgung::where('inventar', $row->inventar)->whereDate('datum', $datum)->exists()) {
                $skipped++;
                continue;
            }

            // grundschutz: alt gespeichert als "on" (Checkbox) oder leer
            $grundschutz = ($row->grundschutz === 'on' || $row->grundschutz === '1' || $row->grundschutz === 1);

            $user = ($row->user ?? '') === '' ? null : $row->user;

            $data = [
                'name'             => $row->name ?? '',
                'modell'           => $row->modell ?? '',
                'hersteller'       => $row->hersteller ?? '',
                'typ'              => ($row->typ ?? '') !== '' ? $row->typ : null,
                'inventar'         => $row->inventar ?? '',
                'entsorger'        => $row->entsorger ?? '',
                'user'             => $user,
                'grundschutz'      => $grundschutz,
                'grundschutzgrund' => ($row->grundschutzgrund ?? '') !== '' ? $row->grundschutzgrund : null,
                'datum'            => $datum,
                'created_by'       => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];

            if ($dryRun) {
                $this->line("  DRY: [{$row->id}] {$row->name} | {$row->inventar} | {$datum} | GS: " . ($grundschutz ? 'Ja' : 'Nein'));
            } else {
                Entsorgung::create($data);
            }

            $imported++;
        }

        $this->info("Importiert: {$imported}");
        if ($skipped > 0) {
            $this->warn("Übersprungen (Duplikate / ungültiges Datum): {$skipped}");
        }
        if ($dryRun) {
            $this->warn('Dry-Run: Keine Änderungen gespeichert.');
        }

        return Command::SUCCESS;
    }

    /**
     * Datum aus Unix-Timestamp (altes System) oder Datumsstring in Y-m-d umwandeln.
     */
    private function parseDatum(mixed $value): ?string
    {
        if (is_numeric($value) && $value > 0) {
            return date('Y-m-d', (int) $value);
        }

        // Fallback: bereits als Y-m-d gespeichert
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return substr($value, 0, 10);
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
