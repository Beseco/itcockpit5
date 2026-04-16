<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssl_certificates', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->unsignedBigInteger('responsible_user_id')->nullable()->after('description');
            $table->string('doc_url')->nullable()->after('responsible_user_id');

            $table->foreign('responsible_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('ssl_certificate_server', function (Blueprint $table) {
            $table->unsignedBigInteger('ssl_certificate_id');
            $table->unsignedBigInteger('server_id');
            $table->primary(['ssl_certificate_id', 'server_id']);
            $table->foreign('ssl_certificate_id')->references('id')->on('ssl_certificates')->cascadeOnDelete();
            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssl_certificate_server');

        Schema::table('ssl_certificates', function (Blueprint $table) {
            $table->dropForeign(['responsible_user_id']);
            $table->dropColumn(['description', 'responsible_user_id', 'doc_url']);
        });
    }
};
