<?php

namespace Database\Seeders;

use App\Models\Announcement;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample critical announcement
        Announcement::create([
            'type' => 'critical',
            'message' => 'Critical system outage detected in the authentication service. Our team is actively working to resolve this issue. Please contact support if you experience login problems.',
            'starts_at' => now()->subHours(2),
            'ends_at' => null,
            'is_fixed' => false,
            'fixed_at' => null,
        ]);

        // Create sample maintenance announcement
        Announcement::create([
            'type' => 'maintenance',
            'message' => 'Scheduled maintenance window for database upgrades. The system will be in read-only mode during this period. All write operations will be temporarily disabled.',
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHours(4),
            'is_fixed' => false,
            'fixed_at' => null,
        ]);

        // Create sample info announcement
        Announcement::create([
            'type' => 'info',
            'message' => 'New feature release: Module management dashboard is now available! Check out the enhanced user interface and improved performance monitoring tools.',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(7),
            'is_fixed' => false,
            'fixed_at' => null,
        ]);
    }
}
