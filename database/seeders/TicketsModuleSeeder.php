<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TicketsModuleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- Modul anlegen ---

        $module = Module::firstOrCreate(
            ['name' => 'tickets'],
            [
                'display_name' => 'Tickets',
                'description'  => 'Zammad-Ticketanbindung – Tickets einsehen und Einstellungen konfigurieren',
                'is_active'    => true,
            ]
        );

        // --- Berechtigungen anlegen ---

        $permissions = [
            ['name' => 'tickets.view',   'module_id' => $module->id],
            ['name' => 'tickets.config', 'module_id' => $module->id],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                ['module_id' => $perm['module_id']]
            );
        }

        // Superadmin bekommt alle Permissions
        $superadmin = Role::where('name', 'superadmin')->first();
        if ($superadmin) {
            $superadmin->givePermissionTo(['tickets.view', 'tickets.config']);
        }

        // Admin bekommt View und Config
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo(['tickets.view', 'tickets.config']);
        }

        $this->command->info('✓ Modul "tickets" registriert');
        $this->command->info('✓ 2 Permissions angelegt (tickets.view, tickets.config)');
    }
}
