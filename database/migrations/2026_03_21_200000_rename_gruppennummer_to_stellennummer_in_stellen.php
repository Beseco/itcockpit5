<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stellen', function (Blueprint $table) {
            if (Schema::hasColumn('stellen', 'gruppennummer')) {
                $table->renameColumn('gruppennummer', 'stellennummer');
            } elseif (!Schema::hasColumn('stellen', 'stellennummer')) {
                $table->string('stellennummer')->nullable()->after('bezeichnung');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stellen', function (Blueprint $table) {
            if (Schema::hasColumn('stellen', 'stellennummer')) {
                $table->renameColumn('stellennummer', 'gruppennummer');
            }
        });
    }
};
