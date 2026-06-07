<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_records', function (Blueprint $table) {
            $table->string('mailbox_status', 20)->nullable()->after('supervisor_mail_sent_at');
            $table->timestamp('mailbox_enabled_at')->nullable()->after('mailbox_status');
            $table->text('mailbox_error')->nullable()->after('mailbox_enabled_at');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_records', function (Blueprint $table) {
            $table->dropColumn(['mailbox_status', 'mailbox_enabled_at', 'mailbox_error']);
        });
    }
};
