<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offboarding_records', function (Blueprint $table) {
            $table->id();

            // Verknüpfung mit AD-Benutzer
            $table->foreignId('aduser_id')->nullable()->constrained('adusers')->nullOnDelete();
            $table->string('samaccountname');
            $table->string('vorname');
            $table->string('nachname');
            $table->string('personalnummer')->nullable();
            $table->string('abteilung')->nullable();
            $table->string('email_bestaetigung')->nullable();

            // Daten
            $table->date('datum_ausscheiden');
            $table->date('datum_geloescht')->nullable();
            $table->string('geloescht_von')->nullable();

            // Anleger
            $table->foreignId('anleger_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('anleger_name');

            // Status & Bestätigung
            $table->enum('status', ['ausstehend', 'bestaetigung_angefragt', 'bestaetigt', 'abgeschlossen'])
                  ->default('ausstehend');
            $table->string('bestaetigungstoken', 64)->unique()->nullable();
            $table->timestamp('bestaetigung_angefragt_at')->nullable();
            $table->timestamp('bestaetigung_erhalten_at')->nullable();
            $table->string('bestaetigung_name')->nullable();
            $table->string('bestaetigung_ip', 45)->nullable();

            // PDFs (binär in DB)
            $table->binary('personalmeldung_pdf')->nullable();
            $table->string('personalmeldung_pdf_name')->nullable();
            $table->binary('bestaetigung_pdf')->nullable();
            $table->string('bestaetigung_pdf_name')->nullable();

            $table->text('bemerkungen')->nullable();

            // Legacy-Import
            $table->integer('legacy_id')->nullable();
            $table->timestamp('imported_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('samaccountname');
            $table->index('datum_ausscheiden');
        });

        // longblob statt standard binary für große PDFs
        \DB::statement('ALTER TABLE offboarding_records MODIFY personalmeldung_pdf LONGBLOB');
        \DB::statement('ALTER TABLE offboarding_records MODIFY bestaetigung_pdf LONGBLOB');
    }

    public function down(): void
    {
        Schema::dropIfExists('offboarding_records');
    }
};
