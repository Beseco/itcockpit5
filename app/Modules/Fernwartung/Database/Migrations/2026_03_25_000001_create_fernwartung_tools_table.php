<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fernwartung_tools', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Standard-Tools
        $tools = ['Teamviewer', 'Fastviewer', 'Microsoft Teams', 'AnyDesk', 'RDP (Windows)'];
        foreach ($tools as $i => $name) {
            DB::table('fernwartung_tools')->insert([
                'name'       => $name,
                'sort_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fernwartung_tools');
    }
};
