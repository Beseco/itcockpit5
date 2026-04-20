<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abteilung_revision_proposals', function (Blueprint $table) {
            $table->id();
            $table->string('abteilung_revision_token', 64)->index();
            $table->unsignedBigInteger('applikation_id');
            $table->json('original_data');
            $table->json('proposed_data')->nullable();
            $table->text('reason')->nullable();
            $table->string('approval_token', 64)->unique()->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('skipped')->default(false);
            $table->timestamps();
        });

        Schema::create('abteilung_revision_settings', function (Blueprint $table) {
            $table->id();
            $table->string('new_app_email')->default('informatiotechnik@kreis-fs.de');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abteilung_revision_proposals');
        Schema::dropIfExists('abteilung_revision_settings');
    }
};
