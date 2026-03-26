<?php

namespace App\Console\Commands;

use App\Models\Applikation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedRevisionDates extends Command
{
    protected $signature   = 'applikationen:seed-revision-dates';
    protected $description = 'Setzt zufällige Revisionsdaten (01.04.2026–01.12.2026) für Applikationen ohne Datum und generiert Revisions-Token.';

    public function handle(): int
    {
        $apps = Applikation::whereNull('revision_date')->get();

        if ($apps->isEmpty()) {
            $this->info('Alle Applikationen haben bereits ein Revisionsdatum.');
            return self::SUCCESS;
        }

        $start = Carbon::create(2026, 4, 1);
        $end   = Carbon::create(2026, 12, 1);
        $days  = (int) $start->diffInDays($end); // 244

        $count = 0;
        foreach ($apps as $app) {
            $app->revision_date  = (clone $start)->addDays(rand(0, $days));
            $app->revision_token = $app->revision_token ?? Str::random(64);
            $app->save();
            $count++;
        }

        // Token auch für Apps mit vorhandenem Datum setzen, falls noch keiner da ist
        Applikation::whereNotNull('revision_date')->whereNull('revision_token')->each(function ($app) {
            $app->update(['revision_token' => Str::random(64)]);
        });

        $this->info("Revisionsdatum gesetzt für {$count} Applikationen.");
        return self::SUCCESS;
    }
}
