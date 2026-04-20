<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abteilung_revision_proposals', function (Blueprint $table) {
            $table->text('kommentar')->nullable()->after('reason');
            $table->boolean('nicht_vorhanden')->default(false)->after('kommentar');
        });
    }

    public function down(): void
    {
        Schema::table('abteilung_revision_proposals', function (Blueprint $table) {
            $table->dropColumn(['kommentar', 'nicht_vorhanden']);
        });
    }
};
