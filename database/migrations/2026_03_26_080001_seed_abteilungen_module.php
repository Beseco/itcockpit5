<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modul anlegen
        $moduleId = DB::table('modules')->insertGetId([
            'name'         => 'abteilungen',
            'display_name' => 'Abteilungen',
            'description'  => 'Verwaltung von Abteilungen und Sachgebieten (hierarchisch)',
            'is_active'    => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Permissions anlegen
        $permissions = [
            'abteilungen.view',
            'abteilungen.create',
            'abteilungen.edit',
            'abteilungen.delete',
        ];

        foreach ($permissions as $perm) {
            DB::table('permissions')->insertOrIgnore([
                'name'       => $perm,
                'guard_name' => 'web',
                'module_id'  => $moduleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', [
            'abteilungen.view',
            'abteilungen.create',
            'abteilungen.edit',
            'abteilungen.delete',
        ])->delete();

        DB::table('modules')->where('name', 'abteilungen')->delete();
    }
};
