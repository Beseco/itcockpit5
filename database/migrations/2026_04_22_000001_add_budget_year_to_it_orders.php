<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('it_orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('budget_year')->nullable()->after('account_code_id');
        });

        // Backfill: alle bestehenden Bestellungen bekommen HH-Jahr 2026
        DB::table('it_orders')->whereNull('budget_year')->update(['budget_year' => 2026]);
    }

    public function down(): void
    {
        Schema::table('it_orders', function (Blueprint $table) {
            $table->dropColumn('budget_year');
        });
    }
};
