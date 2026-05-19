<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class WidModuleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $module = Module::firstOrCreate(
            ['name' => 'wid'],
            [
                'display_name' => 'Sicherheitswarnungen',
                'description'  => 'WID-Portal – LSI Bayern Sicherheitswarnungen',
                'is_active'    => true,
            ]
        );

        $permissions = [
            ['name' => 'wid.view',   'module_id' => $module->id],
            ['name' => 'wid.edit',   'module_id' => $module->id],
            ['name' => 'wid.config', 'module_id' => $module->id],
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
            $admin->givePermissionTo(['wid.view', 'wid.config']);
        }

        $this->command->info('✓ Modul "wid" registriert');
        $this->command->info('✓ 3 Permissions angelegt (wid.view/edit/config)');
    }
}
