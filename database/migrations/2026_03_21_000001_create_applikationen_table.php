<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('applikationen')) {
            return;
        }

        Schema::create('applikationen', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sg')->nullable();                   // Sachgebiet / Abteilung
            $table->text('einsatzzweck')->nullable();
            $table->string('confidentiality', 1)->default('A'); // A/B/C
            $table->string('integrity', 1)->default('A');
            $table->string('availability', 1)->default('A');
            $table->string('baustein')->nullable();             // APP.1, SYS.1 etc.
            $table->string('verantwortlich_sg')->nullable();    // Verfahrensverantwortlicher
            $table->string('admin')->nullable();                // IT-Admin
            $table->string('ansprechpartner')->nullable();
            $table->string('hersteller')->nullable();           // Firmenname aus Dienstleister
            $table->date('revision_date')->nullable();
            $table->string('doc_url')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applikationen');
    }
};
