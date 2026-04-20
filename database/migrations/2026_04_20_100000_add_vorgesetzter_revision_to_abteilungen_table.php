<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abteilungen', function (Blueprint $table) {
            $table->unsignedBigInteger('vorgesetzter_ad_user_id')->nullable()->after('sort_order');
            $table->unsignedBigInteger('stellvertreter_ad_user_id')->nullable()->after('vorgesetzter_ad_user_id');
            $table->date('revision_date')->nullable()->after('stellvertreter_ad_user_id');
            $table->string('revision_token', 64)->nullable()->unique()->after('revision_date');
            $table->timestamp('revision_notified_at')->nullable()->after('revision_token');
            $table->timestamp('revision_completed_at')->nullable()->after('revision_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('abteilungen', function (Blueprint $table) {
            $table->dropColumn([
                'vorgesetzter_ad_user_id',
                'stellvertreter_ad_user_id',
                'revision_date',
                'revision_token',
                'revision_notified_at',
                'revision_completed_at',
            ]);
        });
    }
};
