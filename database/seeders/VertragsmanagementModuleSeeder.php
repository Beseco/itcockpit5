<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class VertragsmanagementModuleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $module = Module::firstOrCreate(
            ['name' => 'vertragsmanagement'],
            [
                'display_name' => 'Vertragsmanagement',
                'description'  => 'IT-Verträge verwalten – Laufzeiten, Kündigungsfristen, Dokumente und automatische Erinnerungen',
                'is_active'    => true,
            ]
        );

        $permissions = [
            ['name' => 'vertragsmanagement.view',   'module_id' => $module->id],
            ['name' => 'vertragsmanagement.edit',   'module_id' => $module->id],
            ['name' => 'vertragsmanagement.config', 'module_id' => $module->id],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                ['module_id' => $perm['module_id']]
            );
        }

        foreach (['superadmin', 'admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo([
                    'vertragsmanagement.view',
                    'vertragsmanagement.edit',
                    'vertragsmanagement.config',
                ]);
            }
        }

        $this->command->info('✓ Modul "vertragsmanagement" registriert');
        $this->command->info('✓ 3 Permissions angelegt (vertragsmanagement.view/edit/config)');
    }
}
