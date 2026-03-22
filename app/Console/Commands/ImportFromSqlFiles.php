<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Network\SqlFileParser;
use App\Services\Network\DataValidator;
use App\Services\Network\ImportProcessor;
use App\Services\Network\VlanImporter;
use App\Services\Network\IpAddressImporter;
use App\Services\Network\ImportStatistics;
use App\Exceptions\CriticalImportException;
use App\Exceptions\ParseException;
use Exception;

class ImportFromSqlFiles extends Command
{
    protected $signature = 'network:import-sql 
                            {--vlans-file= : Path to VLANs SQL file}
                            {--ips-file= : Path to IPs SQL file}
                            {--dry-run : Simulate import without saving}
                            {--force : Skip confirmation prompts}
                            {--verify : Verify import after completion}';
    
    protected $description = 'Import VLAN and IP data from SQL dump files';

    private SqlFileParser $parser;
    private ImportProcessor $processor;
    private float $startTime;

    public function __construct()
    {
        parent::__construct();
        
        // Initialize services
        $this->parser = new SqlFileParser();
        $this->processor = new ImportProcessor(
            new VlanImporter(),
            new IpAddressImporter(),
            new DataValidator()
        );
    }

    public function handle(): int
    {
        $this->startTime = microtime(true);
        
        Log::info("=== Network Import Started ===", [
            'timestamp' => now()->toDateTimeString(),
            'user' => get_current_user(),
            'options' => [
                'dry_run' => $this->option('dry-run'),
                'force' => $this->option('force'),
                'verify' => $this->option('verify'),
            ]
        ]);
        
        // Get file paths
        $vlansFile = $this->option('vlans-file') ?: base_path('docs/olddb/vlan_liste.sql');
        $ipsFile = $this->option('ips-file') ?: base_path('docs/olddb/vlan_ip.sql');
        
        // Validate files exist (critical check)
        try {
            $this->validateFiles($vlansFile, $ipsFile);
        } catch (CriticalImportException $e) {
            $this->error("❌ " . $e->getMessage());
            Log::critical("Import aborted: File validation failed", [
                'error' => $e->getMessage(),
                'vlans_file' => $vlansFile,
                'ips_file' => $ipsFile
            ]);
            return 1;
        }
        
        // Show import information
        $this->displayHeader($vlansFile, $ipsFile);
        
        // Confirmation prompt (unless --force is used)
        if (!$this->option('force') && !$this->option('dry-run')) {
            if (!$this->confirm('Do you want to proceed with the import?')) {
                $this->info('Import cancelled by user.');
                Log::info("Import cancelled by user");
                return 0;
            }
        }
        
        try {
            // Parse SQL files (critical operation)
            $this->info('📄 Parsing SQL files...');
            Log::info("Starting SQL file parsing phase");
            
            $vlanRecords = $this->parseVlanFile($vlansFile);
            $ipRecords = $this->parseIpFile($ipsFile);
            
            $this->info("   Found {$this->formatNumber(count($vlanRecords))} VLANs and {$this->formatNumber(count($ipRecords))} IP addresses");
            $this->newLine();
            
            Log::info("SQL parsing completed", [
                'vlan_count' => count($vlanRecords),
                'ip_count' => count($ipRecords)
            ]);
            
            if ($this->option('dry-run')) {
                $this->warn('🔍 DRY RUN MODE - No data will be saved');
                $this->newLine();
                Log::info("Running in DRY RUN mode");
            }
            
            // Start database transaction (unless dry-run)
            if (!$this->option('dry-run')) {
                try {
                    DB::beginTransaction();
                    Log::info("Database transaction started");
                } catch (Exception $e) {
                    throw CriticalImportException::databaseConnectionFailed($e->getMessage());
                }
            }
            
            // Import VLANs (with error handling)
            $vlanStats = $this->importVlans($vlanRecords);
            
            // Import IP Addresses (with error handling)
            $ipStats = $this->importIps($ipRecords, $vlanRecords);
            
            // Commit transaction (unless dry-run)
            if (!$this->option('dry-run')) {
                try {
                    DB::commit();
                    $this->info('✅ Transaction committed successfully');
                    Log::info("Database transaction committed successfully");
                } catch (Exception $e) {
                    throw CriticalImportException::databaseConnectionFailed("Failed to commit transaction: " . $e->getMessage());
                }
            } else {
                $this->info('✅ Dry run completed (no changes made)');
                Log::info("Dry run completed successfully");
            }
            
            $this->newLine();
            
            // Display summary
            $this->displaySummary($vlanStats, $ipStats);
            
            // Verify import if requested
            if ($this->option('verify') && !$this->option('dry-run')) {
                $this->newLine();
                $this->verifyImport();
            }
            
            Log::info("=== Network Import Completed Successfully ===", [
                'duration' => microtime(true) - $this->startTime,
                'vlan_imported' => $vlanStats->successfullyImported,
                'ip_imported' => $ipStats->successfullyImported
            ]);
            
            return 0;
            
        } catch (CriticalImportException $e) {
            // Critical error: Rollback and abort
            if (!$this->option('dry-run')) {
                try {
                    DB::rollBack();
                    Log::warning("Database transaction rolled back due to critical error");
                } catch (Exception $rollbackException) {
                    Log::error("Failed to rollback transaction", [
                        'error' => $rollbackException->getMessage()
                    ]);
                }
            }
            
            $this->error('❌ CRITICAL ERROR: ' . $e->getMessage());
            $this->error('   Import has been aborted and rolled back.');
            
            Log::critical("Import failed with critical error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'vlans_file' => $vlansFile,
                'ips_file' => $ipsFile,
                'duration' => microtime(true) - $this->startTime
            ]);
            
            return 1;
            
        } catch (Exception $e) {
            // Unexpected error: Rollback and abort
            if (!$this->option('dry-run')) {
                try {
                    DB::rollBack();
                    Log::warning("Database transaction rolled back due to unexpected error");
                } catch (Exception $rollbackException) {
                    Log::error("Failed to rollback transaction", [
                        'error' => $rollbackException->getMessage()
                    ]);
                }
            }
            
            $this->error('❌ UNEXPECTED ERROR: ' . $e->getMessage());
            $this->error('   Import has been aborted and rolled back.');
            
            Log::error("Import failed with unexpected error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'vlans_file' => $vlansFile,
                'ips_file' => $ipsFile,
                'duration' => microtime(true) - $this->startTime
            ]);
            
