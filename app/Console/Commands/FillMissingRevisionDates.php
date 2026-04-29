<?php

namespace App\Console\Commands;

use App\Models\Applikation;
use Illuminate\Console\Command;

class FillMissingRevisionDates extends Command
{
    protected $signature = 'applikationen:fill-revision-dates';
    protected $description = 'Setzt revision_date auf heute + 1 Jahr für alle Applikationen ohne Revisionsdatum';

    public function handle(): int
    {
        $count = Applikation::whereNull('revision_date')->count();

        if ($count === 0) {
            $this->info('Alle Applikationen haben bereits ein Revisionsdatum.');
            return self::SUCCESS;
        }

        $this->info("{$count} Applikation(en) ohne Revisionsdatum gefunden.");

        if (!$this->confirm('Revisionsdatum auf heute + 1 Jahr setzen?')) {
            $this->line('Abgebrochen.');
            return self::SUCCESS;
        }

        $date = now()->addYear()->toDateString();
        Applikation::whereNull('revision_date')->update(['revision_date' => $date]);

        $this->info("{$count} Einträge aktualisiert → {$date}.");
        return self::SUCCESS;
    }
}
