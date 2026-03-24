<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gruppen', function (Blueprint $table) {
            $table->foreignId('vorgesetzter_user_id')
                  ->nullable()
                  ->after('sort_order')
                  ->constrained('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gruppen', function (Blueprint $table) {
            $table->dropForeign(['vorgesetzter_user_id']);
            $table->dropColumn('vorgesetzter_user_id');
        });
    }
};
