<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orgchart_interfaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('orgchart_versions')->cascadeOnDelete();
            $table->foreignId('from_node_id')->constrained('orgchart_nodes')->cascadeOnDelete();
            $table->foreignId('to_node_id')->constrained('orgchart_nodes')->cascadeOnDelete();
            $table->string('label');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orgchart_interfaces');
    }
};
