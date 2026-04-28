<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssl_certificate_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ssl_certificate_id')->constrained('ssl_certificates')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->nullOnDelete()->constrained('users');
            $table->string('user_name')->nullable();
            $table->string('action'); // erstellt | aktualisiert | erneuert | gelöscht
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index('ssl_certificate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssl_certificate_history');
    }
};
