<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssl_certificates', function (Blueprint $table) {
            $table->string('source_url', 500)->nullable()->after('urls')
                ->comment('URL von der das Zertifikat ursprünglich importiert wurde');
            $table->timestamp('last_auto_check_at')->nullable()->after('source_url')
                ->comment('Zeitpunkt der letzten automatischen URL-Prüfung');
        });
    }

    public function down(): void
    {
        Schema::table('ssl_certificates', function (Blueprint $table) {
            $table->dropColumn(['source_url', 'last_auto_check_at']);
        });
    }
};
