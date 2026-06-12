<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vertraege', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('dienstleister_id')->nullable()
                  ->constrained('dienstleister')->nullOnDelete();
            $table->date('vertragsbeginn');
            $table->date('vertragsende')->nullable();           // null = unbefristet
            $table->unsignedSmallInteger('kuendigungsfrist_monate')->nullable();
            $table->unsignedSmallInteger('erinnerung_vorlauf_wochen')->default(4);
            $table->string('benachrichtigungs_email')->nullable();
            $table->string('status')->default('aktiv');         // aktiv | gekündigt | abgelaufen
            $table->text('notizen')->nullable();
            $table->dateTime('last_reminder_sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'vertragsende']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vertraege');
    }
};
