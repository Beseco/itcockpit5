<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bara_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('scan_interval_minutes')->default(15);
            $table->boolean('email_on_smb_error')->default(false);
            $table->string('notification_email')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bara_settings');
    }
};