            return 1;
        }
    }

    /**
     * Validate that required files exist
     * 
     * @throws CriticalImportException
     */
    private function validateFiles(string $vlansFile, string $ipsFile): void
    {
        if (!file_exists($vlansFile)) {
            throw CriticalImportException::fileNotFound($vlansFile);
        }
        
        if (!file_exists($ipsFile)) {
            throw CriticalImportException::fileNotFound($ipsFile);
        }
        
        // Check if files are readable
        if (!is_readable($vlansFile)) {
            throw CriticalImportException::insufficientPermissions("Cannot read VLANs file: {$vlansFile}");
        }
        
        if (!is_readable($ipsFile)) {
            throw CriticalImportException::insufficientPermissions("Cannot read IPs file: {$ipsFile}");
        }
    }

    /**
     * Display import header with file information
     */
    private function displayHeader(string $vlansFile, string $ipsFile): void
    {
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║         Network Data Import from SQL Files                     ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->info('📁 VLANs file: ' . basename($vlansFile));
        $this->info('📁 IPs file: ' . basename($ipsFile));
        
        if ($this->option('dry-run')) {
            $this->warn('🔍 Mode: DRY RUN (simulation only)');
        }
        
        $this->newLine();
    }

    /**
     * Parse VLAN SQL file
     * 
     * @throws CriticalImportException
     */
    private function parseVlanFile(string $filePath): array
    {
        try {
            Log::info("Parsing VLAN SQL file", ['file' => $filePath]);
            $records = $this->parser->parse($filePath);
            
            // Transform parsed records to associative arrays
            $transformed = array_map(function ($record) {
                return [
                    'id' => $record[0] ?? null,
                    'vlan_id' => $record[1] ?? null,
                    'vlan_name' => $record[2] ?? null,
                    'network_address' => $record[3] ?? null,
                    'cidr_suffix' => $record[4] ?? null,
                    'gateway' => $record[5] ?? null,
                    'dhcp_from' => $record[6] ?? null,
                    'dhcp_to' => $record[7] ?? null,
                    'description' => $record[8] ?? null,
                    'internes_netz' => $record[9] ?? null,
                    'ipscan' => $record[10] ?? null,
                ];
            }, $records);
            
            Log::info("VLAN SQL file parsed successfully", [
                'file' => $filePath,
                'record_count' => count($transformed)
            ]);
            
            return $transformed;
            
        } catch (CriticalImportException $e) {
            // Re-throw critical exceptions
            throw $e;
        } catch (Exception $e) {
            // Wrap other exceptions as critical
            Log::error("Failed to parse VLAN SQL file", [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw CriticalImportException::severeParsingError("Failed to parse VLANs file: " . $e->getMessage());
        }
    }

    /**
     * Parse IP SQL file
     * 
     * @throws CriticalImportException
     */
    private function parseIpFile(string $filePath): array
    {
        try {
            Log::info("Parsing IP SQL file", ['file' => $filePath]);
            $records = $this->parser->parse($filePath);
            
            // Transform parsed records to associative arrays
            $transformed = array_map(function ($record) {
                return [
                    'id' => $record[0] ?? null,
                    'vlan_liste_id' => $record[1] ?? null,
                    'dns_name' => $record[2] ?? null,
                    'ip_address' => $record[3] ?? null,
                    'mac_address' => $record[4] ?? null,
                    'is_online' => $record[5] ?? null,
                    'lastonline' => $record[6] ?? null,
                    'lasttest' => $record[7] ?? null,
                    'ping_response_time_ms' => $record[8] ?? null,
                    'kommentar' => $record[9] ?? null,
                ];
            }, $records);
            
            Log::info("IP SQL file parsed successfully", [
                'file' => $filePath,
                'record_count' => count($transformed)
            ]);
            
            return $transformed;
            
        } catch (CriticalImportException $e) {
            // Re-throw critical exceptions
            throw $e;
        } catch (Exception $e) {
            // Wrap other exceptions as critical
            Log::error("Failed to parse IP SQL file", [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw CriticalImportException::severeParsingError("Failed to parse IPs file: " . $e->getMessage());
        }
    }

    /**
     * Import VLANs with progress tracking
     */
    private function importVlans(array $vlanRecords): ImportStatistics
    {
        $this->info('🌐 Importing VLANs...');
        Log::info("Starting VLAN import phase", ['total_records' => count($vlanRecords)]);
        
        $progressBar = $this->output->createProgressBar(count($vlanRecords));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $progressBar->setMessage('Starting...');
        $progressBar->start();
        
        try {
            $stats = $this->processor->processVlans($vlanRecords, function ($current, $total) use ($progressBar) {
                $progressBar->setProgress($current);
                $progressBar->setMessage("Processing VLAN {$current}/{$total}");
            });
            
            $progressBar->setMessage('Completed');
            $progressBar->finish();
            $this->newLine(2);
            
            Log::info("VLAN import phase completed", [
                'imported' => $stats->successfullyImported,
                'duplicates' => $stats->skippedDuplicates,
                'errors' => $stats->validationErrors
            ]);
            
            return $stats;
            
        } catch (Exception $e) {
            $progressBar->setMessage('Failed');
            $progressBar->finish();
            $this->newLine(2);
            
            Log::error("VLAN import phase failed", [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Import IP addresses with progress tracking
     */
    private function importIps(array $ipRecords, array $vlanRecords): ImportStatistics
    {
        $this->info('🔢 Importing IP Addresses...');
        Log::info("Starting IP import phase", ['total_records' => count($ipRecords)]);
        
        $progressBar = $this->output->createProgressBar(count($ipRecords));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $progressBar->setMessage('Starting...');
        $progressBar->start();
        
        try {
            $stats = $this->processor->processIpAddresses($ipRecords, $vlanRecords, function ($current, $total) use ($progressBar) {
                $progressBar->setProgress($current);
                $progressBar->setMessage("Processing IP {$current}/{$total}");
            });
            
            $progressBar->setMessage('Completed');
            $progressBar->finish();
            $this->newLine(2);
            
            Log::info("IP import phase completed", [
                'imported' => $stats->successfullyImported,
                'duplicates' => $stats->skippedDuplicates,
                'errors' => $stats->validationErrors
            ]);
            
            return $stats;
            
        } catch (Exception $e) {
            $progressBar->setMessage('Failed');
            $progressBar->finish();
            $this->newLine(2);
            
            Log::error("IP import phase failed", [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Display import summary
     */
    private function displaySummary(ImportStatistics $vlanStats, ImportStatistics $ipStats): void
    {
        $duration = microtime(true) - $this->startTime;
        
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║                    Import Summary                              ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();
        
        // VLAN Statistics
        $this->info('📊 VLAN Statistics:');
        $this->line("   Total Processed:      {$this->formatNumber($vlanStats->totalProcessed)}");
        $this->line("   Successfully Imported: {$this->formatNumber($vlanStats->successfullyImported)} " . 
                    $this->getStatusIcon($vlanStats->successfullyImported > 0));
        $this->line("   Skipped Duplicates:   {$this->formatNumber($vlanStats->skippedDuplicates)}");
        $this->line("   Validation Errors:    {$this->formatNumber($vlanStats->validationErrors)} " . 
                    $this->getStatusIcon($vlanStats->validationErrors === 0));
        $this->newLine();
        
        // IP Statistics
        $this->info('📊 IP Address Statistics:');
        $this->line("   Total Processed:      {$this->formatNumber($ipStats->totalProcessed)}");
        $this->line("   Successfully Imported: {$this->formatNumber($ipStats->successfullyImported)} " . 
                    $this->getStatusIcon($ipStats->successfullyImported > 0));
        $this->line("   Skipped Duplicates:   {$this->formatNumber($ipStats->skippedDuplicates)}");
        $this->line("   Validation Errors:    {$this->formatNumber($ipStats->validationErrors)} " . 
                    $this->getStatusIcon($ipStats->validationErrors === 0));
        $this->newLine();
        
        // Duration
        $this->info("⏱️  Duration: {$this->formatDuration($duration)}");
        
        // Display errors if any
        if (!empty($vlanStats->errorMessages)) {
            $this->newLine();
            $this->warn('⚠️  VLAN Errors (showing first 5):');
            foreach (array_slice($vlanStats->errorMessages, 0, 5) as $error) {
                $this->line("   • {$error}");
            }
            if (count($vlanStats->errorMessages) > 5) {
                $this->line("   ... and " . (count($vlanStats->errorMessages) - 5) . " more");
            }
        }
        
        if (!empty($ipStats->errorMessages)) {
            $this->newLine();
            $this->warn('⚠️  IP Address Errors (showing first 5):');
            foreach (array_slice($ipStats->errorMessages, 0, 5) as $error) {
                $this->line("   • {$error}");
            }
            if (count($ipStats->errorMessages) > 5) {
                $this->line("   ... and " . (count($ipStats->errorMessages) - 5) . " more");
            }
        }
        
        // Log full statistics
        Log::info('Import completed', [
            'vlan_stats' => [
                'total' => $vlanStats->totalProcessed,
                'imported' => $vlanStats->successfullyImported,
                'duplicates' => $vlanStats->skippedDuplicates,
                'errors' => $vlanStats->validationErrors,
            ],
            'ip_stats' => [
                'total' => $ipStats->totalProcessed,
                'imported' => $ipStats->successfullyImported,
                'duplicates' => $ipStats->skippedDuplicates,
                'errors' => $ipStats->validationErrors,
            ],
            'duration' => $duration,
        ]);
    }

    /**
     * Verify import completeness
     */
    private function verifyImport(): void
    {
        $this->info('🔍 Verifying import...');
        
        // Check if verify command exists
        try {
            $this->call('network:verify-import');
        } catch (Exception $e) {
            $this->warn('Verify command not available yet. Run manually: php artisan network:verify-import');
        }
    }

    /**
     * Format a number with thousands separator
     */
    private function formatNumber(int $number): string
    {
        return number_format($number);
    }

    /**
     * Format duration in human-readable format
     */
    private function formatDuration(float $seconds): string
    {
        if ($seconds < 60) {
            return sprintf('%.2f seconds', $seconds);
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds - ($minutes * 60);
        
        return sprintf('%d minutes %.2f seconds', $minutes, $remainingSeconds);
    }

    /**
     * Get status icon based on condition
     */
    private function getStatusIcon(bool $isGood): string
    {
        return $isGood ? '✓' : '✗';
    }
}
