<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_settings', function (Blueprint $table) {
            // Alle Module exportieren statt nur Schulen
            if (!Schema::hasColumn('backup_settings', 'backup_exports_all')) {
                $table->boolean('backup_exports_all')->default(false)->after('backup_exports');
            }

            // SMB-Export
            if (!Schema::hasColumn('backup_settings', 'smb_enabled')) {
                $table->boolean('smb_enabled')->default(false)->after('backup_exports_all');
                $table->string('smb_server')->nullable()->after('smb_enabled');
                $table->string('smb_share')->nullable()->after('smb_server');
                $table->string('smb_domain')->nullable()->after('smb_share');
                $table->string('smb_username')->nullable()->after('smb_domain');
                $table->text('smb_password')->nullable()->after('smb_username');
                $table->string('smb_path')->nullable()->after('smb_password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('backup_settings', function (Blueprint $table) {
            $table->dropColumn([
                'backup_exports_all',
                'smb_enabled', 'smb_server', 'smb_share',
                'smb_domain', 'smb_username', 'smb_password', 'smb_path',
            ]);
        });
    }
};
