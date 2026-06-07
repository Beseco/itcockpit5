<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_records', function (Blueprint $table) {
            $table->string('phase', 20)->nullable()->after('mailbox_error');  // 'setup' | 'completed'
            $table->string('todo_token', 64)->nullable()->unique()->after('phase');
            $table->json('todos')->nullable()->after('todo_token');           // ['mailbox', 'h_laufwerk', ...]
            $table->string('mail_test_token', 64)->nullable()->unique()->after('todos');
            $table->timestamp('mail_verified_at')->nullable()->after('mail_test_token');
            $table->timestamp('completed_at')->nullable()->after('mail_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_records', function (Blueprint $table) {
            $table->dropColumn(['phase', 'todo_token', 'todos', 'mail_test_token', 'mail_verified_at', 'completed_at']);
        });
    }
};
