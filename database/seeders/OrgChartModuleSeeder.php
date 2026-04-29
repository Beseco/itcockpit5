<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class OrgChartModuleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $module = Module::firstOrCreate(
            ['name' => 'orgchart'],
            [
                'display_name' => 'Organigramm-Planer',
                'description'  => 'Organisationsszenarien modellieren und grafisch visualisieren',
                'is_active'    => true,
            ]
        );

        $viewPerm = Permission::firstOrCreate(
            ['name' => 'orgchart.view'],
            ['module_id' => $module->id]
        );

        $editPerm = Permission::firstOrCreate(
            ['name' => 'orgchart.edit'],
            ['module_id' => $module->id]
        );

        // SuperAdmin und Admin erhalten beide Rechte
        foreach (['super-admin', 'admin'] as $roleName) {
            $role = Role::firstWhere('name', $roleName);
            if ($role) {
                $role->givePermissionTo([$viewPerm, $editPerm]);
            }
        }
    }
}
