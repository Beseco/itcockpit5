<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create modules
        $baseModule = Module::firstOrCreate(
            ['name' => 'base'],
            [
                'display_name' => 'Basismodul',
                'description' => 'Globale Funktionen wie Benutzerverwaltung, Rollenverwaltung und Modulverwaltung',
                'is_active' => true,
            ]
        );

        $announcementsModule = Module::firstOrCreate(
            ['name' => 'announcements'],
            [
                'display_name' => 'Ankündigungen',
                'description' => 'Verwaltung von Ankündigungen und Mitteilungen',
                'is_active' => true,
            ]
        );

        $auditModule = Module::firstOrCreate(
            ['name' => 'audit'],
            [
                'display_name' => 'Auditprotokoll',
                'description' => 'Protokollierung und Überwachung von Systemaktivitäten',
                'is_active' => true,
            ]
        );

        $networkModule = Module::firstOrCreate(
            ['name' => 'network'],
            [
                'display_name' => 'Netzwerkverwaltung',
                'description' => 'Verwaltung von Netzwerkressourcen und -konfigurationen',
                'is_active' => true,
            ]
        );

        $hhModule = Module::firstOrCreate(
            ['name' => 'hh'],
            [
                'display_name' => 'Haushaltsplanung',
                'description' => 'Verwaltung von Haushaltsbudgets und Kostenstellen',
                'is_active' => true,
            ]
        );

        $exampleModule = Module::firstOrCreate(
            ['name' => 'example'],
            [
                'display_name' => 'Example Module',
                'description' => 'Beispielmodul für Demonstrationszwecke',
                'is_active' => true,
            ]
        );

        // Create base module permissions
        $basePermissions = [
            ['name' => 'base.users.view', 'module_id' => $baseModule->id],
            ['name' => 'base.users.create', 'module_id' => $baseModule->id],
            ['name' => 'base.users.edit', 'module_id' => $baseModule->id],
            ['name' => 'base.users.delete', 'module_id' => $baseModule->id],
            ['name' => 'base.roles.view', 'module_id' => $baseModule->id],
            ['name' => 'base.roles.create', 'module_id' => $baseModule->id],
            ['name' => 'base.roles.edit', 'module_id' => $baseModule->id],
            ['name' => 'base.roles.delete', 'module_id' => $baseModule->id],
            ['name' => 'base.modules.view', 'module_id' => $baseModule->id],
            ['name' => 'base.modules.manage', 'module_id' => $baseModule->id],
        ];

        // Create announcements module permissions
        $announcementsPermissions = [
            ['name' => 'announcements.view', 'module_id' => $announcementsModule->id],
            ['name' => 'announcements.create', 'module_id' => $announcementsModule->id],
            ['name' => 'announcements.edit', 'module_id' => $announcementsModule->id],
            ['name' => 'announcements.delete', 'module_id' => $announcementsModule->id],
        ];

        // Create audit module permissions
        $auditPermissions = [
            ['name' => 'audit.view', 'module_id' => $auditModule->id],
        ];

        // Create network module permissions
        $networkPermissions = [
            ['name' => 'network.view', 'module_id' => $networkModule->id],
            ['name' => 'network.edit', 'module_id' => $networkModule->id],
        ];

        // Create hh module permissions
        $hhPermissions = [
            ['name' => 'hh.view', 'module_id' => $hhModule->id],
            ['name' => 'hh.edit', 'module_id' => $hhModule->id],
            ['name' => 'hh.approve', 'module_id' => $hhModule->id],
        ];

        // Create example module permissions
        $examplePermissions = [
            ['name' => 'example.view', 'module_id' => $exampleModule->id],
            ['name' => 'example.edit', 'module_id' => $exampleModule->id],
        ];

        // Combine all permissions
        $allPermissions = array_merge(
            $basePermissions,
            $announcementsPermissions,
            $auditPermissions,
            $networkPermissions,
            $hhPermissions,
            $examplePermissions
        );

        // Create all permissions
        foreach ($allPermissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                ['module_id' => $permissionData['module_id']]
            );
        }

        // Create Superadministrator role (no explicit permissions, handled by Gate::before)
        Role::firstOrCreate(['name' => 'Superadministrator']);

        // Create Admin role with all base and module permissions
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->syncPermissions(array_column($allPermissions, 'name'));

        // Create Abteilungsleiter HH role
        $abteilungsleiterHH = Role::firstOrCreate(['name' => 'Abteilungsleiter HH']);
        $abteilungsleiterHH->syncPermissions([
            'hh.view',
            'hh.edit',
            'hh.approve',
            'audit.view',
        ]);

        // Create Mitarbeiter HH role
        $mitarbeiterHH = Role::firstOrCreate(['name' => 'Mitarbeiter HH']);
        $mitarbeiterHH->syncPermissions([
            'hh.view',
            'hh.edit',
        ]);

        // Create Netzwerk-Editor role
        $netzwerkEditor = Role::firstOrCreate(['name' => 'Netzwerk-Editor']);
        $netzwerkEditor->syncPermissions([
            'network.view',
            'network.edit',
            'audit.view',
        ]);

        // Create Redaktion role
        $redaktion = Role::firstOrCreate(['name' => 'Redaktion']);
        $redaktion->syncPermissions([
            'announcements.view',
            'announcements.create',
            'announcements.edit',
            'announcements.delete',
            'audit.view',
        ]);

        // Create Viewer role
        $viewer = Role::firstOrCreate(['name' => 'Viewer']);
        $viewer->syncPermissions([
            'announcements.view',
            'network.view',
            'audit.view',
        ]);
    }
}
