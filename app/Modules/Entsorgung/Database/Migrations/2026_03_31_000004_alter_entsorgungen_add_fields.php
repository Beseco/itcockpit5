<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entsorgungen', function (Blueprint $table) {
            $table->foreignId('dienstleister_id')
                ->nullable()
                ->after('hersteller')
                ->constrained('dienstleister')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->after('user')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('entsorgungsgrund')->nullable()->after('grundschutzgrund');
        });
    }

    public function down(): void
    {
        Schema::table('entsorgungen', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Dienstleister::class);
            $table->dropColumn('dienstleister_id');
            $table->dropForeignIdFor(\App\Models\User::class, 'user_id');
            $table->dropColumn('user_id');
            $table->dropColumn('entsorgungsgrund');
        });
    }
};
