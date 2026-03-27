<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ServerModuleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- Modul anlegen ---

        $serverModule = Module::firstOrCreate(
            ['name' => 'server'],
            [
                'display_name' => 'Server',
                'description'  => 'Serververwaltung – manuelle Pflege und LDAP-Synchronisation von Computerkonten',
                'is_active'    => true,
            ]
        );

        // --- Berechtigungen anlegen ---

        $permissions = [
            ['name' => 'server.view',   'module_id' => $serverModule->id],
            ['name' => 'server.create', 'module_id' => $serverModule->id],
            ['name' => 'server.edit',   'module_id' => $serverModule->id],
            ['name' => 'server.delete', 'module_id' => $serverModule->id],
            ['name' => 'server.sync',   'module_id' => $serverModule->id],
            ['name' => 'server.config', 'module_id' => $serverModule->id],
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
            $superadmin->givePermissionTo(array_column($permissions, 'name'));
        }

        // Admin bekommt View, Create, Edit, Sync, Config (kein Delete)
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo([
                'server.view', 'server.create', 'server.edit',
                'server.sync', 'server.config',
            ]);
        }

        // --- Server-Optionen vorbefüllen ---

        $seeds = [
            'os_type' => [
                'Linux', 'Windows', 'macOS', 'Sonstiges',
            ],
            'role' => [
                'Fachverfahren', 'Mail', 'Softwareverteilung',
            ],
            'backup_level' => [
                'Gold', 'Silber', 'Bronze',
            ],
            'patch_ring' => [
                'Ring1', 'Ring2', 'Ring3',
            ],
        ];

        foreach ($seeds as $category => $labels) {
            foreach ($labels as $i => $label) {
                DB::table('server_options')->insertOrIgnore([
                    'category'   => $category,
                    'label'      => $label,
                    'sort_order' => $i + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('✓ Modul "server" registriert');
        $this->command->info('✓ 6 Permissions angelegt (server.view/create/edit/delete/sync/config)');
        $this->command->info('✓ Server-Optionen vorbefüllt (os_type, role, backup_level, patch_ring)');
    }
}
