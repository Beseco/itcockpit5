<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hh_budget_years', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('year')->unsigned()->unique();
            $table->enum('status', ['draft', 'preliminary', 'approved'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hh_budget_years');
    }
};
