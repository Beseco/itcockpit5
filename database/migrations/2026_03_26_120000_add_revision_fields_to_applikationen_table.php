<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applikationen', function (Blueprint $table) {
            $table->string('revision_token', 64)->nullable()->unique()->after('revision_date');
            $table->timestamp('revision_notified_at')->nullable()->after('revision_token');
            $table->timestamp('revision_completed_at')->nullable()->after('revision_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('applikationen', function (Blueprint $table) {
            $table->dropColumn(['revision_token', 'revision_notified_at', 'revision_completed_at']);
        });
    }
};
