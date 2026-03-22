<?php

namespace Database\Seeders;

use App\Models\Gruppe;
use App\Models\Stelle;
use App\Models\Stellenbeschreibung;
use App\Models\User;
use Illuminate\Database\Seeder;

class StellenplanSeeder extends Seeder
{
    public function run(): void
    {
        // ── Gruppen ─────────────────────────────────────────────────────────
        $iuk = Gruppe::firstOrCreate(['name' => 'IuK (14)']);
        $sm  = Gruppe::firstOrCreate(['name' => 'Service-Management (14.1)']);
        $zi  = Gruppe::firstOrCreate(['name' => 'Zentrale Infrastruktur (14.2)']);
        $dig = Gruppe::firstOrCreate(['name' => 'Digitalisierung (14.3)']);

        // ── Stellenbeschreibungen ───────────────────────────────────────────
        $sbSGL  = Stellenbeschreibung::firstOrCreate(['bezeichnung' => 'Sachgebietsleiter/in']);
        $sbEDV  = Stellenbeschreibung::firstOrCreate(['bezeichnung' => 'EDV Systembetreuer']);
        $sbGL   = Stellenbeschreibung::firstOrCreate(['bezeichnung' => 'Gruppenleiter/in']);
        $sbTA   = Stellenbeschreibung::firstOrCreate(['bezeichnung' => 'technische Angestellte']);

        // ── Helper ──────────────────────────────────────────────────────────
        $user = fn (string $nachname, string $vorname) =>
            User::whereRaw("name LIKE ?", ["%{$nachname}%"])->first()?->id;

        // Decimal helper: "100,00" → 100.00, "" → null
        $dec = fn (?string $v): ?float => ($v === null || $v === '' || $v === '0,00') ? null : (float) str_replace(',', '.', $v);

        // ── Stellen ─────────────────────────────────────────────────────────
        $stellen = [
            // IuK (14)
            [
                'stellennummer'        => '14.01',
                'stellenbeschreibung'  => $sbSGL,
                'gruppe'               => $iuk,
                'nachname'             => 'Beubl',
                'haushalt_bewertung'   => 'EGr. 13',
                'bes_gruppe'           => 'EGr. 12',
                'belegung'             => '100,00',
                'gesamtarbeitszeit'    => '100,00',
                'anteil_stelle'        => '100,00',
            ],
            [
                'stellennummer'        => '14.02',
                'stellenbeschreibung'  => $sbEDV,
                'gruppe'               => $iuk,
                'nachname'             => 'Strasser',
                'haushalt_bewertung'   => 'EGr. 11',
                'bes_gruppe'           => 'EGr. 11',
                'belegung'             => '65,00',
                'gesamtarbeitszeit'    => '65,00',
                'anteil_stelle'        => '100,00',
            ],
            [
                'stellennummer'        => '14.05',
                'stellenbeschreibung'  => $sbEDV,
                'gruppe'               => $iuk,
                'nachname'             => null,
                'haushalt_bewertung'   => 'EG 9b',
                'bes_gruppe'           => null,
                'belegung'             => null,
                'gesamtarbeitszeit'    => null,
                'anteil_stelle'        => null,
            ],
            [
                'stellennummer'        => '14.07',
                'stellenbeschreibung'  => $sbEDV,
                'gruppe'               => $iuk,
                'nachname'             => 'Koelbl',
                'haushalt_bewertung'   => 'EGr. 10',
                'bes_gruppe'           => 'EGr. 10',
                'belegung'             => '60,00',
                'gesamtarbeitszeit'    => '60,00',
                'anteil_stelle'        => '100,00',
            ],
            [
                'stellennummer'        => '14.08',
                'stellenbeschreibung'  => $sbEDV,
                'gruppe'               => $iuk,
                'nachname'             => 'Gruenberger',
                'haushalt_bewertung'   => 'EGr. 10',
                'bes_gruppe'           => 'EGr. 10',
                'belegung'             => '65,00',
                'gesamtarbeitszeit'    => '65,00',
                'anteil_stelle'        => '100,00',
            ],

            // Service-Management (14.1)
            [
                'stellennummer'        => '14.1.01',
                'stellenbeschreibung'  => $sbGL,
                'gruppe'               => $sm,
                'nachname'             => 'Moch',
                'haushalt_bewertung'   => 'EGr. 11',
                'bes_gruppe'           => 'EGr. 11',
                'belegung'             => '100,00',
                'gesamtarbeitszeit'    => '100,00',
                'anteil_stelle'        => '100,00',
            ],
            [
                'stellennummer'        => '14.1.02',
                'stellenbeschreibung'  => $sbEDV,
                'gruppe'               => $sm,
                'nachname'             => 'Thieme',
                'haushalt_bewertung'   => 'EG 9b',
                'bes_gruppe'           => 'EG 9b',
                'belegung'             => '100,00',
                'gesamtarbeitszeit'    => '100,00',
                'anteil_stelle'        => '100,00',
            ],
            [
                'stellennummer'        => '14.1.03',
                'stellenbeschreibung'  => $sbEDV,
                'gruppe'               => $sm,
                'nachname'             => 'Jahn',
                'haushalt_bewertung'   => 'EGr. 10',
                'bes_gruppe'           => 'EGr. 10',
                'belegung'             => '100,00',
                'gesamtarbeitszeit'    => '100,00',
                'anteil_stelle'        => '100,00',
            ],
            [
                'stellennummer'        => '14.1.04',
                'stellenbeschreibung'  => $sbEDV,
                'gruppe'               => $sm,
                'nachname'             => 'Stefan',
                'haushalt_bewertung'   => 'EG 9b',
                'bes_gruppe'           => 'EG 9b',
                'belegung'             => '100,00',
                'gesamtarbeitszeit'    => '100,00',
                'anteil_stelle'        => '100,00',
            ],
            [
                'stellennummer'        => '14.1.05',
                'stellenbeschreibung'  => $sbEDV,
                'gruppe'               => $sm,
                'nachname'             => 'Heindl',
                'haushalt_bewertung'   => 'EG 9b',
                'bes_gruppe'           => 'EG 9b',
                'belegung'             => '100,00',
                'gesamtarbeitszeit'    => '100,00',
                'anteil_stelle'        => '100,00',
            ],
            [
                'stellennummer'        => '14.1.06',
                'stellenbeschreibung'  => $sbTA,
                'gruppe'               => $sm,
                'nachname'             => 'Kunda',
                'haushalt_bewertung'   => 'EGr. 7',
                'bes_gruppe'           => 'EGr. 7',
                'belegung'             => '100,00',
                'gesamtarbeitszeit'    => '100,00',
                'anteil_stelle'        => '100,00',
            ],

            // Zentrale Infrastruktur (14.2)
            [
                'stellennummer'        => '14.2.01',
                'stellenbeschreibung'  => $sbGL,
                'gruppe'               => $zi,
                'nachname'             => 'Jankovic',
                'haushalt_bewertung'   => 'EGr. 12',
                'bes_gruppe'           => 'EGr. 12',
                'belegung'             => '100,00',
                'gesamtarbeitszeit'    => '100,00',
                'anteil_stelle'        => '100,00',
            ],
            [
                'stellennummer'        => '14.2.02',
                'stellenbeschreibung'  => $sbEDV,
                'gruppe'               => $zi,
                'nachname'             => 'Woerle',
                'haushalt_bewertung'   => 'EGr. 11',
                'bes_gruppe'           => 'EGr. 11',
                'belegung'             => '20,00',
                'gesamtarbeitszeit'    => '20,00',
                'anteil_stelle'        => '100,00',
            ],
            [
                'stellennummer'        => '14.2.03',
                'stellenbeschreibung'  => $sbEDV,
                'gruppe'               => $zi,
                'nachname'             => null,
                'haushalt_bewertung'   => 'EGr. 10',
                'bes_gruppe'           => null,
                'belegung'             => null,
                'gesamtarbeitszeit'    => null,
                'anteil_stelle'        => null,
            ],
            [
                'stellennummer'        => '14.2.04',
                'stellenbeschreibung'  => $sbTA,
                'gruppe'               => $zi,
                'nachname'             => 'Moosreiner',
                'haushalt_bewertung'   => 'EG 9a',
                'bes_gruppe'           => 'EG 9a',
                'belegung'             => '80,00',
                'gesamtarbeitszeit'    => '80,00',
                'anteil_stelle'        => '100,00',
            ],
            [
                'stellennummer'        => '14.2.05',
                'stellenbeschreibung'  => $sbEDV,
                'gruppe'               => $zi,
                'nachname'             => 'Kink',
                'haushalt_bewertung'   => 'EGr. 10',
                'bes_gruppe'           => 'EGr. 10',
                'belegung'             => '100,00',
                'gesamtarbeitszeit'    => '100,00',
                'anteil_stelle'        => '100,00',
            ],

            // Digitalisierung (14.3)
            [
                'stellennummer'        => '14.3.01',
                'stellenbeschreibung'  => $sbGL,
                'gruppe'               => $dig,
                'nachname'             => 'Ostermayr',
                'haushalt_bewertung'   => 'A 12 BayBesG',
                'bes_gruppe'           => 'A 10 BayBesG',
                'belegung'             => '100,00',
                'gesamtarbeitszeit'    => '100,00',
                'anteil_stelle'        => '100,00',
            ],
            [
                'stellennummer'        => '14.3.02',
                'stellenbeschreibung'  => $sbEDV,
                'gruppe'               => $dig,
                'nachname'             => null,
                'haushalt_bewertung'   => 'A 11 BayBesG',
                'bes_gruppe'           => null,
                'belegung'             => null,
                'gesamtarbeitszeit'    => null,
                'anteil_stelle'        => null,
            ],
            [
                'stellennummer'        => '14.3.03',
                'stellenbeschreibung'  => $sbEDV,
                'gruppe'               => $dig,
                'nachname'             => 'Hadersdorfer',
                'haushalt_bewertung'   => 'EGr. 10',
                'bes_gruppe'           => 'EGr. 10',
                'belegung'             => '100,00',
                'gesamtarbeitszeit'    => '100,00',
                'anteil_stelle'        => '100,00',
            ],
            [
                'stellennummer'        => '14.3.04',
                'stellenbeschreibung'  => $sbEDV,
                'gruppe'               => $dig,
                'nachname'             => null,
                'haushalt_bewertung'   => 'EG 9a',
                'bes_gruppe'           => null,
                'belegung'             => null,
                'gesamtarbeitszeit'    => null,
                'anteil_stelle'        => null,
            ],
            [
                'stellennummer'        => '14.3.05',
                'stellenbeschreibung'  => $sbEDV,
                'gruppe'               => $dig,
                'nachname'             => null,
                'haushalt_bewertung'   => 'EGr. 11',
                'bes_gruppe'           => null,
                'belegung'             => null,
                'gesamtarbeitszeit'    => null,
                'anteil_stelle'        => null,
            ],
            [
                'stellennummer'        => '14.3.06',
                'stellenbeschreibung'  => $sbEDV,
                'gruppe'               => $dig,
                'nachname'             => 'Wassermann',
                'haushalt_bewertung'   => 'EG 9b',
                'bes_gruppe'           => 'EG 9b',
                'belegung'             => '100,00',
                'gesamtarbeitszeit'    => '100,00',
                'anteil_stelle'        => '100,00',
            ],
        ];

        foreach ($stellen as $data) {
            $userId = $data['nachname'] ? $user($data['nachname'], '') : null;

            Stelle::updateOrCreate(
                ['stellennummer' => $data['stellennummer']],
                [
                    'stellenbeschreibung_id' => $data['stellenbeschreibung']->id,
                    'gruppe_id'              => $data['gruppe']->id,
                    'user_id'                => $userId,
                    'haushalt_bewertung'     => $data['haushalt_bewertung'],
                    'bes_gruppe'             => $data['bes_gruppe'],
                    'belegung'               => $dec($data['belegung']),
                    'gesamtarbeitszeit'      => $dec($data['gesamtarbeitszeit']),
                    'anteil_stelle'          => $dec($data['anteil_stelle']),
                ]
            );
        }

        $this->command->info('✓ 4 Gruppen angelegt/aktualisiert');
        $this->command->info('✓ 4 Stellenbeschreibungen angelegt/aktualisiert');
        $this->command->info('✓ ' . count($stellen) . ' Stellen angelegt/aktualisiert');
    }
}
