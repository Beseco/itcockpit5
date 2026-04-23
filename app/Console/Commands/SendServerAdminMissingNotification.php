<?php

namespace App\Console\Commands;

use App\Mail\ServerAdminMissingMail;
use App\Modules\Server\Models\Server;
use App\Modules\Server\Models\ServerAdminNotificationSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendServerAdminMissingNotification extends Command
{
    protected $signature   = 'server:send-admin-missing-notification';
    protected $description = 'Sendet eine E-Mail wenn Server ohne Administrator vorhanden sind (konfigurierbar).';

    public function handle(): int
    {
        $settings = ServerAdminNotificationSettings::getSingleton();

        if (!$settings->isConfigured()) {
            $this->info('Benachrichtigung deaktiviert oder keine E-Mail konfiguriert.');
            return self::SUCCESS;
        }

        if (!$settings->isDue()) {
            $this->info('Noch nicht fällig (Wochentag, Stunde oder Intervall nicht erfüllt).');
            return self::SUCCESS;
        }

        $servers = Server::with(['abteilung'])
            ->whereNull('admin_user_id')
            ->whereNotIn('status', ['ausgemustert'])
            ->orderBy('name')
            ->get();

        $settings->last_sent_at = now();
        $settings->save();

        if ($servers->isEmpty()) {
            $this->info('Alle aktiven Server haben einen Administrator. Keine E-Mail versendet.');
            return self::SUCCESS;
        }

        try {
            Mail::to($settings->email)->send(new ServerAdminMissingMail($servers));
            $this->info("E-Mail gesendet an {$settings->email}: {$servers->count()} Server ohne Administrator.");
        } catch (\Exception $e) {
            $this->error('Fehler beim Versand: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
