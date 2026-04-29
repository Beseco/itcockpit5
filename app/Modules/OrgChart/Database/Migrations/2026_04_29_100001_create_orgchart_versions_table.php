<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orgchart_versions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['entwurf', 'abstimmung', 'aktiv', 'archiviert'])->default('entwurf');
            $table->string('color_scheme', 50)->default('klassisch');
            $table->text('notes')->nullable();
            $table->string('created_by', 255)->default('');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orgchart_versions');
    }
};
