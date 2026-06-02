<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bara_settings', function (Blueprint $table) {
            $table->string('smb_domain')->nullable()->after('notification_email');
            $table->string('smb_username')->nullable()->after('smb_domain');
            $table->text('smb_password')->nullable()->after('smb_username');
        });
    }

    public function down(): void
    {
        Schema::table('bara_settings', function (Blueprint $table) {
            $table->dropColumn(['smb_domain', 'smb_username', 'smb_password']);
        });
    }
};
