<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applikationen', function (Blueprint $table) {
            $table->foreignId('verantwortlich_ad_user_id')
                  ->nullable()
                  ->after('verantwortlich_sg')
                  ->constrained('adusers')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('applikationen', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Modules\AdUsers\Models\AdUser::class, 'verantwortlich_ad_user_id');
            $table->dropColumn('verantwortlich_ad_user_id');
        });
    }
};
