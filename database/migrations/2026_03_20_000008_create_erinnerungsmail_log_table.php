<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('erinnerungsmail_log')) {
            return;
        }

        Schema::create('erinnerungsmail_log', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('typ')->default(1); // 1=Log, 2=Aktion, 3=Fehler
            $table->text('nachricht');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erinnerungsmail_log');
    }
};
