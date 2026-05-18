<?php

namespace Database\Seeders;

use App\Modules\Schulen\Models\DienstKategorie;
use App\Modules\Schulen\Models\Dienstleistung;
use App\Modules\Schulen\Models\Schule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Importiert alle Schulen, Dienstleistungen und Matrix-Daten
 * aus der Services-Matrix 2026.
 *
 * Voraussetzung: SchulenModuleSeeder wurde bereits ausgeführt.
 * Ausführen: keyhelp-php83 artisan db:seed --class=SchulenDataSeeder
 */
class SchulenDataSeeder extends Seeder
{
    // Status-Kürzel → Enum-Wert
    private const S = [
        'a'  => 'aktiv',
        'p'  => 'geplant',
        'ng' => 'nicht_gewuenscht',
        'nm' => 'nicht_moeglich',
        ''   => 'nicht_vorhanden',
    ];

    public function run(): void
    {
        // ─── 1. Kategorien sicherstellen ────────────────────────────────────
        $kategorien = $this->upsertKategorien();

        // ─── 2. Dienstleistungen anlegen ────────────────────────────────────
        $dienste = $this->upsertDienstleistungen($kategorien);

        // ─── 3. Schulen anlegen ─────────────────────────────────────────────
        $schulen = $this->upsertSchulen();

        // ─── 4. Matrix-Daten eintragen ──────────────────────────────────────
        $this->importMatrix($schulen, $dienste);

        $this->command->info('✓ ' . count($schulen) . ' Schulen angelegt');
        $this->command->info('✓ ' . count($dienste) . ' Dienstleistungen angelegt');
        $this->command->info('✓ Matrix-Daten importiert');
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function upsertKategorien(): array
    {
        $defs = [
            'Internet'                   => 1,
            'LAN'                        => 2,
            'WLAN'                       => 3,
            'Telefon'                    => 4,
            'Klassenzimmer / AV-Technik' => 5,
            'Verwaltung'                 => 6,
            'Software'                   => 7,
        ];
        $result = [];
        foreach ($defs as $name => $sort) {
            $result[$name] = DienstKategorie::firstOrCreate(
                ['name' => $name],
                ['sort_order' => $sort]
            );
        }
        return $result;
    }

    private function upsertDienstleistungen(array $kategorien): array
    {
        $defs = [
            // Internet
            ['kat' => 'Internet', 'name' => 'Glasfaser Telekom',              'sort' => 1],
            ['kat' => 'Internet', 'name' => 'Glasfasernetz Freising',         'sort' => 2],
            ['kat' => 'Internet', 'name' => 'Firewall Standard',              'sort' => 3],
            ['kat' => 'Internet', 'name' => 'Webhosting',                     'sort' => 4],

            // LAN
            ['kat' => 'LAN', 'name' => 'VPN-Verwaltungsnetz',                'sort' => 1],
            ['kat' => 'LAN', 'name' => 'Serverhosting',                      'sort' => 2],
            ['kat' => 'LAN', 'name' => 'Software as a Service (ASV und NM)', 'sort' => 3],
            ['kat' => 'LAN', 'name' => 'Arbeitsplatz',                       'sort' => 4],
            ['kat' => 'LAN', 'name' => 'Homeoffice',                         'sort' => 5],
            ['kat' => 'LAN', 'name' => 'LAN',                                'sort' => 6],
            ['kat' => 'LAN', 'name' => 'Standard Hardware (LAN)',             'sort' => 7],
            ['kat' => 'LAN', 'name' => 'Strukturierte Verkabelung',           'sort' => 8],
            ['kat' => 'LAN', 'name' => '10 Gbit Glasfaser Ausbau',           'sort' => 9],
            ['kat' => 'LAN', 'name' => 'Serverraum Standardisiert',          'sort' => 10],
            ['kat' => 'LAN', 'name' => 'Netzwerkverteiler Standardisiert',   'sort' => 11],
            ['kat' => 'LAN', 'name' => 'Zugang',                             'sort' => 12],
            ['kat' => 'LAN', 'name' => 'USV Versorgung',                     'sort' => 13],
            ['kat' => 'LAN', 'name' => 'Server / Netzwerk Monitoring',       'sort' => 14],
            ['kat' => 'LAN', 'name' => 'Netzwerksegmentierung päd. Netz',    'sort' => 15],
            ['kat' => 'LAN', 'name' => 'Netzwerksegmentierung Verwaltungsnetz','sort' => 16],
            ['kat' => 'LAN', 'name' => 'Netzwerksegmentierung Telefon',      'sort' => 17],
            ['kat' => 'LAN', 'name' => 'Netzwerksegmentierung Haustechnik',  'sort' => 18],

            // WLAN
            ['kat' => 'WLAN', 'name' => 'WLAN',                'sort' => 1],
            ['kat' => 'WLAN', 'name' => 'Standard Hardware (WLAN)', 'sort' => 2],
            ['kat' => 'WLAN', 'name' => 'Vollversorgung',       'sort' => 3],
            ['kat' => 'WLAN', 'name' => 'Gäste-WLAN',          'sort' => 4],
            ['kat' => 'WLAN', 'name' => 'Lehrer-WLAN',         'sort' => 5],
            ['kat' => 'WLAN', 'name' => 'Schüler-WLAN',        'sort' => 6],
            ['kat' => 'WLAN', 'name' => 'BYOD',                'sort' => 7],

            // Telefon
            ['kat' => 'Telefon', 'name' => 'Zentrale Telefonanlage Swyx', 'sort' => 1],
            ['kat' => 'Telefon', 'name' => 'Klassenzimmertelefone',       'sort' => 2],

            // Klassenzimmer / AV-Technik
            ['kat' => 'Klassenzimmer / AV-Technik', 'name' => 'Digitale Tafeln (Hersteller A)',    'sort' => 1],
            ['kat' => 'Klassenzimmer / AV-Technik', 'name' => 'Digitale Tafeln (Hersteller B)',    'sort' => 2],
            ['kat' => 'Klassenzimmer / AV-Technik', 'name' => 'Soundlösung',                       'sort' => 3],
            ['kat' => 'Klassenzimmer / AV-Technik', 'name' => 'Lehrer PC und Zubehör',             'sort' => 4],
            ['kat' => 'Klassenzimmer / AV-Technik', 'name' => 'Apple-TV / Cynaps',                 'sort' => 5],
            ['kat' => 'Klassenzimmer / AV-Technik', 'name' => 'Klassenzimmer Netzwerkdosen',       'sort' => 6],
            ['kat' => 'Klassenzimmer / AV-Technik', 'name' => '2x1 Gbit am Lehrerarbeitsplatz',    'sort' => 7],
            ['kat' => 'Klassenzimmer / AV-Technik', 'name' => '2x1 Gbit am Beamer / Tafelsystem',  'sort' => 8],
            ['kat' => 'Klassenzimmer / AV-Technik', 'name' => '2x1 Gbit für PC im Klassenzimmer', 'sort' => 9],
            ['kat' => 'Klassenzimmer / AV-Technik', 'name' => 'Klassenzimmer WLAN LAN Internet',   'sort' => 10],

            // Verwaltung
            ['kat' => 'Verwaltung', 'name' => 'Verwaltungsnetz',                 'sort' => 1],
            ['kat' => 'Verwaltung', 'name' => 'Verwaltungsnetzwerk Computer',    'sort' => 2],
            ['kat' => 'Verwaltung', 'name' => 'Drucker, MFG, Wartungsvertrag',   'sort' => 3],

            // Software
            ['kat' => 'Software', 'name' => 'FWU Vertrag Campus',                           'sort' => 1],
            ['kat' => 'Software', 'name' => 'Pädagogisches System über 73\'s (Schuladmin)', 'sort' => 2],
            ['kat' => 'Software', 'name' => 'Pädagogisches System Wartung',                 'sort' => 3],
            ['kat' => 'Software', 'name' => 'Virenscanner',                                 'sort' => 4],
            ['kat' => 'Software', 'name' => 'Softwareverteilung im Verwaltungsnetz',        'sort' => 5],
            ['kat' => 'Software', 'name' => 'iPad Klassen',                                 'sort' => 6],
            ['kat' => 'Software', 'name' => 'MDM (Tafeln, Cynaps, Lehrerdienstgeräte)',     'sort' => 7],
            ['kat' => 'Software', 'name' => 'iPad Koffer',                                  'sort' => 8],
            ['kat' => 'Software', 'name' => 'Ticketsystem',                                 'sort' => 9],
            ['kat' => 'Software', 'name' => 'ServiceDesk Schüler',                          'sort' => 10],
            ['kat' => 'Software', 'name' => 'ServiceDesk Lehrer',                           'sort' => 11],
            ['kat' => 'Software', 'name' => 'ServiceDesk Verwaltung',                       'sort' => 12],
            ['kat' => 'Software', 'name' => 'ASV',                                          'sort' => 13],
            ['kat' => 'Software', 'name' => 'Notenmanager',                                 'sort' => 14],
            ['kat' => 'Software', 'name' => 'Stundenplaner (vUntis)',                       'sort' => 15],
            ['kat' => 'Software', 'name' => 'Bibliothek Software',                          'sort' => 16],
            ['kat' => 'Software', 'name' => 'Mail (Exchange Online / BayernCloud / OX)',    'sort' => 17],
            ['kat' => 'Software', 'name' => 'MS AdminCenter, Entra und Intune',             'sort' => 18],
        ];

        $result = [];
        foreach ($defs as $d) {
            $katId = $kategorien[$d['kat']]->id;
            $obj = Dienstleistung::firstOrCreate(
                ['name' => $d['name'], 'dienst_kategorie_id' => $katId],
                ['sort_order' => $d['sort'], 'is_active' => true]
            );
            $result[$d['name']] = $obj;
        }
        return $result;
    }

    private function upsertSchulen(): array
    {
        $defs = [
            // Realschulen (sort 1-5)
            ['name' => 'Realschule Karl-Meichelbeck',  'kurz' => 'RS Meichelbeck', 'typ' => 'realschule', 'ort' => 'Freising',             'sort' => 1],
            ['name' => 'Realschule Gute Änger',         'kurz' => 'RS Gute Änger',  'typ' => 'realschule', 'ort' => 'Freising',             'sort' => 2],
            ['name' => 'Realschule Moosburg',           'kurz' => 'RS Moosburg',    'typ' => 'realschule', 'ort' => 'Moosburg a.d. Isar',   'sort' => 3],
            ['name' => 'Realschule Eching',             'kurz' => 'RS Eching',      'typ' => 'realschule', 'ort' => 'Eching',               'sort' => 4],
            ['name' => 'Realschule Au in der Hallertau','kurz' => 'RS Au',          'typ' => 'realschule', 'ort' => 'Au in der Hallertau',  'sort' => 5],

            // Gymnasien (sort 1-5)
            ['name' => 'Gymnasium Camerloher',         'kurz' => 'Gym Camerloher',  'typ' => 'gymnasium',  'ort' => 'Freising',             'sort' => 1],
            ['name' => 'Gymnasium DOM',                'kurz' => 'Gym DOM',         'typ' => 'gymnasium',  'ort' => 'Freising',             'sort' => 2],
            ['name' => 'Gymnasium Josef-Hofmiller',    'kurz' => 'Gym J-Hofmiller', 'typ' => 'gymnasium',  'ort' => 'Freising',             'sort' => 3],
            ['name' => 'Gymnasium Moosburg',           'kurz' => 'Gym Moosburg',    'typ' => 'gymnasium',  'ort' => 'Moosburg a.d. Isar',   'sort' => 4],
            ['name' => 'Gymnasium Neufahrn',           'kurz' => 'Gym Neufahrn',    'typ' => 'gymnasium',  'ort' => 'Neufahrn b. Freising', 'sort' => 5],

            // Sonstige (sort 1-4)
            ['name' => 'Berufsschule Freising',              'kurz' => 'BS Freising', 'typ' => 'sonstige', 'ort' => 'Freising', 'sort' => 1],
            ['name' => 'FOS / BOS Freising',                 'kurz' => 'FOS/BOS',     'typ' => 'sonstige', 'ort' => 'Freising', 'sort' => 2],
            ['name' => 'Wirtschaftsschule',                  'kurz' => 'Wirtschaftssch.','typ'=>'sonstige', 'ort' => 'Freising', 'sort' => 3],
            ['name' => 'Sonderpädagogisches Förderzentrum',  'kurz' => 'SPF',         'typ' => 'sonstige', 'ort' => 'Freising', 'sort' => 4],
        ];

        $result = [];
        foreach ($defs as $d) {
            $obj = Schule::firstOrCreate(
                ['name' => $d['name']],
                ['kurzname' => $d['kurz'], 'schultyp' => $d['typ'], 'ort' => $d['ort'], 'sort_order' => $d['sort']]
            );
            // Kurzname nachträglich setzen falls Schule bereits existiert
            if (!$obj->kurzname) {
                $obj->update(['kurzname' => $d['kurz']]);
            }
            $result[$d['name']] = $obj;
        }
        return $result;
    }

    /**
     * Matrix-Daten aus der Services-Matrix 2026 (PDF).
     *
     * Spaltenreihenfolge (Indizes 0–13):
     *  0  RS Karl-Meichelbeck   1  RS Gute Änger      2  RS Moosburg
     *  3  RS Eching             4  RS Au               5  Gym Camerloher
     *  6  Gym DOM               7  Gym Josef-Hofmiller 8  Gym Moosburg
     *  9  Gym Neufahrn         10  Berufsschule        11 FOS/BOS
     * 12  Wirtschaftsschule    13  SPF
     *
     * Status-Kürzel: a=aktiv, p=geplant, ng=nicht_gewuenscht,
     *                nm=nicht_moeglich, ''=nicht_vorhanden (kein Eintrag)
     */
    private function importMatrix(array $schulen, array $dienste): void
    {
        $schulKeys = [
            0  => 'Realschule Karl-Meichelbeck',
            1  => 'Realschule Gute Änger',
            2  => 'Realschule Moosburg',
            3  => 'Realschule Eching',
            4  => 'Realschule Au in der Hallertau',
            5  => 'Gymnasium Camerloher',
            6  => 'Gymnasium DOM',
            7  => 'Gymnasium Josef-Hofmiller',
            8  => 'Gymnasium Moosburg',
            9  => 'Gymnasium Neufahrn',
            10 => 'Berufsschule Freising',
            11 => 'FOS / BOS Freising',
            12 => 'Wirtschaftsschule',
            13 => 'Sonderpädagogisches Förderzentrum',
        ];

        /**
         * Matrix: [Dienstleistungsname => [s0, s1, ..., s13]]
         * Quelle: Services-Matrix 2026.pdf
         */
        $matrix = [
            // ── Internet ───────────────────────────────────────────────────
            'Glasfaser Telekom' => [
                'a','a','a','a','a',  'a','a','p','a','a',  'a','a','a','a',
            ],
            'Glasfasernetz Freising' => [
                'a','a','nm','nm','nm',  'p','a','a','nm','nm',  'p','p','p','nm',
            ],
            // Firewall Standard: keine Daten in der Matrix → leer lassen

            'Webhosting' => [
                'a','a','','','',  '','','','','',  '','','','',
            ],

            // ── LAN ────────────────────────────────────────────────────────
            'VPN-Verwaltungsnetz' => [
                '','','ng','ng','ng',  'p','ng','','p','',  'ng','ng','','ng',
            ],
            'Serverhosting' => [
                'a','a','ng','ng','',  'a','a','p','ng','ng',  'ng','','','',
            ],
            'Software as a Service (ASV und NM)' => [
                'a','a','ng','ng','ng',  'a','a','p','ng','ng',  'p','ng','','',
            ],
            'Arbeitsplatz' => [
                'a','a','ng','ng','ng',  'a','a','p','ng','ng',  'ng','','','',
            ],
            'Homeoffice' => [
                'a','a','ng','ng','ng',  'p','a','p','ng','ng',  'ng','','','',
            ],
            'LAN' => [
                'a','a','ng','ng','ng',  'a','a','a','ng','a',  'a','a','a','',
            ],

            // ── WLAN ───────────────────────────────────────────────────────
            'WLAN' => [
                'a','a','ng','ng','ng',  'a','a','a','a','a',  'a','a','a','',
            ],

            // ── Telefon ────────────────────────────────────────────────────
            'Zentrale Telefonanlage Swyx' => [
                'a','a','p','p','p',  'a','p','p','a','',  '','','','',
            ],

            // ── Software ───────────────────────────────────────────────────
            'FWU Vertrag Campus' => [
                'a','a','a','a','a',  'a','a','a','a','a',  'a','a','a','a',
            ],
            'Pädagogisches System über 73\'s (Schuladmin)' => [
                'a','a','p','p','',  '','','','','',  '','','','',
            ],
        ];

        $rows = 0;
        foreach ($matrix as $dienstName => $statusArray) {
            if (!isset($dienste[$dienstName])) {
                $this->command->warn("Dienstleistung nicht gefunden: $dienstName");
                continue;
            }
            $dienstId = $dienste[$dienstName]->id;

            foreach ($statusArray as $idx => $kuerzel) {
                if ($kuerzel === '') {
                    continue; // nicht_vorhanden = kein Pivot-Eintrag nötig
                }
                $schulName = $schulKeys[$idx];
                if (!isset($schulen[$schulName])) {
                    continue;
                }
                $schuleId = $schulen[$schulName]->id;
                $status   = self::S[$kuerzel] ?? 'nicht_vorhanden';

                DB::table('schule_dienstleistung')->updateOrInsert(
                    ['schule_id' => $schuleId, 'dienstleistung_id' => $dienstId],
                    [
                        'status'     => $status,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                $rows++;
            }
        }

        $this->command->info("✓ $rows Matrix-Einträge geschrieben");
    }
}
