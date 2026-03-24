<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erinnerungsmail', function (Blueprint $table) {
            $table->json('mailto_new')->nullable()->after('mailto');
        });

        DB::statement("UPDATE erinnerungsmail SET mailto_new = JSON_ARRAY(mailto) WHERE mailto IS NOT NULL AND mailto != ''");

        Schema::table('erinnerungsmail', function (Blueprint $table) {
            $table->dropColumn('mailto');
        });

        Schema::table('erinnerungsmail', function (Blueprint $table) {
            $table->renameColumn('mailto_new', 'mailto');
        });
    }

    public function down(): void
    {
        Schema::table('erinnerungsmail', function (Blueprint $table) {
            $table->string('mailto_new', 255)->nullable()->after('mailto');
        });

        DB::statement("UPDATE erinnerungsmail SET mailto_new = JSON_UNQUOTE(JSON_EXTRACT(mailto, '$[0]'))");

        Schema::table('erinnerungsmail', function (Blueprint $table) {
            $table->dropColumn('mailto');
        });

        Schema::table('erinnerungsmail', function (Blueprint $table) {
            $table->renameColumn('mailto_new', 'mailto');
        });
    }
};
