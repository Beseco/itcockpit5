<?php

namespace App\Console\Commands;

use App\Mail\SslCertExpiryMail;
use App\Modules\SslCerts\Models\SslCertificate;
use App\Modules\SslCerts\Models\SslCertsSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckSslCertExpiry extends Command
{
    protected $signature   = 'sslcerts:check-expiry';
    protected $description = 'Ablaufende SSL-Zertifikate prüfen und E-Mail-Benachrichtigungen versenden';

    public function handle(): int
    {
        $settings = SslCertsSettings::getSingleton();

        if (!$settings->isConfigured()) {
            $this->info('SSL-Benachrichtigungen deaktiviert oder keine E-Mail konfiguriert.');
            return self::SUCCESS;
        }

        $certs = SslCertificate::with('responsibleUser')
            ->whereNotNull('valid_to')
            ->where('valid_to', '>', now())          // noch nicht abgelaufen
            ->where('valid_to', '<=', now()->addDays(30))
            ->get();

        $sent = 0;

        foreach ($certs as $cert) {
            $days = $cert->getDaysRemaining();

            // ── 30-Tage-Warnung (einmalig) ───────────────────────────────────
            if ($days <= 30 && $days > 14 && !$cert->notified_30_at) {
                $this->sendMail($settings, $cert, 30);
                $cert->update(['notified_30_at' => now()]);
                $sent++;
                continue;
            }

            // ── 14-Tage-Warnung (einmalig) ───────────────────────────────────
            if ($days <= 14 && $days > 7 && !$cert->notified_14_at) {
                $this->sendMail($settings, $cert, 14);
                $cert->update(['notified_14_at' => now()]);
                $sent++;
                continue;
            }

            // ── Tägliche Warnung ab ≤ 7 Tage ────────────────────────────────
            if ($days <= 7) {
                $lastDaily = $cert->notified_daily_at;
                if (!$lastDaily || $lastDaily->isYesterday() || $lastDaily->lt(now()->startOfDay())) {
                    $this->sendMail($settings, $cert, $days);
                    $cert->update(['notified_daily_at' => now()]);
                    $sent++;
                }
            }
        }

        $this->info("SSL-Ablaufprüfung abgeschlossen. {$sent} E-Mail(s) versendet.");
        return self::SUCCESS;
    }

    private function sendMail(SslCertsSettings $settings, SslCertificate $cert, int $days): void
    {
        $recipients = array_filter([
            $settings->notification_email,
            $cert->responsibleUser?->email,
        ]);

        foreach (array_unique($recipients) as $email) {
            Mail::to($email)->send(new SslCertExpiryMail($cert, $days));
        }
    }
}
