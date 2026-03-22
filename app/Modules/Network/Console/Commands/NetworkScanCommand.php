<?php

namespace App\Modules\Network\Console\Commands;

use App\Modules\Network\Models\Vlan;
use App\Modules\Network\Services\ScannerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NetworkScanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'network:scan
                            {--vlan= : Scan specific VLAN by ID}
                            {--force : Force scan even if interval has not elapsed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan VLANs to detect online devices and update IP address status';

    /**
     * The scanner service instance.
     *
     * @var \App\Modules\Network\Services\ScannerService
     */
    protected $scannerService;

    /**
     * Create a new command instance.
     *
     * @param \App\Modules\Network\Services\ScannerService $scannerService
     */
    public function __construct(ScannerService $scannerService)
    {
        parent::__construct();
        $this->scannerService = $scannerService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $startTime = now();
        
        // Check if specific VLAN was requested
        $vlanId = $this->option('vlan');
        $force = $this->option('force');
        
        if ($vlanId) {
            // Scan specific VLAN only
            return $this->scanSpecificVlan($vlanId, $force, $startTime);
        }
        
        // Scan all VLANs where ipscan is enabled
        return $this->scanAllVlans($force, $startTime);
    }

    /**
     * Scan a specific VLAN.
     *
     * @param int $vlanId
     * @param bool $force
     * @param \Illuminate\Support\Carbon $startTime
     * @return int
     */
    protected function scanSpecificVlan($vlanId, bool $force, $startTime): int
    {
        $vlan = Vlan::find($vlanId);
        
        if (!$vlan) {
            $this->error("VLAN with ID {$vlanId} not found.");
            Log::error('Network scan failed: VLAN not found', [
                'vlan_id' => $vlanId,
            ]);
            return 1;
        }
        
        $this->info("Scanning VLAN {$vlan->vlan_id} ({$vlan->vlan_name})...");
        
        // Check if scan should run (unless forced)
        if (!$force && !$this->scannerService->shouldScanVlan($vlan)) {
            $this->info("Skipping VLAN {$vlan->vlan_id}: scan interval not elapsed.");
            return 0;
        }
        
        // Perform the scan
        $result = $this->performVlanScan($vlan, $startTime);
        
        return $result ? 0 : 1;
    }

    /**
     * Scan all VLANs where ipscan is enabled.
     *
     * @param bool $force
     * @param \Illuminate\Support\Carbon $startTime
     * @return int
     */
    protected function scanAllVlans(bool $force, $startTime): int
    {
        // Load all VLANs where ipscan is true
        $vlans = Vlan::where('ipscan', true)->get();
        
        if ($vlans->isEmpty()) {
            $this->info('No VLANs configured for scanning.');
            return 0;
        }
        
        $this->info("Found {$vlans->count()} VLAN(s) configured for scanning.");
        
        $scannedCount = 0;
        $skippedCount = 0;
        
        foreach ($vlans as $vlan) {
            // Check if scan should run (unless forced)
            if (!$force && !$this->scannerService->shouldScanVlan($vlan)) {
                $this->line("Skipping VLAN {$vlan->vlan_id} ({$vlan->vlan_name}): scan interval not elapsed.");
                $skippedCount++;
                continue;
            }
            
            $this->info("Scanning VLAN {$vlan->vlan_id} ({$vlan->vlan_name})...");
            
            // Perform the scan
            if ($this->performVlanScan($vlan, $startTime)) {
                $scannedCount++;
            }
        }
        
        $this->info("Scan complete. Scanned: {$scannedCount}, Skipped: {$skippedCount}");
        
        return 0;
    }

    /**
     * Perform scan on a single VLAN and update its last_scanned_at timestamp.
     *
     * @param \App\Modules\Network\Models\Vlan $vlan
     * @param \Illuminate\Support\Carbon $startTime
     * @return bool
     */
    protected function performVlanScan(Vlan $vlan, $startTime): bool
    {
        try {
            $progressBar = null;

            // Call ScannerService to scan the VLAN
            $result = $this->scannerService->scanVlan($vlan, function (string $event, int $value) use (&$progressBar) {
                if ($event === 'start') {
                    $progressBar = $this->output->createProgressBar($value);
                    $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
                    $progressBar->start();
                } elseif ($event === 'advance' && $progressBar) {
                    $progressBar->setProgress($value);
                }
            });

            if ($progressBar) {
                $progressBar->finish();
                $this->newLine();
            }

            // Check if scan was skipped due to concurrent execution
            if (isset($result['skipped']) && $result['skipped']) {
                $this->warn("  Scan skipped: {$result['reason']}");
                return false;
            }
            
            $duration = $result['duration'] ?? 0;
            
            // Update VLAN's last_scanned_at timestamp
            $vlan->last_scanned_at = now();
            $vlan->save();
            
            // Output progress to console
            $this->info("  Scanned: {$result['scanned']}, Online: {$result['online']}, Offline: {$result['offline']} (Duration: {$duration}s)");
            
            // Log scan results
            Log::info('Network scan completed for VLAN', [
                'vlan_id' => $vlan->id,
                'vlan_name' => $vlan->vlan_name,
                'scanned' => $result['scanned'],
                'online' => $result['online'],
                'offline' => $result['offline'],
                'duration_seconds' => $duration,
            ]);

            // Audit log scan execution (only if there's an authenticated user)
            // Automated scans from scheduler won't have a user context
            if (auth()->check()) {
                $auditLogger = app(\App\Services\AuditLogger::class);
                $auditLogger->logModuleAction('Network', 'Network scan executed', [
                    'vlan_id' => $vlan->id,
                    'vlan_name' => $vlan->vlan_name,
                    'scanned' => $result['scanned'],
                    'online' => $result['online'],
                    'offline' => $result['offline'],
                    'duration_seconds' => $duration,
                ]);
            }
            
            return true;
            
        } catch (\Exception $e) {
            $this->error("  Error scanning VLAN {$vlan->vlan_id}: {$e->getMessage()}");
            
            Log::error('Network scan failed for VLAN', [
                'vlan_id' => $vlan->id,
                'vlan_name' => $vlan->vlan_name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return false;
        }
    }
}
