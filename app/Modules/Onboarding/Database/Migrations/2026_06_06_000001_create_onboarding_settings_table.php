<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_settings', function (Blueprint $table) {
            $table->id();
            $table->string('ldap_write_bind_dn')->nullable();
            $table->text('ldap_write_bind_password')->nullable();
            $table->string('welcome_mail_subject')->default('Willkommen – Ihr neues Benutzerkonto');
            $table->text('welcome_mail_body')->nullable();
            $table->string('supervisor_mail_subject')->default('Neues Benutzerkonto wurde angelegt');
            $table->text('supervisor_mail_body')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_settings');
    }
};
