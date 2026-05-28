<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FeedbackModuleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $module = Module::firstOrCreate(
            ['name' => 'feedback'],
            [
                'display_name' => 'Feedback',
                'description'  => 'Anonymes Bewertungssystem für den IT-Support',
                'is_active'    => true,
            ]
        );

        $permissions = [
            ['name' => 'feedback.view',   'module_id' => $module->id],
            ['name' => 'feedback.edit',   'module_id' => $module->id],
            ['name' => 'feedback.delete', 'module_id' => $module->id],
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
            $admin->givePermissionTo(['feedback.view', 'feedback.edit', 'feedback.delete']);
        }

        $this->command->info('✓ Modul "feedback" registriert');
        $this->command->info('✓ 3 Permissions angelegt (feedback.view/edit/delete)');
    }
}
