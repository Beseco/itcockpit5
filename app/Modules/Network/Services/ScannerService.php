<?php

namespace App\Modules\Network\Services;

use App\Modules\Network\Models\Vlan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;

class ScannerService
{
    /**
     * Ping an IP address to check availability and measure response time.
     *
     * Executes a platform-specific ping command with 1 second timeout and 1 packet.
     * Parses the output to determine if the host is online and extract response time.
     *
     * @param string $ipAddress The IP address to ping
     * @return array ['is_online' => bool, 'ping_ms' => float|null]
     */
    public function pingIpAddress(string $ipAddress): array
    {
        $isWindows = $this->isWindows();
        
        // Build platform-specific ping command
        if ($isWindows) {
            // Windows: ping -n 1 -w 60 {ip}
            // -n 1: send 1 packet
            // -w 60: timeout in milliseconds (60ms)
            $command = sprintf('ping -n 1 -w 60 %s', escapeshellarg($ipAddress));
        } else {
            // Linux: ping -c 1 -W 1 {ip}
            // -c 1: send 1 packet
            // -W 1: timeout in seconds (minimum is 1 second on Linux)
            // Note: Linux ping doesn't support sub-second timeouts via -W
            $command = sprintf('ping -c 1 -W 1 %s', escapeshellarg($ipAddress));
        }
        
        // Execute ping command
        $output = shell_exec($command);
        
        if ($output === null) {
            Log::error('Ping command execution failed', [
                'ip_address' => $ipAddress,
                'command' => $command,
            ]);
            
            return [
                'is_online' => false,
                'ping_ms' => null,
            ];
        }
        
        // Parse ping output
        return $this->parsePingOutput($output, $isWindows);
    }
    
    /**
     * Determine if the current OS is Windows.
     *
     * @return bool
     */
    protected function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
    
    /**
     * Parse ping command output to extract success/failure and response time.
     *
     * @param string $output The raw ping command output
     * @param bool $isWindows Whether the output is from Windows
     * @return array ['is_online' => bool, 'ping_ms' => float|null]
     */
    protected function parsePingOutput(string $output, bool $isWindows): array
    {
        if ($isWindows) {
            return $this->parseWindowsPingOutput($output);
        } else {
            return $this->parseLinuxPingOutput($output);
        }
    }
    
    /**
     * Parse Windows ping output.
     *
     * Windows ping output format:
     * Reply from 192.168.1.1: bytes=32 time=1ms TTL=64
     * or
     * Request timed out.
     *
     * @param string $output The raw ping output
     * @return array ['is_online' => bool, 'ping_ms' => float|null]
     */
    protected function parseWindowsPingOutput(string $output): array
    {
        // Check for time in format "time<1ms" or "Zeit<1ms" first (sub-millisecond)
        // English: "Reply from ... time<1ms"
        // German: "Antwort von ... Zeit<1ms"
        if (preg_match('/(Reply from|Antwort von).*(time|Zeit)<(\d+)ms/i', $output, $matches)) {
            // If time is less than Xms, use 1ms as minimum realistic value
            return [
                'is_online' => true,
                'ping_ms' => 1.0,
            ];
        }
        
        // Check for successful reply with exact time
        // English: "Reply from ... time=1ms"
        // German: "Antwort von ... Zeit=1ms"
        if (preg_match('/(Reply from|Antwort von).*(time|Zeit)[=](\d+)ms/i', $output, $matches)) {
            $pingMs = (float) $matches[3];
            
            return [
                'is_online' => true,
                'ping_ms' => $pingMs,
            ];
        }
        
        // No successful reply found
        return [
            'is_online' => false,
            'ping_ms' => null,
        ];
    }
    
    /**
     * Parse Linux ping output.
     *
     * Linux ping output format:
     * 64 bytes from 192.168.1.1: icmp_seq=1 ttl=64 time=0.123 ms
     * or
     * From 192.168.1.1 icmp_seq=1 Destination Host Unreachable
     *
     * @param string $output The raw ping output
     * @return array ['is_online' => bool, 'ping_ms' => float|null]
     */
    protected function parseLinuxPingOutput(string $output): array
    {
        // Check for successful reply with time
        if (preg_match('/time=([0-9.]+)\s*ms/i', $output, $matches)) {
            $pingMs = (float) $matches[1];
            
            return [
                'is_online' => true,
                'ping_ms' => $pingMs,
            ];
        }
        
        // No successful reply found
        return [
            'is_online' => false,
            'ping_ms' => null,
        ];
    }
    
