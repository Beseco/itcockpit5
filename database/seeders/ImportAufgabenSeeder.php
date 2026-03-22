<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImportAufgabenSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------------------
        // 1. Gruppen anlegen (falls nicht vorhanden)
        // ---------------------------------------------------------------
        $gruppenNamen = [
            '14.1 IT-Service Management',
            '14.2 Zentrale Infrastruktur',
            '14.3 eGov, Digitalisierung und IT Verwaltung',
            'Extern',
        ];

        foreach ($gruppenNamen as $i => $name) {
            DB::table('gruppen')->updateOrInsert(
                ['name' => $name],
                ['name' => $name, 'sort_order' => $i + 1, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        // Gruppen-IDs laden
        $gruppen = DB::table('gruppen')->pluck('id', 'name');

        // ---------------------------------------------------------------
        // 2. Aufgaben mit Gruppe definieren
        //    Format: ['name' => '...', 'gruppe' => '...' oder null]
        // ---------------------------------------------------------------
        $aufgaben = [
            // 14.1 IT-Service Management
            ['name' => 'AD Benutzer und Gruppenpflege',                                                          'gruppe' => '14.1 IT-Service Management'],
            ['name' => 'Betriebssystembereitstellung',                                                           'gruppe' => '14.1 IT-Service Management'],
            ['name' => 'Clients',                                                                                 'gruppe' => '14.1 IT-Service Management'],
            ['name' => 'Drucker-, Kopierer- & Scandienste',                                                      'gruppe' => '14.1 IT-Service Management'],
            ['name' => 'Medientechnik (Beamer & Co)',                                                            'gruppe' => '14.1 IT-Service Management'],
            ['name' => 'Mobile Geräte / MDM',                                                                    'gruppe' => '14.1 IT-Service Management'],
            ['name' => 'MS Office',                                                                               'gruppe' => '14.1 IT-Service Management'],
            ['name' => 'Softwareverteilung / Baramundi',                                                          'gruppe' => '14.1 IT-Service Management'],
            ['name' => 'Support (Benutzerverwaltung, Anwenderbetreuung, Dokumentation, Aufbau und Ausgabe von Geräten)', 'gruppe' => '14.1 IT-Service Management'],
            ['name' => 'Support Webseiten',                                                                      'gruppe' => '14.1 IT-Service Management'],
            ['name' => 'Telefonie Alcatel',                                                                      'gruppe' => '14.1 IT-Service Management'],
            ['name' => 'Telefonie Teams',                                                                        'gruppe' => '14.1 IT-Service Management'],
            ['name' => 'Updates Clients [WSUS]',                                                                 'gruppe' => '14.1 IT-Service Management'],
            ['name' => 'Verfahren (Implementierungen, Updates, Ansprechpartner)',                                 'gruppe' => '14.1 IT-Service Management'],

            // 14.2 Zentrale Infrastruktur
            ['name' => 'AD, DNS, DHCP, Freigaben, GPO',                                                         'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'Anbindungen (Behördennetz)',                                                             'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'Applikationsserver',                                                                     'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'Backup',                                                                                  'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'Cloud Datenaustausch',                                                                   'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'DBMS (Datenbanken)',                                                                     'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'DMS Technik',                                                                            'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'E-Mail-Kommunikation',                                                                   'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'Firewall',                                                                                'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'Inventarisierung (Hardware, Software und Lizenzen)',                                      'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'Klimatisierung',                                                                         'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'Linux Systeme',                                                                          'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'Netze (LAN, WLAN)',                                                                      'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'Telefonanlage',                                                                          'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'Terminalserver',                                                                         'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'Updates Server',                                                                         'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'Überwachung (CheckMK & Co.)',                                                            'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'USV, Notstrom',                                                                          'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'Virtuelle Umgebung & Speicher',                                                          'gruppe' => '14.2 Zentrale Infrastruktur'],
            ['name' => 'Webdienste',                                                                             'gruppe' => '14.2 Zentrale Infrastruktur'],

            // 14.3 eGov, Digitalisierung und IT Verwaltung
            ['name' => 'CERT',                                                                                   'gruppe' => '14.3 eGov, Digitalisierung und IT Verwaltung'],
            ['name' => 'DMS Betreuung',                                                                          'gruppe' => '14.3 eGov, Digitalisierung und IT Verwaltung'],
            ['name' => 'DMS Schulung',                                                                           'gruppe' => '14.3 eGov, Digitalisierung und IT Verwaltung'],
            ['name' => 'E-Government',                                                                           'gruppe' => '14.3 eGov, Digitalisierung und IT Verwaltung'],
            ['name' => 'Einwahl (RAS)',                                                                          'gruppe' => '14.3 eGov, Digitalisierung und IT Verwaltung'],
            ['name' => 'Formulare',                                                                              'gruppe' => '14.3 eGov, Digitalisierung und IT Verwaltung'],
            ['name' => 'Lizenzen und Verträge',                                                                  'gruppe' => '14.3 eGov, Digitalisierung und IT Verwaltung'],
            ['name' => 'Verschlüsselung S/MIME',                                                                 'gruppe' => '14.3 eGov, Digitalisierung und IT Verwaltung'],
            ['name' => 'Zertifikate',                                                                            'gruppe' => '14.3 eGov, Digitalisierung und IT Verwaltung'],

            // Extern
            ['name' => 'Aufbau & Entwicklung Webseiten',                                                        'gruppe' => 'Extern'],

            // Keine Gruppe
            ['name' => 'Applikationskontrolle, Gerätesteuerung',                                                'gruppe' => null],
            ['name' => 'Virenscanner',                                                                           'gruppe' => null],
        ];

        // ---------------------------------------------------------------
        // 3. Aufgaben + Zuweisungen speichern
        // ---------------------------------------------------------------
        foreach ($aufgaben as $i => $data) {
            // Aufgabe anlegen (oder bestehende ID holen)
            $existing = DB::table('aufgaben')->where('name', $data['name'])->first();

            if ($existing) {
                $aufgabeId = $existing->id;
            } else {
                $aufgabeId = DB::table('aufgaben')->insertGetId([
                    'name'       => $data['name'],
                    'parent_id'  => null,
                    'sort_order' => $i + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Zuweisung anlegen wenn Gruppe vorhanden
            if ($data['gruppe'] !== null) {
                $gruppeId = $gruppen[$data['gruppe']] ?? null;

                if ($gruppeId) {
                    $zuweisungExists = DB::table('aufgaben_zuweisungen')
                        ->where('aufgabe_id', $aufgabeId)
                        ->where('gruppe_id', $gruppeId)
                        ->exists();

                    if (!$zuweisungExists) {
                        DB::table('aufgaben_zuweisungen')->insert([
                            'aufgabe_id'             => $aufgabeId,
                            'gruppe_id'              => $gruppeId,
                            'admin_user_id'          => null,
                            'stellvertreter_user_id' => null,
                            'created_at'             => now(),
                            'updated_at'             => now(),
                        ]);
                    }
                }
            }
        }

        $this->command->info('✓ ' . count($aufgaben) . ' Aufgaben importiert.');
        $this->command->info('✓ ' . count($gruppenNamen) . ' Gruppen angelegt/geprüft.');
    }
}
