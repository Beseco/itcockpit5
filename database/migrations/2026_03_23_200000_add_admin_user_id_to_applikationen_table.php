<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applikationen', function (Blueprint $table) {
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete()->after('verantwortlich_sg');
        });
    }

    public function down(): void
    {
        Schema::table('applikationen', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'admin_user_id');
            $table->dropColumn('admin_user_id');
        });
    }
};
