<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('titel');
            $table->text('beschreibung')->nullable();
            $table->datetime('start_at');
            $table->datetime('end_at')->nullable();
            $table->boolean('ganztag')->default(false);
            $table->string('typ', 30)->default('termin'); // termin, wartung, sonstiges
            $table->string('farbe', 20)->nullable();      // z.B. #4f46e5
            $table->unsignedInteger('erinnerung_minuten')->nullable();
            $table->boolean('erinnerung_gesendet')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
