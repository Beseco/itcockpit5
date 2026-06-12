<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BaramundiModuleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $module = Module::firstOrCreate(
            ['name' => 'baramundi'],
            [
                'display_name' => 'Baramundi',
                'description'  => 'Baramundi Paketüberwachung – neue Softwareversionen erkennen und Benachrichtigungen verwalten',
                'is_active'    => true,
            ]
        );

        $permissions = [
            ['name' => 'baramundi.view',   'module_id' => $module->id],
            ['name' => 'baramundi.edit',   'module_id' => $module->id],
            ['name' => 'baramundi.config', 'module_id' => $module->id],
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
                $role->givePermissionTo(['baramundi.view', 'baramundi.edit', 'baramundi.config']);
            }
        }

        $this->command->info('✓ Modul "baramundi" registriert');
        $this->command->info('✓ 3 Permissions angelegt (baramundi.view, baramundi.edit, baramundi.config)');
    }
}
