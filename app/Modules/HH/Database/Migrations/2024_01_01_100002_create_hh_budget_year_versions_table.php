<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hh_budget_year_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_year_id')->constrained('hh_budget_years')->cascadeOnDelete();
            $table->smallInteger('version_number')->unsigned();
            $table->boolean('is_active')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['budget_year_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hh_budget_year_versions');
    }
};
