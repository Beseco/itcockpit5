<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_vorlage_gruppen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vorlage_id')->constrained('onboarding_vorlagen')->cascadeOnDelete();
            $table->string('ad_group_dn', 1000);
            $table->string('ad_group_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_vorlage_gruppen');
    }
};
