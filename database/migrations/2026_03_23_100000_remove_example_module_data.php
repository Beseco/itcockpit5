<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove permissions assigned to roles/users first (foreign key safety)
        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['example.view', 'example.edit'])
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        // Remove the module entry
        DB::table('modules')->where('name', 'example')->delete();
    }

    public function down(): void
    {
        // Not restoring — module is intentionally removed
    }
};
