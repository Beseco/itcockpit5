<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_user_group_change_logs', function (Blueprint $table) {
            $table->id();
            $table->string('samaccountname', 100)->index();
            $table->string('user_dn', 1000);
            $table->string('group_dn', 1000);
            $table->string('group_name', 255);
            $table->enum('action', ['add', 'remove']);
            $table->foreignId('performed_by_user_id')->constrained('users');
            $table->timestamp('reverted_at')->nullable();
            $table->foreignId('reverted_by_user_id')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_user_group_change_logs');
    }
};
