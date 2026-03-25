<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Fernwartung\Models\Fernwartung;
use App\Modules\Fernwartung\Models\FernwartungTool;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyFernwartung extends Command
{
    protected $signature = 'fernwartung:import-legacy
                            {--host=itcockpit.lra.lan : DB-Host}
                            {--database=ticketsystem_db1 : Datenbankname}
                            {--username=ticketsystem_db1 : DB-Benutzer}
                            {--password= : DB-Passwort}
                            {--dry-run : Nur anzeigen, nicht importieren}';

    protected $description = 'Importiert Fernwartungseinträge aus dem alten System (isis12_fernwartung)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Temporäre Legacy-Verbindung konfigurieren
        config(['database.connections.legacy_fw' => [
            'driver'    => 'mysql',
            'host'      => $this->option('host'),
            'database'  => $this->option('database'),
            'username'  => $this->option('username'),
            'password'  => $this->option('password') ?? '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ]]);

        try {
            $rows = DB::connection('legacy_fw')
                ->table('isis12_fernwartung')
                ->orderBy('timestamp')
                ->get();
        } catch (\Exception $e) {
            $this->error('Verbindung fehlgeschlagen: ' . $e->getMessage());
            return 1;
        }

        $this->info("Gefundene Einträge: {$rows->count()}");

        $imported  = 0;
        $skipped   = 0;
        $toolCache = [];

        foreach ($rows as $row) {
            // Datum parsen
            try {
                $datum = Carbon::createFromFormat('d.m.Y', trim($row->datum))->format('Y-m-d');
            } catch (\Exception) {
                try {
                    $datum = Carbon::parse($row->datum)->format('Y-m-d');
                } catch (\Exception) {
                    $this->warn("Überspringe Zeile #{$row->id}: ungültiges Datum '{$row->datum}'");
                    $skipped++;
                    continue;
                }
            }

            $beginn = $row->beginn ?: '00:00';
            $beginn = substr($beginn, 0, 5); // auf HH:MM kürzen

            // Duplikate prüfen
            $exists = Fernwartung::where('datum', $datum)
                ->where('beginn', $beginn)
                ->where('externer_name', $row->name)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            // Tool sicherstellen
            $toolName = trim($row->tool ?: 'Unbekannt');
            if (!isset($toolCache[$toolName])) {
                FernwartungTool::firstOrCreate(
                    ['name' => $toolName],
                    ['sort_order' => 99]
                );
                $toolCache[$toolName] = true;
            }

            // Beobachter-User suchen
            $adminName  = trim($row->admin ?? '');
            $beobachter = User::where('name', $adminName)->first()
                ?? User::where('name', 'like', "%{$adminName}%")->first();

            $ende = $row->ende ? substr(trim($row->ende), 0, 5) : null;
            if ($ende === '' || $ende === '00:00') $ende = null;

            $createdAt = $row->timestamp
                ? Carbon::createFromTimestamp($row->timestamp)
                : now();

            $data = [
                'externer_name'      => trim($row->name),
                'firma'              => trim($row->firma),
                'beobachter_user_id' => $beobachter?->id,
                'beobachter_name'    => $beobachter ? $beobachter->name : $adminName,
                'ziel'               => trim($row->ziel),
                'tool'               => $toolName,
                'datum'              => $datum,
                'beginn'             => $beginn,
                'ende'               => $ende,
                'grund'              => trim($row->grund),
                'created_by'         => null,
                'created_at'         => $createdAt,
                'updated_at'         => $createdAt,
            ];

            if ($dryRun) {
                $this->line("  [{$datum} {$beginn}] {$row->name} ({$row->firma}) via {$toolName}");
            } else {
                Fernwartung::insert($data);
            }

            $imported++;
        }

        $action = $dryRun ? 'Würde importieren' : 'Importiert';
        $this->info("{$action}: {$imported} · Übersprungen: {$skipped}");

        return 0;
    }
}
