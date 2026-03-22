<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('gruppen')) {
            Schema::create('gruppen', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->foreign('parent_id')->references('id')->on('gruppen')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gruppen');
    }
};
