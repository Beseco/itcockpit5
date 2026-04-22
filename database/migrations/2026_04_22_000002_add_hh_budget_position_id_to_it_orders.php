<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('it_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('hh_budget_position_id')->nullable()->after('budget_year');
        });
    }

    public function down(): void
    {
        Schema::table('it_orders', function (Blueprint $table) {
            $table->dropColumn('hh_budget_position_id');
        });
    }
};
