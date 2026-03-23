<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('it_orders', function (Blueprint $table) {
            $table->foreignId('buyer_user_id')->nullable()->constrained('users')->nullOnDelete()->after('buyer_username');
        });
    }

    public function down(): void
    {
        Schema::table('it_orders', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'buyer_user_id');
            $table->dropColumn('buyer_user_id');
        });
    }
};
