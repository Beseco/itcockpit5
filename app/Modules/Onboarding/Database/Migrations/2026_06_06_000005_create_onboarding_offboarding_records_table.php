<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_offboarding_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onboarding_record_id')->nullable()->constrained('onboarding_records')->nullOnDelete();
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['ausstehend', 'abgeschlossen'])->default('ausstehend');
            $table->timestamp('ad_disabled_at')->nullable();
            $table->timestamp('groups_removed_at')->nullable();
            $table->timestamp('mailbox_disabled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_offboarding_records');
    }
};
