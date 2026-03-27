<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();

            // Stammdaten
            $table->string('name');
            $table->string('dns_hostname')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('operating_system')->nullable();
            $table->string('os_version')->nullable();
            $table->text('description')->nullable();
            $table->text('bemerkungen')->nullable();
            $table->string('doc_url')->nullable();

            // Feste Enums
            $table->enum('status', ['produktiv', 'testsystem', 'ausgeschaltet', 'im_aufbau', 'ausgemustert'])
                  ->default('produktiv');
            $table->enum('type', ['vm', 'bare_metal'])->nullable();

            // Erweiterbare Optionen
            $table->foreignId('os_type_id')->nullable()->constrained('server_options')->nullOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('server_options')->nullOnDelete();
            $table->foreignId('backup_level_id')->nullable()->constrained('server_options')->nullOnDelete();
            $table->foreignId('patch_ring_id')->nullable()->constrained('server_options')->nullOnDelete();

            // Organisatorische Zuordnungen
            $table->foreignId('abteilung_id')->nullable()->constrained('abteilungen')->nullOnDelete();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('gruppe_id')->nullable()->constrained('gruppen')->nullOnDelete();

            // LDAP-Felder
            $table->string('distinguished_name')->nullable()->unique();
            $table->string('managed_by_ldap')->nullable();
            $table->boolean('ldap_synced')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->json('raw_ldap_data')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('ldap_synced');
            $table->index('abteilung_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
