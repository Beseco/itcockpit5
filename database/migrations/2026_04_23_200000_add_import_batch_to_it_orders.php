<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('it_orders', function (Blueprint $table) {
            $table->string('import_batch_id', 64)->nullable()->index()->after('hh_budget_position_id');
            $table->string('import_source', 64)->nullable()->after('import_batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('it_orders', function (Blueprint $table) {
            $table->dropColumn(['import_batch_id', 'import_source']);
        });
    }
};
