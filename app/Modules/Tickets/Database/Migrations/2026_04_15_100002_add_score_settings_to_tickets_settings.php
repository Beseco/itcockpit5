<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets_settings', function (Blueprint $table) {
            $table->boolean('email_enabled')->default(false)->after('enabled');
            $table->decimal('email_threshold', 5, 1)->default(3.0)->after('email_enabled');
            $table->decimal('score_green_max', 5, 1)->default(3.0)->after('email_threshold');
            $table->decimal('score_red_min', 5, 1)->default(6.0)->after('score_green_max');
        });
    }

    public function down(): void
    {
        Schema::table('tickets_settings', function (Blueprint $table) {
            $table->dropColumn(['email_enabled', 'email_threshold', 'score_green_max', 'score_red_min']);
        });
    }
};
