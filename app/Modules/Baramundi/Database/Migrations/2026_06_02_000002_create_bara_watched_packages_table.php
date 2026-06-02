<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bara_watched_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('server_name');
            $table->string('share_path');
            $table->boolean('enabled')->default(true);
            $table->boolean('email_enabled')->default(true);
            $table->string('download_type')->default('none'); // none|http|powershell|batch
            $table->text('download_command')->nullable();
            $table->string('download_url', 1000)->nullable();
            $table->text('notes')->nullable();
            $table->string('last_known_version')->nullable();
            $table->timestamp('last_scan')->nullable();
            $table->timestamp('last_detected')->nullable();
            $table->string('status')->default('ok'); // ok|new_version|download_running|download_ok|download_failed|smb_unreachable
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bara_watched_packages');
    }
};
