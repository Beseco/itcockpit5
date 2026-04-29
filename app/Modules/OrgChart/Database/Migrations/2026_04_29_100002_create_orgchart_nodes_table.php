<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orgchart_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('orgchart_versions')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('orgchart_nodes')->cascadeOnDelete();
            $table->enum('type', ['top', 'staff', 'frame', 'group', 'task'])->default('group');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->nullable();
            $table->decimal('headcount', 4, 2)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orgchart_nodes');
    }
};