    /**
     * Resolve MAC address for an IP address using ARP cache.
     *
     * Note: MAC address resolution only works for devices in the same Layer-2 network
     * (same VLAN) as the server running this scan. For devices in other VLANs,
     * this will return null, which is expected behavior.
     *
     * Executes a platform-specific ARP command to query the system ARP cache.
     * Parses the output to extract and normalize the MAC address.
     *
     * @param string $ipAddress The IP address to resolve
     * @return string|null The MAC address in uppercase with colons, or null if not found
     */
    public function resolveMacAddress(string $ipAddress): ?string
    {
        $isWindows = $this->isWindows();
        
        // Build platform-specific ARP command
        if ($isWindows) {
            // Windows: arp -a {ip}
            $command = sprintf('arp -a %s 2>&1', escapeshellarg($ipAddress));
        } else {
            // Linux: Try ip neigh first (modern), fallback to arp (legacy)
            // ip neigh is part of iproute2 which is standard on modern Linux
            $command = sprintf('ip neigh show %s 2>/dev/null || arp -n %s 2>/dev/null', 
                escapeshellarg($ipAddress), 
                escapeshellarg($ipAddress)
            );
        }
        
        // Execute ARP command
        $output = @shell_exec($command);
        
        if ($output === null || trim($output) === '') {
            // Silently return null if command fails or no output
            return null;
        }
        
        // Parse ARP output to extract MAC address
        $macAddress = $this->parseArpOutput($output);
        
        if ($macAddress === null) {
            return null;
        }
        
        // Normalize MAC address format (uppercase with colons)
        return $this->normalizeMacAddress($macAddress);
    }
    
    /**
     * Parse ARP command output to extract MAC address.
     *
     * Matches MAC address pattern: ([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})
     * This matches both colon-separated (Linux) and hyphen-separated (Windows) formats.
     *
     * @param string $output The raw ARP command output
     * @return string|null The MAC address if found, null otherwise
     */
    protected function parseArpOutput(string $output): ?string
    {
        // Match MAC address pattern (supports both : and - separators)
        if (preg_match('/([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})/', $output, $matches)) {
            return $matches[0];
        }
        
        return null;
    }
    
    /**
     * Normalize MAC address to uppercase with colon separators.
     *
     * Converts formats like:
     * - aa-bb-cc-dd-ee-ff → AA:BB:CC:DD:EE:FF
     * - aa:bb:cc:dd:ee:ff → AA:BB:CC:DD:EE:FF
     *
     * @param string $macAddress The MAC address to normalize
     * @return string The normalized MAC address
     */
    protected function normalizeMacAddress(string $macAddress): string
    {
        // Replace hyphens with colons
        $normalized = str_replace('-', ':', $macAddress);
        
        // Convert to uppercase
        return strtoupper($normalized);
    }
    
    /**
     * Determine if a VLAN should be scanned now.
     *
     * Checks if ipscan is enabled and if scan_interval_minutes have elapsed
     * since last_scanned_at.
     *
     * @param \App\Modules\Network\Models\Vlan $vlan The VLAN to check
     * @return bool True if the VLAN should be scanned, false otherwise
     */
    public function shouldScanVlan(Vlan $vlan): bool
    {
        // Check if ipscan is enabled
        if (!$vlan->ipscan) {
            return false;
        }

        // If never scanned, should scan now
        if ($vlan->last_scanned_at === null) {
            return true;
        }

        // Check if scan_interval_minutes have elapsed since last scan
        $intervalMinutes = $vlan->scan_interval_minutes ?? 60;
        $nextScanTime = $vlan->last_scanned_at->copy()->addMinutes($intervalMinutes);

        return now()->gte($nextScanTime);
    }
    
