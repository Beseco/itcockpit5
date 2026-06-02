<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bara_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('bara_watched_packages')->cascadeOnDelete();
            $table->string('version')->nullable();
            $table->string('event_type'); // version_detected|smb_unreachable|download_started|download_ok|download_failed|config_changed
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bara_events');
    }
};
