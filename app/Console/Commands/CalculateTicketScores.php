<?php

namespace App\Console\Commands;

use App\Mail\TicketScoreMail;
use App\Models\User;
use App\Modules\Tickets\Models\TicketScore;
use App\Modules\Tickets\Models\TicketsSettings;
use App\Modules\Tickets\Services\ZammadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CalculateTicketScores extends Command
{
    protected $signature   = 'tickets:calculate-scores';
    protected $description = 'Berechnet Ticket-Scores je Benutzer und sendet ggf. E-Mail-Benachrichtigungen';

    public function handle(): int
    {
        $settings = TicketsSettings::getSingleton();

        if (!$settings->isConfigured()) {
            $this->warn('Zammad ist nicht konfiguriert. Abbruch.');
            return self::SUCCESS;
        }

        $service = new ZammadService();
        $users   = User::whereNotNull('email')->get();
        $count   = 0;

        foreach ($users as $user) {
            $tickets = $service->searchTickets(email: $user->email, includeClosed: false);

            $yellowTickets = $tickets->filter(fn($t) => ZammadService::getTicketColor($t) === 'yellow');
            $redTickets    = $tickets->filter(fn($t) => ZammadService::getTicketColor($t) === 'red');

            $yellowCount = $yellowTickets->count();
            $redCount    = $redTickets->count();
            $score       = ($yellowCount * 0.5) + ($redCount * 1.0);

            TicketScore::create([
                'user_id'       => $user->id,
                'score'         => $score,
                'yellow_count'  => $yellowCount,
                'red_count'     => $redCount,
                'calculated_at' => now(),
            ]);

            if ($settings->email_enabled && $score >= (float) $settings->email_threshold) {
                try {
                    Mail::to($user->email)->send(
                        new TicketScoreMail($user, $score, $yellowTickets, $redTickets, $settings)
                    );
                    $this->line("Mail gesendet an {$user->email} (Score: {$score})");
                } catch (\Exception $e) {
                    $this->error("Mail-Fehler für {$user->email}: " . $e->getMessage());
                }
            }

            $count++;
        }

        $this->info("Ticket-Scores berechnet für {$count} Benutzer.");
        return self::SUCCESS;
    }
}
