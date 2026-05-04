<?php

namespace App\Console\Commands;

use App\Mail\UserMissingMail;
use App\Models\User;
use App\Models\UserNotificationSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMissingUserMail extends Command
{
    protected $signature = 'users:send-missing-mail';
    protected $description = 'Sendet eine Erinnerungs-Mail an Benutzer, die seit X Tagen inaktiv sind';

    public function handle(): void
    {
        $settings = UserNotificationSettings::getSingleton();

        if (! $settings->missing_mail_enabled) {
            $this->info('Missing-Mail ist deaktiviert.');
            return;
        }

        $days = $settings->missing_mail_days;
        $cutoff = now()->subDays($days);

        $users = User::whereNotNull('last_login_at')
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('last_active_at')
                  ->orWhere('last_active_at', '<', $cutoff);
            })
            ->where('last_login_at', '<', $cutoff)
            ->get();

        $count = 0;
        foreach ($users as $user) {
            $inactiveDays = (int) now()->diffInDays($user->last_active_at ?? $user->last_login_at);
            Mail::send(new UserMissingMail($user, $inactiveDays));
            $count++;
        }

        $this->info("Missing-Mail versendet an {$count} Benutzer.");
    }
}
