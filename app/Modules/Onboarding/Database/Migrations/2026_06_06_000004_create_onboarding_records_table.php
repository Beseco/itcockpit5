<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vorlage_id')->nullable()->constrained('onboarding_vorlagen')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('vorname');
            $table->string('nachname');
            $table->string('samaccountname');
            $table->string('upn');
            $table->string('distinguished_name', 1000)->nullable();
            $table->string('rufnummer')->nullable();
            $table->json('ad_attributes_snapshot')->nullable();
            $table->enum('status', ['ausstehend', 'erfolgreich', 'fehler'])->default('ausstehend');
            $table->text('error_message')->nullable();
            $table->timestamp('welcome_mail_sent_at')->nullable();
            $table->timestamp('supervisor_mail_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_records');
    }
};
