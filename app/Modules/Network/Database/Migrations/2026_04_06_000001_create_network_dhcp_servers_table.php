<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_dhcp_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('symbol', 20)->default('server'); // server, firewall, router, switch, cloud
            $table->string('color', 20)->default('blue');    // blue, red, green, yellow, purple
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::table('vlans', function (Blueprint $table) {
            $table->boolean('dhcp_enabled')->default(false)->after('dhcp_to');
            $table->foreignId('dhcp_server_id')->nullable()->constrained('network_dhcp_servers')->nullOnDelete()->after('dhcp_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('vlans', function (Blueprint $table) {
            $table->dropForeign(['dhcp_server_id']);
            $table->dropColumn(['dhcp_enabled', 'dhcp_server_id']);
        });
        Schema::dropIfExists('network_dhcp_servers');
    }
};
