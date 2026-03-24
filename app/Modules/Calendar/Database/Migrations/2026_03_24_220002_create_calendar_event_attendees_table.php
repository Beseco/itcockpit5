<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_event_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('calendar_events')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable();
            $table->timestamp('eingeladen_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_attendees');
    }
};
