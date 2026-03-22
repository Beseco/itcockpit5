<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('it_cost_centers')) {
            Schema::create('it_cost_centers', function (Blueprint $table) {
                $table->id();
                $table->string('number');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('it_cost_centers');
    }
};
