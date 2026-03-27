<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_applikation', function (Blueprint $table) {
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->foreignId('applikation_id')->constrained('applikationen')->cascadeOnDelete();
            $table->primary(['server_id', 'applikation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_applikation');
    }
};
