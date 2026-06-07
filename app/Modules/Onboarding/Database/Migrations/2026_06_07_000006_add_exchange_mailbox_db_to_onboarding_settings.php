<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_settings', function (Blueprint $table) {
            $table->string('exchange_mailbox_db', 255)->nullable()->after('exchange_auth');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_settings', function (Blueprint $table) {
            $table->dropColumn('exchange_mailbox_db');
        });
    }
};
