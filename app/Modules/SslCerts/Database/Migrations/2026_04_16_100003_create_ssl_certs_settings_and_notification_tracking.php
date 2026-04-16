<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Einstellungen-Tabelle (Singleton)
        Schema::create('ssl_certs_settings', function (Blueprint $table) {
            $table->id();
            $table->string('notification_email')->nullable();
            $table->boolean('notifications_enabled')->default(false);
            $table->timestamps();
        });

        // Versand-Tracking pro Zertifikat
        Schema::table('ssl_certificates', function (Blueprint $table) {
            $table->timestamp('notified_30_at')->nullable()->after('private_key');
            $table->timestamp('notified_14_at')->nullable()->after('notified_30_at');
            $table->timestamp('notified_daily_at')->nullable()->after('notified_14_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssl_certs_settings');

        Schema::table('ssl_certificates', function (Blueprint $table) {
            $table->dropColumn(['notified_30_at', 'notified_14_at', 'notified_daily_at']);
        });
    }
};
