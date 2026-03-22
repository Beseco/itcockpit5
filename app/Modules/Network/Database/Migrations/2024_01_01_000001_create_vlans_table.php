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
        Schema::create('vlans', function (Blueprint $table) {
            $table->id();
            $table->integer('vlan_id');
            $table->string('vlan_name');
            $table->string('network_address', 45);
            $table->tinyInteger('cidr_suffix');
            $table->string('gateway', 45)->nullable();
            $table->string('dhcp_from', 45)->nullable();
            $table->string('dhcp_to', 45)->nullable();
            $table->text('description')->nullable();
            $table->boolean('internes_netz')->default(false);
            $table->boolean('ipscan')->default(false);
            $table->integer('scan_interval_minutes')->default(60);
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamps();

            // Composite unique index: allows multiple VLANs with same vlan_id (e.g., 999)
            // but different network addresses
            $table->unique(['vlan_id', 'network_address'], 'unique_vlan_network');
            $table->index('vlan_id');
            $table->index('ipscan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vlans');
    }
};
