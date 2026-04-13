<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applikation_revision_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->tinyInteger('interval_weeks')->default(1)->comment('1, 2 oder 4 Wochen');
            $table->tinyInteger('weekday')->default(5)->comment('1=Mo, 2=Di, 3=Mi, 4=Do, 5=Fr');
            $table->tinyInteger('hour')->default(8)->comment('7–19 Uhr');
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applikation_revision_settings');
    }
};
