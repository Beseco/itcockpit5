<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vsphere_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('vcenter_url')->default('');
            $table->string('username')->default('');
            $table->string('password')->default('');
            $table->boolean('verify_ssl')->default(true);
            $table->timestamps();
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->string('vsphere_vm_id')->nullable()->unique()->after('raw_ldap_data');
            $table->boolean('vsphere_synced')->default(false)->after('vsphere_vm_id');
            $table->timestamp('vsphere_synced_at')->nullable()->after('vsphere_synced');
            $table->unsignedSmallInteger('cpu_count')->nullable()->after('vsphere_synced_at');
            $table->unsignedInteger('memory_mb')->nullable()->after('cpu_count');
            $table->unsignedInteger('disk_gb')->nullable()->after('memory_mb');
            $table->string('vsphere_datastore')->nullable()->after('disk_gb');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vsphere_settings');
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn([
                'vsphere_vm_id', 'vsphere_synced', 'vsphere_synced_at',
                'cpu_count', 'memory_mb', 'disk_gb', 'vsphere_datastore',
            ]);
        });
    }
};
