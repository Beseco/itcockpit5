<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hh_user_cost_center_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cost_center_id')->constrained('hh_cost_centers')->cascadeOnDelete();
            $table->enum('role', ['Leiter', 'Teamleiter', 'Mitarbeiter', 'Audit_Zugang']);
            $table->timestamps();

            $table->unique(['user_id', 'cost_center_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hh_user_cost_center_roles');
    }
};
