<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_settings', function (Blueprint $table) {
            $table->string('group_search_base_dn', 1000)->nullable()->after('ldap_write_bind_password');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_settings', function (Blueprint $table) {
            $table->dropColumn('group_search_base_dn');
        });
    }
};