    /**
     * Scan all IP addresses in a VLAN using parallel processing.
     *
     * Retrieves all IP addresses for the VLAN and performs ping and MAC resolution
     * in parallel batches. Updates IP address records with scan results and handles errors gracefully.
     *
     * @param \App\Modules\Network\Models\Vlan $vlan The VLAN to scan
     * @param callable|null $progressCallback Optional callback for progress updates
     * @return array Summary with counts: ['scanned' => int, 'online' => int, 'offline' => int, 'duration' => float]
     */
    public function scanVlan($vlan, ?callable $progressCallback = null): array
    {
        $lockKey = "vlan_scan_{$vlan->id}";
        $lock = Cache::lock($lockKey, 600); // 10 minute lock timeout

        // Try to acquire lock
        if (!$lock->get()) {
            Log::warning('Scan already in progress for VLAN', [
                'vlan_id' => $vlan->id,
                'vlan_name' => $vlan->vlan_name,
            ]);

            return [
                'scanned' => 0,
                'online' => 0,
                'offline' => 0,
                'skipped' => true,
                'reason' => 'Concurrent scan in progress',
            ];
        }

        try {
            $scanStartTime = microtime(true);

            $scannedCount = 0;
            $onlineCount = 0;
            $offlineCount = 0;
            
            // Retrieve all IP addresses for the VLAN
            $ipAddresses = $vlan->ipAddresses;
            $totalCount = $ipAddresses->count();

            if ($progressCallback) {
                $progressCallback('start', $totalCount);
            }

            if ($totalCount === 0) {
                return [
                    'scanned' => 0,
                    'online' => 0,
                    'offline' => 0,
                    'duration' => 0,
                ];
            }
            
            // Process IPs in parallel batches
            $batchSize = 50; // Process 50 IPs concurrently
            $batches = $ipAddresses->chunk($batchSize);
            
            foreach ($batches as $batch) {
                // Run parallel pings for this batch
                $pingResults = $this->parallelPing($batch);
                
                // Process results and update database
                foreach ($batch as $index => $ipAddress) {
                    try {
                        $ip = $ipAddress->ip_address;
                        $pingResult = $pingResults[$ip] ?? ['is_online' => false, 'ping_ms' => null];
                        
                        $macAddress = null;
                        $dnsName = null;
                        
                        // If online, attempt to resolve MAC address and DNS name
                        if ($pingResult['is_online']) {
                            $macAddress = $this->resolveMacAddress($ip);
                            $resolved = gethostbyaddr($ip);
                            $dnsName = ($resolved !== $ip) ? $resolved : null;
                            $onlineCount++;
                        } else {
                            $offlineCount++;
                        }
                        
                        // Update IP address record with scan results
                        $ipAddress->updateFromScan(
                            $pingResult['is_online'],
                            $pingResult['ping_ms'],
                            $macAddress
                        );

                        // Update DNS name if resolved
                        if ($dnsName !== null) {
                            $ipAddress->dns_name = $dnsName;
                            $ipAddress->save();
                        }
                        
                        $scannedCount++;

                        if ($progressCallback) {
                            $progressCallback('advance', $scannedCount);
                        }
                        
                    } catch (\Exception $e) {
                        // Log error and continue with next IP address
                        Log::error('Error scanning IP address', [
                            'ip_address' => $ipAddress->ip_address,
                            'vlan_id' => $vlan->id,
                            'error' => $e->getMessage(),
                        ]);
                        
                        continue;
                    }
                }
            }

            $scanEndTime = microtime(true);
            $duration = round($scanEndTime - $scanStartTime, 2);

            // Log warning if scan took longer than 5 minutes (300 seconds)
            if ($duration > 300) {
                Log::warning('Long scan duration detected', [
                    'vlan_id' => $vlan->id,
                    'vlan_name' => $vlan->vlan_name,
                    'duration_seconds' => $duration,
                    'ip_count' => $scannedCount,
                ]);
            }
            
            return [
                'scanned' => $scannedCount,
                'online' => $onlineCount,
                'offline' => $offlineCount,
                'duration' => $duration,
            ];

        } finally {
            // Always release the lock
            $lock->release();
        }
    }

    /**
     * Perform parallel ping on multiple IP addresses.
     *
     * Uses Laravel's Process Pool to execute multiple ping commands concurrently.
     *
     * @param \Illuminate\Support\Collection $ipAddresses Collection of IP address models
     * @return array Associative array with IP as key and ping result as value
     */
    protected function parallelPing($ipAddresses): array
    {
        $isWindows = $this->isWindows();
        $results = [];
        
        Log::info('Starting parallel ping', [
            'count' => $ipAddresses->count(),
            'is_windows' => $isWindows,
        ]);
        
        // Build ping commands for all IPs
        $pool = Process::pool(function (Pool $pool) use ($ipAddresses, $isWindows) {
            foreach ($ipAddresses as $ipAddress) {
                $ip = $ipAddress->ip_address;
                
                if ($isWindows) {
                    $pool->as($ip)->command(['ping', '-n', '1', '-w', '60', $ip]);
                } else {
                    $pool->as($ip)->command(['ping', '-c', '1', '-W', '1', $ip]);
                }
            }
        });
        
        // Execute all pings concurrently and wait for results
        $processResults = $pool->start()->wait();
        
        Log::info('Parallel ping completed', [
            'results_count' => $ipAddresses->count(),
        ]);
        
        // Parse results - iterate over original IP addresses
        $debugCount = 0;
        foreach ($ipAddresses as $ipAddress) {
            $ip = $ipAddress->ip_address;
            
            // Get the process result for this IP
            $process = $processResults[$ip] ?? null;
            
            if (!$process) {
                Log::warning('No process result for IP', ['ip' => $ip]);
                $results[$ip] = [
                    'is_online' => false,
                    'ping_ms' => null,
                ];
                continue;
            }
            
            $output = $process->output();
            $errorOutput = $process->errorOutput();
            
            // Log first 2 IPs with full details
            if ($debugCount < 2) {
                Log::info('Ping output sample', [
                    'ip' => $ip,
                    'exit_code' => $process->exitCode(),
                    'output_length' => strlen($output),
                    'output' => $output,
                    'error' => $errorOutput,
                ]);
            }
            
            // Parse the output regardless of exit code (ping returns non-zero for unreachable hosts)
            $pingResult = $this->parsePingOutput($output, $isWindows);
            
            // Log parsed result for first IP
            if ($debugCount < 1) {
                Log::info('Parsed result sample', [
                    'ip' => $ip,
                    'is_online' => $pingResult['is_online'],
                    'ping_ms' => $pingResult['ping_ms'],
                ]);
            }
            
            $debugCount++;
            $results[$ip] = $pingResult;
        }
        
        return $results;
    }
}
