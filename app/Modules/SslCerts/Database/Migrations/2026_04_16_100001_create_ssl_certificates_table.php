<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssl_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject_cn')->nullable();
            $table->string('subject_o')->nullable();
            $table->string('subject_ou')->nullable();
            $table->string('issuer_cn')->nullable();
            $table->string('issuer_o')->nullable();
            $table->string('serial_number')->nullable();
            $table->datetime('valid_from')->nullable();
            $table->datetime('valid_to')->nullable();
            $table->text('san')->nullable();               // JSON-Array
            $table->string('fingerprint_sha1')->nullable();
            $table->string('fingerprint_sha256')->nullable();
            $table->text('cert_pem');                      // öffentlicher Teil
            $table->text('private_key')->nullable();       // verschlüsselt
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssl_certificates');
    }
};
