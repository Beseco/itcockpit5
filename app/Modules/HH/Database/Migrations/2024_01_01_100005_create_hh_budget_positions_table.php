<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hh_budget_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_year_version_id')->constrained('hh_budget_year_versions')->cascadeOnDelete();
            $table->foreignId('cost_center_id')->constrained('hh_cost_centers');
            $table->foreignId('account_id')->constrained('hh_accounts');
            $table->string('project_name', 255);
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->smallInteger('start_year')->unsigned()->nullable();
            $table->smallInteger('end_year')->unsigned()->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->enum('priority', ['hoch', 'mittel', 'niedrig']);
            $table->enum('category', ['Pflichtaufgabe', 'gesetzlich gebunden', 'freiwillige Leistung']);
            $table->enum('status', ['geplant', 'angepasst', 'gestrichen'])->default('geplant');
            $table->foreignId('origin_position_id')->nullable()->constrained('hh_budget_positions')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hh_budget_positions');
    }
};
