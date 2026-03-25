<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class CompareApplikationenLegacy extends Command
{
    protected $signature = 'applikationen:compare-legacy
                            {--reimport : Fehlende Datensätze aus alter DB einspielen}
                            {--force   : Abweichende Datensätze in neuer DB überschreiben}';

    protected $description = 'Vergleicht Applikationen zwischen alter DB (ticketsystem_db1) und neuer DB';

    /** Felder, die beim Vergleich geprüft werden */
    private const COMPARE_FIELDS = [
        'name', 'sg', 'einsatzzweck',
        'confidentiality', 'integrity', 'availability',
        'baustein', 'verantwortlich_sg', 'admin', 'ansprechpartner',
        'hersteller', 'revision_date', 'doc_url',
    ];

    private PDO $oldPdo;

    public function handle(): int
    {
        $this->info('=== Applikationen – Vergleich alt/neu ===');

        try {
            $this->oldPdo = new PDO(
                'mysql:host=itcockpit.lra.lan;dbname=ticketsystem_db1;charset=utf8mb4',
                'ticketsystem_db1',
                'jXMwhfT6jFAEUyYP',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (\Exception $e) {
            $this->error('Verbindung zur alten DB fehlgeschlagen: ' . $e->getMessage());
            return self::FAILURE;
        }

        $reimport = $this->option('reimport');
        $force    = $this->option('force');

        // Daten aus alter DB laden
        $oldRows = $this->oldPdo
            ->query('SELECT * FROM applikationen ORDER BY id')
            ->fetchAll(PDO::FETCH_ASSOC);

        // Daten aus neuer DB laden (indexiert nach id)
        $newRows = DB::table('applikationen')
            ->get()
            ->keyBy('id')
            ->toArray();

        $missing    = [];
        $differing  = [];

        foreach ($oldRows as $old) {
            $id = $old['id'];

            if (!isset($newRows[$id])) {
                $missing[] = $old;
                continue;
            }

            $new  = (array) $newRows[$id];
            $diff = [];

            foreach (self::COMPARE_FIELDS as $field) {
                $oldVal = $old[$field]  ?? null;
                // Sonderfall: altes Feld 'Hersteller' (Großschreibung)
                if ($field === 'hersteller' && $oldVal === null) {
                    $oldVal = $old['Hersteller'] ?? null;
                }
                $newVal = $new[$field] ?? null;

                // Leere Strings als null behandeln
                $oldVal = $oldVal === '' ? null : $oldVal;
                $newVal = $newVal === '' ? null : $newVal;

                if ((string) $oldVal !== (string) $newVal) {
                    $diff[$field] = ['alt' => $oldVal, 'neu' => $newVal];
                }
            }

            if (!empty($diff)) {
                $differing[] = ['id' => $id, 'name' => $old['name'], 'diff' => $diff];
            }
        }

        // ── Ausgabe: Fehlende ──────────────────────────────────────────────
        $this->info('');
        $this->info("Fehlend in neuer DB: " . count($missing));
        foreach ($missing as $row) {
            $this->line("  ID {$row['id']}: {$row['name']}");
        }

        // ── Ausgabe: Abweichende ───────────────────────────────────────────
        $this->info('');
        $this->info("Abweichend: " . count($differing));
        foreach ($differing as $item) {
            $this->line("  ID {$item['id']}: {$item['name']}");
            foreach ($item['diff'] as $field => $vals) {
                $this->line("    {$field}: [{$vals['alt']}] → [{$vals['neu']}]");
            }
        }

        // ── Reimport: Fehlende einspielen ──────────────────────────────────
        if ($reimport && !empty($missing)) {
            $this->info('');
            $this->info('→ Fehlende Datensätze werden importiert...');
            $this->importRows($missing);
        }

        // ── Force: Abweichende überschreiben ──────────────────────────────
        if ($force && !empty($differing)) {
            $this->info('');
            $this->info('→ Abweichende Datensätze werden überschrieben...');
            $ids = array_column($differing, 'id');
            $rows = array_filter($oldRows, fn($r) => in_array($r['id'], $ids));
            $this->importRows(array_values($rows));
        }

        $this->info('');
        $this->info('✓ Vergleich abgeschlossen.');
        return self::SUCCESS;
    }

    private function importRows(array $rows): void
    {
        // Benutzer-Index für Admin-Zuordnung
        $usersByName = DB::table('users')
            ->select('id', 'name')
            ->get()
            ->keyBy(fn($u) => strtolower(trim($u->name)));

        $imported     = 0;
        $adminMatched = 0;

        foreach ($rows as $row) {
            $adminText   = $row['admin'] ?? null;
            $adminUserId = null;
            if ($adminText) {
                $match = $usersByName[strtolower(trim($adminText))] ?? null;
                if ($match) {
                    $adminUserId = $match->id;
                    $adminMatched++;
                }
            }

            DB::table('applikationen')->updateOrInsert(
                ['id' => $row['id']],
                [
                    'name'              => $row['name'],
                    'sg'                => $row['sg'] ?? null,
                    'einsatzzweck'      => $row['einsatzzweck'] ?? null,
                    'confidentiality'   => $row['confidentiality'] ?? 'A',
                    'integrity'         => $row['integrity'] ?? 'A',
                    'availability'      => $row['availability'] ?? 'A',
                    'baustein'          => $row['baustein'] ?? null,
                    'verantwortlich_sg' => $row['verantwortlich_sg'] ?? null,
                    'admin'             => $adminText,
                    'admin_user_id'     => $adminUserId,
                    'ansprechpartner'   => $row['ansprechpartner'] ?? null,
                    'hersteller'        => $row['Hersteller'] ?? $row['hersteller'] ?? null,
                    'revision_date'     => !empty($row['revision_date']) ? $row['revision_date'] : null,
                    'doc_url'           => $row['doc_url'] ?? null,
                    'updated_by'        => $row['updated_by'] ?? null,
                    'created_at'        => $row['created_at'] ?? now(),
                    'updated_at'        => $row['updated_at'] ?? now(),
                ]
            );
            $imported++;
        }

        $this->line("   Importiert: {$imported} | Admins zugeordnet: {$adminMatched}");
    }
}
