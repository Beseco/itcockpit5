<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkmk_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('url')->default('');
            $table->string('site')->default('');
            $table->string('username')->default('automation');
            $table->string('secret')->default('');
            $table->boolean('verify_ssl')->default(true);
            $table->timestamps();
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->string('checkmk_alias')->nullable()->after('dns_hostname')
                ->comment('Optionaler CheckMK Hostname (überschreibt dns_hostname)');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkmk_settings');
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('checkmk_alias');
        });
    }
};
