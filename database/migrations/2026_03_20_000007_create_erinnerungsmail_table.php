<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('erinnerungsmail')) {
            return;
        }

        Schema::create('erinnerungsmail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->tinyInteger('status')->default(1); // 1=aktiv, 0=inaktiv
            $table->string('titel');
            $table->text('nachricht');
            $table->dateTime('nextsend');
            $table->string('mailto');
            $table->integer('intervall_nummer')->default(1);
            $table->integer('intervall_faktor')->default(86400); // 60=Min, 3600=Std, 86400=Tage
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erinnerungsmail');
    }
};
