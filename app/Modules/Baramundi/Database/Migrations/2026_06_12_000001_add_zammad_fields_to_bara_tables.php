<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Gespeicherte Zammad-Ticket-ID pro Paket (für Follow-up-Artikel)
        Schema::table('bara_watched_packages', function (Blueprint $table) {
            $table->unsignedInteger('zammad_ticket_id')->nullable()->after('status');
        });

        // Zammad-Gruppe für neu erstellte Baramundi-Tickets
        Schema::table('bara_settings', function (Blueprint $table) {
            $table->string('zammad_group')->nullable()->after('notification_email');
        });
    }

    public function down(): void
    {
        Schema::table('bara_watched_packages', function (Blueprint $table) {
            $table->dropColumn('zammad_ticket_id');
        });
        Schema::table('bara_settings', function (Blueprint $table) {
            $table->dropColumn('zammad_group');
        });
    }
};
