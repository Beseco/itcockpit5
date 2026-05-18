<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Modules\Schulen\Models\DienstKategorie;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SchulenModuleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- Modul anlegen ---

        $module = Module::firstOrCreate(
            ['name' => 'schulen'],
            [
                'display_name' => 'Schulen',
                'description'  => 'Schulverwaltung – Dienstleistungsmatrix, Schulen und VZE-Bedarfsrechner',
                'is_active'    => true,
            ]
        );

        // --- Berechtigungen anlegen ---

        $permissions = [
            ['name' => 'schulen.view',   'module_id' => $module->id],
            ['name' => 'schulen.edit',   'module_id' => $module->id],
            ['name' => 'schulen.delete', 'module_id' => $module->id],
            ['name' => 'schulen.config', 'module_id' => $module->id],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                ['module_id' => $perm['module_id']]
            );
        }

        $superadmin = Role::where('name', 'superadmin')->first();
        if ($superadmin) {
            $superadmin->givePermissionTo(array_column($permissions, 'name'));
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo(['schulen.view', 'schulen.edit', 'schulen.config']);
        }

        // --- Dienst-Kategorien vorbefüllen ---

        $kategorien = [
            ['name' => 'Internet',                  'sort_order' => 1],
            ['name' => 'LAN',                       'sort_order' => 2],
            ['name' => 'WLAN',                      'sort_order' => 3],
            ['name' => 'Telefon',                   'sort_order' => 4],
            ['name' => 'Klassenzimmer / AV-Technik','sort_order' => 5],
            ['name' => 'Verwaltung',                'sort_order' => 6],
            ['name' => 'Software',                  'sort_order' => 7],
        ];

        foreach ($kategorien as $kat) {
            DienstKategorie::firstOrCreate(
                ['name' => $kat['name']],
                ['sort_order' => $kat['sort_order']]
            );
        }

        $this->command->info('✓ Modul "schulen" registriert');
        $this->command->info('✓ 4 Permissions angelegt (schulen.view/edit/delete/config)');
        $this->command->info('✓ 7 Dienst-Kategorien vorbefüllt');
    }
}
