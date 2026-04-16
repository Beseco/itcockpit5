<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SslCertsModuleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- Modul anlegen ---

        $module = Module::firstOrCreate(
            ['name' => 'sslcerts'],
            [
                'display_name' => 'SSL-Zertifikate',
                'description'  => 'Verwaltung von SSL-Zertifikaten inkl. Ablaufüberwachung',
                'is_active'    => true,
            ]
        );

        // --- Berechtigungen anlegen ---

        $permissions = [
            ['name' => 'sslcerts.view',   'module_id' => $module->id],
            ['name' => 'sslcerts.edit',   'module_id' => $module->id],
            ['name' => 'sslcerts.delete', 'module_id' => $module->id],
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
            $superadmin->givePermissionTo(['sslcerts.view', 'sslcerts.edit', 'sslcerts.delete']);
        }

        // Admin bekommt alle drei
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo(['sslcerts.view', 'sslcerts.edit', 'sslcerts.delete']);
        }

        $this->command->info('✓ Modul "sslcerts" registriert');
        $this->command->info('✓ 3 Permissions angelegt (sslcerts.view, sslcerts.edit, sslcerts.delete)');
    }
}
