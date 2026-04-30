<?php

use App\Modules\Server\Models\ServerOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change type column from enum to varchar so it can hold any option value
        DB::statement("ALTER TABLE servers MODIFY COLUMN type VARCHAR(50) NULL");

        // Seed initial Gerätetypen into server_options
        $defaults = ['VM', 'Bare Metal', 'Firewall', 'USV', 'Sonstiges Gerät'];
        foreach ($defaults as $i => $label) {
            ServerOption::firstOrCreate(
                ['category' => 'geraet_typ', 'label' => $label],
                ['sort_order' => $i]
            );
        }

        // Migrate existing enum values to the new labels
        $map = [
            'vm'         => 'VM',
            'bare_metal' => 'Bare Metal',
            'firewall'   => 'Firewall',
            'usv'        => 'USV',
            'sonstiges'  => 'Sonstiges Gerät',
        ];
        foreach ($map as $old => $new) {
            DB::table('servers')->where('type', $old)->update(['type' => $new]);
        }
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE servers MODIFY COLUMN type ENUM('vm','bare_metal','firewall','usv','sonstiges') NULL");
    }
};
