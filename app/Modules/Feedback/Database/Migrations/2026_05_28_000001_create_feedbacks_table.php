<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('q1_overall')->unsigned()->comment('Gesamtzufriedenheit (1–5)');
            $table->tinyInteger('q2_processing_time')->unsigned()->comment('Bearbeitungszeit (1–5)');
            $table->tinyInteger('q3_communication')->unsigned()->comment('Kommunikation (1–5)');
            $table->tinyInteger('q4_simplicity')->unsigned()->comment('Unkompliziertheit (1–5)');
            $table->tinyInteger('q5_competence')->unsigned()->comment('Fachliche Kompetenz (1–5)');
            $table->text('comment')->nullable();
            $table->string('ip_hash', 64)->nullable()->comment('SHA-256-Hash der IP-Adresse – kein Klartext');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
