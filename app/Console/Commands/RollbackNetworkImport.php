<?php

namespace App\Console\Commands;

use App\Modules\Network\Models\IpAddress;
use App\Modules\Network\Models\Vlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class RollbackNetworkImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'network:rollback-import {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback network data import by deleting all VLANs and IP addresses';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Log::info("=== Network Import Rollback Started ===", [
            'timestamp' => now()->toDateTimeString(),
            'user' => get_current_user(),
            'force' => $this->option('force'),
        ]);

        // Display warning header
        $this->displayWarningHeader();

        // Get current counts before deletion
        $vlanCount = Vlan::count();
        $ipCount = IpAddress::count();

        if ($vlanCount === 0 && $ipCount === 0) {
            $this->info('ℹ️  No data to rollback. Database is already empty.');
            Log::info("Rollback skipped: No data found");
            return 0;
        }

        // Display what will be deleted
        $this->displayDeletionSummary($vlanCount, $ipCount);

        // Confirmation prompt (unless --force is used)
        if (!$this->option('force')) {
            $this->newLine();
            $this->warn('⚠️  This action cannot be undone!');
            $this->newLine();

            if (!$this->confirm('Are you absolutely sure you want to delete all network data?', false)) {
                $this->info('Rollback cancelled by user.');
                Log::info("Rollback cancelled by user");
                return 0;
            }

            // Double confirmation for safety
            $this->newLine();
            $confirmation = $this->ask('Type "DELETE ALL" to confirm (case-sensitive)');

            if ($confirmation !== 'DELETE ALL') {
                $this->error('❌ Confirmation text did not match. Rollback cancelled.');
                Log::info("Rollback cancelled: Confirmation text mismatch");
                return 0;
            }
        }

        $this->newLine();
        $this->info('🗑️  Starting rollback...');
        Log::info("Starting rollback operation", [
            'vlans_to_delete' => $vlanCount,
            'ips_to_delete' => $ipCount
        ]);

        try {
            DB::beginTransaction();

            // Delete IP addresses first (due to foreign key constraint)
            $this->info('   Deleting IP addresses...');
            $deletedIps = $this->truncateIpAddresses();
            $this->line("   <fg=green>✓</> Deleted {$this->formatNumber($deletedIps)} IP address(es)");

            // Delete VLANs
            $this->info('   Deleting VLANs...');
            $deletedVlans = $this->truncateVlans();
            $this->line("   <fg=green>✓</> Deleted {$this->formatNumber($deletedVlans)} VLAN(s)");

            DB::commit();

            $this->newLine();
            $this->displaySuccessSummary($deletedVlans, $deletedIps);

            Log::info("=== Network Import Rollback Completed Successfully ===", [
                'deleted_vlans' => $deletedVlans,
                'deleted_ips' => $deletedIps
            ]);

            return 0;

        } catch (Exception $e) {
            DB::rollBack();

            $this->newLine();
            $this->error('❌ ROLLBACK FAILED: ' . $e->getMessage());
            $this->error('   Database transaction has been rolled back.');
            $this->error('   Your data remains unchanged.');

            Log::error("Rollback operation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }

    /**
     * Display warning header with security notice
     */
    private function displayWarningHeader(): void
    {
        $this->newLine();
        $this->error('╔════════════════════════════════════════════════════════════════╗');
        $this->error('║                    ⚠️  DANGER ZONE ⚠️                          ║');
        $this->error('║              Network Import Rollback                           ║');
        $this->error('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->warn('⚠️  WARNING: This command will permanently delete ALL network data!');
        $this->warn('   • All VLANs will be removed');
        $this->warn('   • All IP addresses will be removed');
        $this->warn('   • This action CANNOT be undone');
        $this->newLine();
    }

    /**
     * Display summary of what will be deleted
     */
    private function displayDeletionSummary(int $vlanCount, int $ipCount): void
    {
        $this->info('📊 Current Database Status:');
        $this->line("   VLANs:        <fg=white;options=bold>{$this->formatNumber($vlanCount)}</>");
        $this->line("   IP Addresses: <fg=white;options=bold>{$this->formatNumber($ipCount)}</>");
    }

    /**
     * Truncate IP addresses table and return count of deleted records
     */
    private function truncateIpAddresses(): int
    {
        $count = IpAddress::count();

        if ($count > 0) {
            // Use delete() instead of truncate() to respect foreign key constraints
            // and trigger model events if needed
            IpAddress::query()->delete();

            Log::info("IP addresses deleted", ['count' => $count]);
        }

        return $count;
    }

    /**
     * Truncate VLANs table and return count of deleted records
     */
    private function truncateVlans(): int
    {
        $count = Vlan::count();

        if ($count > 0) {
            // Use delete() instead of truncate() to respect foreign key constraints
            // and trigger model events if needed
            Vlan::query()->delete();

            Log::info("VLANs deleted", ['count' => $count]);
        }

        return $count;
    }

    /**
     * Display success summary after rollback
     */
    private function displaySuccessSummary(int $deletedVlans, int $deletedIps): void
    {
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║                 Rollback Completed Successfully                ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->info('📊 Deletion Summary:');
        $this->line("   VLANs Deleted:        <fg=green;options=bold>{$this->formatNumber($deletedVlans)}</>");
        $this->line("   IP Addresses Deleted: <fg=green;options=bold>{$this->formatNumber($deletedIps)}</>");
        $this->newLine();
        $this->info('✅ All network data has been successfully removed from the database.');
        $this->info('💡 You can now re-import data using: php artisan network:import-sql');
    }

    /**
     * Format a number with thousands separator
     */
    private function formatNumber(int $number): string
    {
        return number_format($number);
    }
}
