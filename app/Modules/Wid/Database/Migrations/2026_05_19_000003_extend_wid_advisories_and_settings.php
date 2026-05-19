<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wid_advisories', function (Blueprint $table) {
            $table->longText('description')->nullable()->after('exploit');
            $table->boolean('detail_fetched')->default(false)->after('description');
        });

        Schema::table('wid_settings', function (Blueprint $table) {
            $table->boolean('abo_filter')->default(false)->after('min_classification');
        });
    }

    public function down(): void
    {
        Schema::table('wid_advisories', function (Blueprint $table) {
            $table->dropColumn(['description', 'detail_fetched']);
        });
        Schema::table('wid_settings', function (Blueprint $table) {
            $table->dropColumn('abo_filter');
        });
    }
};
