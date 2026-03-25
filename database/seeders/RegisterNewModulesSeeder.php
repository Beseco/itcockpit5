<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RegisterNewModulesSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- Module anlegen ---

        $ordersModule = Module::firstOrCreate(
            ['name' => 'orders'],
            [
                'display_name' => 'Bestellverwaltung',
                'description'  => 'Verwaltung von IT-Bestellungen, Kostenstellen und Sachkonten',
                'is_active'    => true,
            ]
        );

        $dienstleisterModule = Module::firstOrCreate(
            ['name' => 'dienstleister'],
            [
                'display_name' => 'Dienstleister',
                'description'  => 'Verwaltung von Lieferanten und Dienstleistern inkl. DSGVO-Bewertung',
                'is_active'    => true,
            ]
        );

        $applikationenModule = Module::firstOrCreate(
            ['name' => 'applikationen'],
            [
                'display_name' => 'Applikationen',
                'description'  => 'Verwaltung von IT-Applikationen inkl. BSI-Schutzbedarf',
                'is_active'    => true,
            ]
        );

        $remindersModule = Module::firstOrCreate(
            ['name' => 'reminders'],
            [
                'display_name' => 'Erinnerungsmails',
                'description'  => 'Verwaltung und automatischer Versand von Erinnerungsmails',
                'is_active'    => true,
            ]
        );

        $gruppenModule = Module::firstOrCreate(
            ['name' => 'gruppen'],
            [
                'display_name' => 'Gruppenverwaltung',
                'description'  => 'Organisationsgruppen mit verschachtelter Struktur und Rollenvererbung',
                'is_active'    => true,
            ]
        );

        $stellenModule = Module::firstOrCreate(
            ['name' => 'stellen'],
            [
                'display_name' => 'Stellen',
                'description'  => 'Stellenverwaltung mit Beschreibungen, TVöD-Bewertung und Arbeitsvorgängen',
                'is_active'    => true,
            ]
        );

        $aufgabenModule = Module::firstOrCreate(
            ['name' => 'aufgaben'],
            [
                'display_name' => 'Rollen & Aufgaben',
                'description'  => 'Hierarchische Zuständigkeitsmatrix der IuK',
                'is_active'    => true,
            ]
        );

        $stellenplanModule = Module::firstOrCreate(
            ['name' => 'stellenplan'],
            [
                'display_name' => 'Stellenplan',
                'description'  => 'Übersicht aller Stellen des Sachgebiets IuK',
                'is_active'    => true,
            ]
        );

        $stellenbeschreibungenModule = Module::firstOrCreate(
            ['name' => 'stellenbeschreibungen'],
            [
                'display_name' => 'Stellenbeschreibungen',
                'description'  => 'Verwaltung von Stellenbeschreibungen und Arbeitsvorgängen',
                'is_active'    => true,
            ]
        );

        $calendarModule = Module::firstOrCreate(
            ['name' => 'calendar'],
            [
                'display_name' => 'Kalender',
                'description'  => 'IT-Kalender für Termine, Wartungen und Ereignisse',
                'is_active'    => true,
            ]
        );

        $fernwartungModule = Module::firstOrCreate(
            ['name' => 'fernwartung'],
            [
                'display_name' => 'Fernwartung',
                'description'  => 'Dokumentation von Fernwartungszugriffen externer Dienstleister',
                'is_active'    => true,
            ]
        );

        $adusersModule = Module::firstOrCreate(
            ['name' => 'adusers'],
            [
                'display_name' => 'AD-Benutzer',
                'description'  => 'Active Directory Benutzerverwaltung – Import, Sync und Anzeige von AD-Benutzern',
                'is_active'    => true,
            ]
        );

        // --- Permissions anlegen ---

        $permissions = [
            // Bestellverwaltung
            ['name' => 'orders.view',              'module_id' => $ordersModule->id],
            ['name' => 'orders.create',            'module_id' => $ordersModule->id],
            ['name' => 'orders.edit',              'module_id' => $ordersModule->id],
            ['name' => 'orders.delete',            'module_id' => $ordersModule->id],
            ['name' => 'cost-centers.view',        'module_id' => $ordersModule->id],
            ['name' => 'cost-centers.manage',      'module_id' => $ordersModule->id],
            ['name' => 'account-codes.view',       'module_id' => $ordersModule->id],
            ['name' => 'account-codes.manage',     'module_id' => $ordersModule->id],

            // Applikationen
            ['name' => 'applikationen.view',   'module_id' => $applikationenModule->id],
            ['name' => 'applikationen.create', 'module_id' => $applikationenModule->id],
            ['name' => 'applikationen.edit',   'module_id' => $applikationenModule->id],
            ['name' => 'applikationen.delete', 'module_id' => $applikationenModule->id],

            // Dienstleister
            ['name' => 'dienstleister.view',       'module_id' => $dienstleisterModule->id],
            ['name' => 'dienstleister.create',     'module_id' => $dienstleisterModule->id],
            ['name' => 'dienstleister.edit',       'module_id' => $dienstleisterModule->id],
            ['name' => 'dienstleister.delete',     'module_id' => $dienstleisterModule->id],

            // Erinnerungsmails
            ['name' => 'reminders.view',           'module_id' => $remindersModule->id],
            ['name' => 'reminders.create',         'module_id' => $remindersModule->id],
            ['name' => 'reminders.edit',           'module_id' => $remindersModule->id],
            ['name' => 'reminders.delete',         'module_id' => $remindersModule->id],

            // Gruppenverwaltung
            ['name' => 'base.gruppen.view',        'module_id' => $gruppenModule->id],
            ['name' => 'base.gruppen.create',      'module_id' => $gruppenModule->id],
            ['name' => 'base.gruppen.edit',        'module_id' => $gruppenModule->id],
            ['name' => 'base.gruppen.delete',      'module_id' => $gruppenModule->id],

            // Stellen
            ['name' => 'base.stellen.view',        'module_id' => $stellenModule->id],
            ['name' => 'base.stellen.create',      'module_id' => $stellenModule->id],
            ['name' => 'base.stellen.edit',        'module_id' => $stellenModule->id],
            ['name' => 'base.stellen.delete',      'module_id' => $stellenModule->id],

            // Aufgaben
            ['name' => 'base.aufgaben.view',       'module_id' => $aufgabenModule->id],
            ['name' => 'base.aufgaben.create',     'module_id' => $aufgabenModule->id],
            ['name' => 'base.aufgaben.edit',       'module_id' => $aufgabenModule->id],
            ['name' => 'base.aufgaben.delete',     'module_id' => $aufgabenModule->id],

            // Stellenplan
            ['name' => 'module.stellenplan.view',           'module_id' => $stellenplanModule->id],
            ['name' => 'module.stellenplan.view_sensitive',  'module_id' => $stellenplanModule->id],

            // Stellenbeschreibungen
            ['name' => 'base.stellenbeschreibungen.view',   'module_id' => $stellenbeschreibungenModule->id],
            ['name' => 'base.stellenbeschreibungen.edit',   'module_id' => $stellenbeschreibungenModule->id],

            // Kalender
            ['name' => 'calendar.view',  'module_id' => $calendarModule->id],
            ['name' => 'calendar.edit',  'module_id' => $calendarModule->id],

            // AD-Benutzer
            ['name' => 'adusers.view',   'module_id' => $adusersModule->id],
            ['name' => 'adusers.delete', 'module_id' => $adusersModule->id],
            ['name' => 'adusers.sync',   'module_id' => $adusersModule->id],
            ['name' => 'adusers.config', 'module_id' => $adusersModule->id],

            // Fernwartung
            ['name' => 'fernwartung.view',         'module_id' => $fernwartungModule->id],
            ['name' => 'fernwartung.create',        'module_id' => $fernwartungModule->id],
            ['name' => 'fernwartung.edit',          'module_id' => $fernwartungModule->id],
            ['name' => 'fernwartung.delete',        'module_id' => $fernwartungModule->id],
            ['name' => 'fernwartung.tools.manage',  'module_id' => $fernwartungModule->id],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                ['module_id' => $perm['module_id']]
            );
        }

        // --- Superadmin bekommt alle neuen Permissions ---

        $superadmin = Role::where('name', 'superadmin')->first();
        if ($superadmin) {
            $newPermissionNames = array_column($permissions, 'name');
            $superadmin->givePermissionTo($newPermissionNames);
        }

        // --- Admin bekommt View + Create + Edit (kein Delete, kein cost-centers/account-codes manage) ---

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $adminPermissions = [
                'applikationen.view', 'applikationen.create', 'applikationen.edit',
                'orders.view', 'orders.create', 'orders.edit',
                'cost-centers.view', 'account-codes.view',
                'dienstleister.view', 'dienstleister.create', 'dienstleister.edit',
                'reminders.view', 'reminders.create', 'reminders.edit',
                'base.gruppen.view', 'base.gruppen.create', 'base.gruppen.edit',
                'base.stellen.view', 'base.stellen.create', 'base.stellen.edit',
                'base.aufgaben.view', 'base.aufgaben.create', 'base.aufgaben.edit',
                'module.stellenplan.view', 'module.stellenplan.view_sensitive',
                'base.stellenbeschreibungen.view', 'base.stellenbeschreibungen.edit',
                'calendar.view', 'calendar.edit',
                'adusers.view', 'adusers.delete', 'adusers.sync', 'adusers.config',
                'fernwartung.view', 'fernwartung.create', 'fernwartung.edit',
                'fernwartung.delete', 'fernwartung.tools.manage',
            ];
            $admin->givePermissionTo($adminPermissions);
        }

        $this->command->info('✓ Module registriert: orders, dienstleister, reminders, gruppen, stellen, aufgaben, stellenplan, stellenbeschreibungen, calendar, fernwartung');
        $this->command->info('✓ ' . count($permissions) . ' Permissions angelegt');
        $this->command->info('✓ Superadmin und Admin aktualisiert');
    }
}
