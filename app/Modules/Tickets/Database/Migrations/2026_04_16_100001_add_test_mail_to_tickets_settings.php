<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets_settings', function (Blueprint $table) {
            $table->string('test_email')->nullable()->after('score_red_min');
            $table->unsignedBigInteger('test_user_id')->nullable()->after('test_email');
        });
    }

    public function down(): void
    {
        Schema::table('tickets_settings', function (Blueprint $table) {
            $table->dropColumn(['test_email', 'test_user_id']);
        });
    }
};
