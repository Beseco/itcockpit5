<?php

namespace App\Modules\Vertragsmanagement\Console\Commands;

use App\Modules\Vertragsmanagement\Mail\ContractReminderMail;
use App\Modules\Vertragsmanagement\Models\Vertrag;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendContractRemindersCommand extends Command
{
    protected $signature = 'contracts:send-reminders
                            {--dry-run : Nur anzeigen, welche Erinnerungen gesendet würden}';

    protected $description = 'Sendet wöchentliche Erinnerungen für auslaufende Verträge und markiert abgelaufene Verträge';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // 1. Abgelaufene aktive Verträge auf "abgelaufen" setzen.
        $abgelaufen = Vertrag::where('status', 'aktiv')
            ->whereNotNull('vertragsende')
            ->whereDate('vertragsende', '<', Carbon::today())
            ->get();

        foreach ($abgelaufen as $v) {
            if (!$dryRun) {
                $v->update(['status' => 'abgelaufen']);
            }
            $this->line("  Vertrag #{$v->id} '{$v->name}' → abgelaufen");
        }

        // 2. Verträge in der Erinnerungsphase.
        $faellige = Vertrag::faelligFuerErinnerung()->get();

        if ($faellige->isEmpty()) {
            $this->info('Keine Verträge in der Erinnerungsphase.');
            return 0;
        }

        $this->info("{$faellige->count()} Vertrag/Verträge in der Erinnerungsphase – prüfe wöchentlichen Versand …");

        $gesendet = 0;
        foreach ($faellige as $vertrag) {
            // Wöchentlich: nur senden, wenn die letzte Erinnerung >= 7 Tage her ist.
            if ($vertrag->last_reminder_sent_at
                && $vertrag->last_reminder_sent_at->gt(Carbon::now()->subDays(7))) {
                $this->line("  → '{$vertrag->name}': zuletzt am "
                    . $vertrag->last_reminder_sent_at->format('d.m.Y')
                    . " erinnert – noch keine Woche vergangen, übersprungen.");
                continue;
            }

            $empfaenger = $vertrag->getEmpfaengerEmail();
            if (!$empfaenger) {
                $this->warn("  → '{$vertrag->name}': kein Empfänger (auch kein Fallback) – übersprungen.");
                continue;
            }

            if ($dryRun) {
                $this->line("  → [DRY-RUN] würde Erinnerung an {$empfaenger} senden für '{$vertrag->name}'");
                continue;
            }

            try {
                Mail::to($empfaenger)->send(new ContractReminderMail($vertrag));
                $vertrag->update(['last_reminder_sent_at' => Carbon::now()]);
                $gesendet++;
                $this->info("  → Erinnerung gesendet an {$empfaenger} für '{$vertrag->name}'");
            } catch (\Throwable $e) {
                $this->error("  → Fehler bei '{$vertrag->name}': {$e->getMessage()}");
                Log::error('Vertragsmanagement: Erinnerung fehlgeschlagen', [
                    'vertrag_id' => $vertrag->id,
                    'empfaenger' => $empfaenger,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        $this->info("Fertig. {$gesendet} Erinnerung(en) gesendet.");
        return 0;
    }
}
