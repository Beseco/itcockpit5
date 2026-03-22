<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ip_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vlan_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 15);
            $table->string('dns_name')->nullable();
            $table->string('mac_address', 17)->nullable();
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_online_at')->nullable();
            $table->timestamp('last_scanned_at')->nullable();
            $table->float('ping_ms', 8, 2)->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('vlan_id');
            $table->index('ip_address');
            $table->index('is_online');
            $table->unique(['vlan_id', 'ip_address']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_addresses');
    }
};
