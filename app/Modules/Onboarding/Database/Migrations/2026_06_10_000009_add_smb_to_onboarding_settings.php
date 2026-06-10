<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_settings', function (Blueprint $table) {
            $table->string('smb_user')->nullable()->after('exchange_mailbox_db');
            $table->text('smb_password')->nullable()->after('smb_user');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_settings', function (Blueprint $table) {
            $table->dropColumn(['smb_user', 'smb_password']);
        });
    }
};
