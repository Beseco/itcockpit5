<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_admin_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('email')->default('');
            $table->unsignedTinyInteger('interval_weeks')->default(2);
            $table->unsignedTinyInteger('weekday')->default(5);
            $table->unsignedTinyInteger('hour')->default(9);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_admin_notification_settings');
    }
};
