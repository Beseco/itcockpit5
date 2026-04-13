<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_sync_ous', function (Blueprint $table) {
            $table->id();
            $table->string('distinguished_name', 500);
            $table->string('label', 255)->nullable();
            $table->boolean('enabled')->default(true);
            $table->smallInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();
        });

        // Bisherige hartcodierte OU als ersten Eintrag übernehmen
        DB::table('server_sync_ous')->insert([
            'distinguished_name' => 'OU=Server,OU=LRA-FS,DC=lra,DC=lan',
            'label'              => null,
            'enabled'            => true,
            'sort_order'         => 0,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('server_sync_ous');
    }
};
