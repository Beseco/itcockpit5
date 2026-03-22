<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('it_orders')) {
            Schema::create('it_orders', function (Blueprint $table) {
                $table->id();
                $table->string('subject');
                $table->integer('quantity')->default(1);
                $table->decimal('price_gross', 10, 2)->default(0);
                $table->date('order_date');
                $table->unsignedBigInteger('vendor_id')->nullable();
                $table->unsignedBigInteger('cost_center_id')->nullable();
                $table->unsignedBigInteger('account_code_id')->nullable();
                $table->string('buyer_username')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->text('bemerkungen')->nullable();
                $table->dateTime('status_updated_at')->nullable();
                $table->timestamps();

                $table->index('status');
                $table->index('cost_center_id');
                $table->index('order_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('it_orders');
    }
};
