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
        Schema::table('ip_addresses', function (Blueprint $table) {
            $table->index('dns_name');
            $table->index('mac_address');
        });

        Schema::table('vlans', function (Blueprint $table) {
            $table->index('vlan_name');
            $table->index('network_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ip_addresses', function (Blueprint $table) {
            $table->dropIndex(['dns_name']);
            $table->dropIndex(['mac_address']);
        });

        Schema::table('vlans', function (Blueprint $table) {
            $table->dropIndex(['vlan_name']);
            $table->dropIndex(['network_address']);
        });
    }
};
