<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class ImportLegacyData extends Command
{
    protected $signature   = 'import:legacy-data {--force : Vorhandene Datensätze überschreiben}';
    protected $description = 'Importiert Daten aus der alten DB (ticketsystem_db1) in die neue DB (itcockpit)';

    private PDO $oldPdo;

    public function handle(): int
    {
        $this->info('=== Legacy-Daten Import ===');

        // Verbindung zur alten DB
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

        $force = $this->option('force');

        // Reihenfolge wichtig wegen FK-Abhängigkeiten
        $this->importCostCenters($force);
        $this->importAccountCodes($force);
        $this->importDienstleister($force);
        $this->importOrders($force);
        $this->importOrderHistory($force);
        $this->importErinnerungsmails($force);
        $this->importApplikationen($force);

        $this->info('');
        $this->info('✓ Import abgeschlossen.');
        return self::SUCCESS;
    }

    private function importCostCenters(bool $force): void
    {
        $this->info('');
        $this->info('→ Kostenstellen (it_cost_centers)...');

        $rows = $this->oldPdo->query('SELECT * FROM it_cost_centers')->fetchAll(PDO::FETCH_ASSOC);
        $imported = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            $exists = DB::table('it_cost_centers')->where('id', $row['id'])->exists();

            if ($exists && !$force) {
                $skipped++;
                continue;
            }

            DB::table('it_cost_centers')->updateOrInsert(
                ['id' => $row['id']],
                [
                    'number'      => $row['number'],
                    'description' => $row['description'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]
            );
            $imported++;
        }

        $this->line("   Importiert: {$imported} | Übersprungen: {$skipped}");
    }

    private function importAccountCodes(bool $force): void
    {
        $this->info('');
        $this->info('→ Sachkonten (it_account_codes)...');

        $rows = $this->oldPdo->query('SELECT * FROM it_account_codes')->fetchAll(PDO::FETCH_ASSOC);
        $imported = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            $exists = DB::table('it_account_codes')->where('id', $row['id'])->exists();

            if ($exists && !$force) {
                $skipped++;
                continue;
            }

            DB::table('it_account_codes')->updateOrInsert(
                ['id' => $row['id']],
                [
                    'code'        => $row['code'],
                    'description' => $row['description'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]
            );
            $imported++;
        }

        $this->line("   Importiert: {$imported} | Übersprungen: {$skipped}");
    }

    private function importDienstleister(bool $force): void
    {
        $this->info('');
        $this->info('→ Dienstleister...');

        $rows = $this->oldPdo->query('SELECT * FROM dienstleister')->fetchAll(PDO::FETCH_ASSOC);
        $imported = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            $exists = DB::table('dienstleister')->where('id', $row['id'])->exists();

            if ($exists && !$force) {
                $skipped++;
                continue;
            }

            DB::table('dienstleister')->updateOrInsert(
                ['id' => $row['id']],
                [
                    'firmenname'                         => $row['firmenname'],
                    'strasse'                            => $row['strasse'] ?? null,
                    'plz'                                => $row['plz'] ?? null,
                    'ort'                                => $row['ort'] ?? null,
                    'land'                               => $row['land'] ?? 'Deutschland',
                    'website'                            => $row['website'] ?? null,
                    'email'                              => $row['email'] ?? null,
                    'telefon'                            => $row['telefon'] ?? null,
                    'bemerkungen'                        => $row['bemerkungen'] ?? null,
                    'dienstleister_typ'                  => $row['dienstleister_typ'] ?? null,
                    'leistungsbeschreibung'              => $row['leistungsbeschreibung'] ?? null,
                    'fachgebiet'                         => $row['fachgebiet'] ?? null,
                    'kritischer_dienstleister'           => $row['kritischer_dienstleister'] ?? 0,
                    'verarbeitet_personenbezogene_daten' => $row['verarbeitet_personenbezogene_daten'] ?? 0,
                    'av_vertrag_vorhanden'               => $row['av_vertrag_vorhanden'] ?? 0,
                    'av_vertrag_datum'                   => $row['av_vertrag_datum'] ?? null,
                    'av_bemerkungen'                     => $row['av_bemerkungen'] ?? null,
                    'status'                             => $row['status'] ?? 'aktiv',
                    'bewertung_gesamt'                   => $row['bewertung_gesamt'] ?? null,
                    'bewertung_fachlich'                 => $row['bewertung_fachlich'] ?? null,
                    'bewertung_zuverlaessigkeit'         => $row['bewertung_zuverlaessigkeit'] ?? null,
                    'empfehlung'                         => $row['empfehlung'] ?? 0,
                    'bewertungsnotiz'                    => $row['bewertungsnotiz'] ?? null,
                    'verantwortliche_stelle'             => $row['verantwortliche_stelle'] ?? null,
                    'angelegt_am'                        => $row['angelegt_am'] ?? null,
                    'aktualisiert_am'                    => $row['aktualisiert_am'] ?? null,
                    'created_at'                         => $row['angelegt_am'] ?? now(),
                    'updated_at'                         => $row['aktualisiert_am'] ?? now(),
                ]
            );
            $imported++;
        }

        $this->line("   Importiert: {$imported} | Übersprungen: {$skipped}");
    }

    /**
     * Mappt alte Text-Status auf neue numerische Werte (1–6)
     */
    private function mapStatus(string $status): int
    {
        return match (strtolower(trim($status))) {
            'bestellt'              => 1,
            'geliefert'             => 2,
            'bestätigt', 'bestaetigt', 'in bearbeitung', 'bearbeitung' => 2,
            'in inventarisierung'   => 3,
            'im rechnungsworkflow', 'rechnungsworkflow' => 4,
            'feststellung'          => 5,
            'angeordnet', 'bezahlt' => 6,
            default                 => is_numeric($status) ? (int) $status : 1,
        };
    }

    private function importOrders(bool $force): void
    {
        $this->info('');
        $this->info('→ Bestellungen (it_orders)...');

        $rows = $this->oldPdo->query('SELECT * FROM it_orders')->fetchAll(PDO::FETCH_ASSOC);
        $imported = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            $exists = DB::table('it_orders')->where('id', $row['id'])->exists();

            if ($exists && !$force) {
                $skipped++;
                continue;
            }

            DB::table('it_orders')->updateOrInsert(
                ['id' => $row['id']],
                [
                    'subject'          => $row['subject'],
                    'quantity'         => (int) $row['quantity'],
                    'price_gross'      => (float) $row['price_gross'],
                    'order_date'       => $row['order_date'],
                    'vendor_id'        => $row['vendor_id'] ?: null,
                    'cost_center_id'   => $row['cost_center_id'] ?: null,
                    'account_code_id'  => $row['account_code_id'] ?: null,
                    'buyer_username'   => $row['buyer_username'] ?? null,
                    'status'           => $this->mapStatus((string) $row['status']),
                    'bemerkungen'      => $row['bemerkungen'] ?? null,
                    'status_updated_at' => $row['status_updated_at'] ?? null,
                    'created_at'       => $row['created_at'] ?? now(),
                    'updated_at'       => $row['created_at'] ?? now(),
                ]
            );
            $imported++;
        }

        $this->line("   Importiert: {$imported} | Übersprungen: {$skipped}");
    }

    private function importOrderHistory(bool $force): void
    {
        $this->info('');
        $this->info('→ Bestellhistorie (it_order_history)...');

        $rows = $this->oldPdo->query('SELECT * FROM it_order_history')->fetchAll(PDO::FETCH_ASSOC);
        $imported = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            $exists = DB::table('it_order_history')->where('id', $row['id'])->exists();

            if ($exists && !$force) {
                $skipped++;
                continue;
            }

            // Alte DB: changed_at → created_at
            DB::table('it_order_history')->updateOrInsert(
                ['id' => $row['id']],
                [
                    'order_id'   => $row['order_id'],
                    'changed_by' => $row['changed_by'],
                    'field'      => $row['field'],
                    'old_value'  => $row['old_value'] ?? null,
                    'new_value'  => $row['new_value'] ?? null,
                    'created_at' => $row['changed_at'] ?? now(),
                    'updated_at' => $row['changed_at'] ?? now(),
                ]
            );
            $imported++;
        }

        $this->line("   Importiert: {$imported} | Übersprungen: {$skipped}");
    }

    private function importApplikationen(bool $force): void
    {
        $this->info('');
        $this->info('→ Applikationen...');

        $rows = $this->oldPdo->query('SELECT * FROM applikationen')->fetchAll(PDO::FETCH_ASSOC);
        $imported = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            $exists = DB::table('applikationen')->where('id', $row['id'])->exists();

            if ($exists && !$force) {
                $skipped++;
                continue;
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
                    'admin'             => $row['admin'] ?? null,
                    'ansprechpartner'   => $row['ansprechpartner'] ?? null,
                    'hersteller'        => $row['Hersteller'] ?? null, // Altes Feld: Großschreibung
                    'revision_date'     => !empty($row['revision_date']) ? $row['revision_date'] : null,
                    'doc_url'           => $row['doc_url'] ?? null,
                    'updated_by'        => $row['updated_by'] ?? null,
                    'created_at'        => $row['created_at'] ?? now(),
                    'updated_at'        => $row['updated_at'] ?? now(),
                ]
            );
            $imported++;
        }

        $this->line("   Importiert: {$imported} | Übersprungen: {$skipped}");
    }

    private function importErinnerungsmails(bool $force): void
    {
        $this->info('');
        $this->info('→ Erinnerungsmails (erinnerungsmail)...');

        $rows = $this->oldPdo->query('SELECT * FROM erinnerungsmail')->fetchAll(PDO::FETCH_ASSOC);
        $imported = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            $exists = DB::table('erinnerungsmail')->where('id', $row['id'])->exists();

            if ($exists && !$force) {
                $skipped++;
                continue;
            }

            // nextsend war Unix-Timestamp in alter DB → datetime konvertieren
            $nextsend = date('Y-m-d H:i:s', (int) $row['nextsend']);

            DB::table('erinnerungsmail')->updateOrInsert(
                ['id' => $row['id']],
                [
                    'user_id'           => $row['userid'] ?? null,
                    'status'            => (int) ($row['status'] ?? 1),
                    'titel'             => $row['titel'],
                    'nachricht'         => $row['nachricht'],
                    'nextsend'          => $nextsend,
                    'mailto'            => $row['mailto'],
                    'intervall_nummer'  => (int) ($row['intervall_nummer'] ?? 1),
                    'intervall_faktor'  => (int) ($row['intervall_faktor'] ?? 86400),
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]
            );
            $imported++;
        }

        $this->line("   Importiert: {$imported} | Übersprungen: {$skipped}");
    }
}
