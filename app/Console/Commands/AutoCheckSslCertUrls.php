<?php

namespace App\Console\Commands;

use App\Modules\SslCerts\Models\SslCertHistory;
use App\Modules\SslCerts\Models\SslCertificate;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoCheckSslCertUrls extends Command
{
    protected $signature   = 'sslcerts:auto-check-url';
    protected $description = 'Zertifikate die per URL importiert wurden täglich neu abrufen und bei Änderung aktualisieren';

    public function handle(): int
    {
        $certs = SslCertificate::whereNotNull('source_url')->get();

        if ($certs->isEmpty()) {
            $this->info('Keine Zertifikate mit hinterlegter Quell-URL gefunden.');
            return self::SUCCESS;
        }

        $updated = 0;
        $unchanged = 0;
        $failed = 0;

        foreach ($certs as $cert) {
            $this->line("Prüfe: {$cert->name} ({$cert->source_url})");

            $certPem = $this->fetchPemFromUrl($cert->source_url);

            if ($certPem === null) {
                $this->warn("  ✗ Verbindung fehlgeschlagen: {$cert->source_url}");
                $failed++;
                continue;
            }

            $newFingerprint = openssl_x509_fingerprint($certPem, 'sha256');

            if ($newFingerprint === $cert->fingerprint_sha256) {
                $cert->update(['last_auto_check_at' => now()]);
                $this->line('  = Unverändert');
                $unchanged++;
                continue;
            }

            // Neues Zertifikat gefunden → aktualisieren
            $parsed    = openssl_x509_parse($certPem);
            $validFrom = isset($parsed['validFrom_time_t'])
                ? Carbon::createFromTimestamp($parsed['validFrom_time_t']) : null;
            $validTo   = isset($parsed['validTo_time_t'])
                ? Carbon::createFromTimestamp($parsed['validTo_time_t']) : null;

            $sanRaw = $parsed['extensions']['subjectAltName'] ?? '';
            $sans   = [];
            foreach (explode(',', $sanRaw) as $entry) {
                $entry = trim($entry);
                if ($entry) $sans[] = $entry;
            }

            $cert->update([
                'subject_cn'         => $parsed['subject']['CN']  ?? null,
                'subject_o'          => $parsed['subject']['O']   ?? null,
                'subject_ou'         => $parsed['subject']['OU']  ?? null,
                'issuer_cn'          => $parsed['issuer']['CN']   ?? null,
                'issuer_o'           => $parsed['issuer']['O']    ?? null,
                'serial_number'      => $parsed['serialNumberHex'] ?? ($parsed['serialNumber'] ?? null),
                'valid_from'         => $validFrom,
                'valid_to'           => $validTo,
                'san'                => $sans ?: null,
                'fingerprint_sha1'   => openssl_x509_fingerprint($certPem, 'sha1') ?: null,
                'fingerprint_sha256' => $newFingerprint,
                'cert_pem'           => $certPem,
                'notified_30_at'     => null,
                'notified_14_at'     => null,
                'notified_daily_at'  => null,
                'last_auto_check_at' => now(),
            ]);

            $note = $validTo ? 'Automatisch erneuert – gültig bis ' . $validTo->format('d.m.Y') : 'Automatisch erneuert';
            SslCertHistory::create([
                'ssl_certificate_id' => $cert->id,
                'user_id'            => null,
                'user_name'          => 'System (Auto-Check)',
                'action'             => 'erneuert',
                'note'               => $note,
            ]);

            $this->info("  ✓ Neues Zertifikat gefunden und aktualisiert (gültig bis {$validTo?->format('d.m.Y')})");
            $updated++;
        }

        $this->info("Auto-Check abgeschlossen: {$updated} aktualisiert, {$unchanged} unverändert, {$failed} fehlgeschlagen.");
        return self::SUCCESS;
    }

    private function fetchPemFromUrl(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT) ?? 443;

        if (!$host) return null;

        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'SNI_enabled'       => true,
                'peer_name'         => $host,
            ],
        ]);

        $socket = @stream_socket_client(
            "ssl://{$host}:{$port}",
            $errno, $errstr, 10,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) return null;

        $params = stream_context_get_params($socket);
        fclose($socket);

        $certResource = $params['options']['ssl']['peer_certificate'] ?? null;
        if (!$certResource) return null;

        openssl_x509_export($certResource, $certPem);
        return $certPem ?: null;
    }
}
