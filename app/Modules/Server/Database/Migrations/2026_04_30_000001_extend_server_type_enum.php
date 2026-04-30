<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE servers MODIFY COLUMN type ENUM('vm','bare_metal','firewall','usv','sonstiges') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE servers MODIFY COLUMN type ENUM('vm','bare_metal') NULL");
    }
};
