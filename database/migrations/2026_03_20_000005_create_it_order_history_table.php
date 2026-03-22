<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('it_order_history')) {
            Schema::create('it_order_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->string('changed_by');
                $table->string('field');
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->timestamps();

                $table->index('order_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('it_order_history');
    }
};
