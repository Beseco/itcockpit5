<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class OnboardingModuleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $module = Module::firstOrCreate(
            ['name' => 'onboarding'],
            [
                'display_name' => 'Onboarding',
                'description'  => 'AD-Benutzer anlegen: Vorlagen pro OE, automatische Attribute, E-Mails und Audit-Log',
                'is_active'    => true,
            ]
        );

        $permissions = [
            ['name' => 'onboarding.view',   'module_id' => $module->id],
            ['name' => 'onboarding.edit',   'module_id' => $module->id],
            ['name' => 'onboarding.config', 'module_id' => $module->id],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                ['module_id' => $perm['module_id']]
            );
        }

        // Superadmin bekommt alle Onboarding-Permissions
        $superadmin = Role::where('name', 'superadmin')->first();
        if ($superadmin) {
            $superadmin->givePermissionTo(['onboarding.view', 'onboarding.edit', 'onboarding.config']);
        }

        // Admin bekommt View + Edit (kein config)
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo(['onboarding.view', 'onboarding.edit']);
        }

        $this->command->info('Onboarding-Modul: Permissions angelegt und Rollen aktualisiert.');
    }
}
