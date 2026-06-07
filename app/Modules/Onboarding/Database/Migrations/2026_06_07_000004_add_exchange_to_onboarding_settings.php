<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_settings', function (Blueprint $table) {
            $table->string('exchange_url', 500)->nullable()->after('group_search_base_dn');
            $table->string('exchange_user', 255)->nullable()->after('exchange_url');
            $table->text('exchange_password')->nullable()->after('exchange_user');
            $table->string('exchange_auth', 50)->nullable()->default('Negotiate')->after('exchange_password');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_settings', function (Blueprint $table) {
            $table->dropColumn(['exchange_url', 'exchange_user', 'exchange_password', 'exchange_auth']);
        });
    }
};
