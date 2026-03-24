<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ReminderMail extends Model
{
    protected $table = 'erinnerungsmail';

    protected $fillable = [
        'user_id',
        'status',
        'titel',
        'nachricht',
        'nextsend',
        'mailto',
        'intervall_typ',
        'intervall_config',
    ];

    protected $casts = [
        'nextsend'         => 'datetime',
        'status'           => 'integer',
        'intervall_config' => 'array',
        'mailto'           => 'array',
    ];

    public function getMailtoLabelAttribute(): string
    {
        return implode(', ', (array)($this->mailto ?? []));
    }

    const TYPEN = [
        'minutes' => 'Minuten',
        'hours'   => 'Stunden',
        'days'    => 'Tage',
        'months'  => 'Monate',
        'weekly'  => 'Wöchentlich',
        'monthly' => 'Monatlich',
        'yearly'  => 'Jährlich',
    ];

    const WOCHENTAGE = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

    const MONATE = [
        1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
        5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
    ];

    // Carbon day-of-week: 0=Sun,1=Mon,...,6=Sat
    const DOW_MAP = ['So' => 0, 'Mo' => 1, 'Di' => 2, 'Mi' => 3, 'Do' => 4, 'Fr' => 5, 'Sa' => 6];

    public function getIntervallLabelAttribute(): string
    {
        $typ    = $this->intervall_typ;
        $config = $this->intervall_config ?? [];

        return match ($typ) {
            'minutes' => 'Alle ' . ($config['every'] ?? '?') . ' Minute(n)',
            'hours'   => 'Alle ' . ($config['every'] ?? '?') . ' Stunde(n)',
            'days'    => 'Alle ' . ($config['every'] ?? '?') . ' Tag(e)',
            'months'  => 'Alle ' . ($config['every'] ?? '?') . ' Monat(e)',
            'weekly'  => 'Wöchentlich ' . implode(', ', $config['days'] ?? []) . ' um ' . ($config['time'] ?? '?'),
            'monthly' => 'Monatlich am ' . $this->nthLabel($config['nth'] ?? 1) . ' '
                       . ($config['weekday'] ?? '?') . ' um ' . ($config['time'] ?? '?'),
            'yearly'  => 'Jährlich am ' . ($config['day'] ?? '?') . '.'
                       . ($config['month'] ?? '?') . '. um ' . ($config['time'] ?? '?'),
            default   => '—',
        };
    }

    private function nthLabel(int|string $nth): string
    {
        return match ((string)$nth) {
            '1'    => '1.',
            '2'    => '2.',
            '3'    => '3.',
            '4'    => '4.',
            '5'    => '5.',
            'last' => 'letzten',
            default => $nth . '.',
        };
    }

    public function getRestzeitAttribute(): string
    {
        if ($this->nextsend->isPast()) {
            return 'überfällig';
        }
        $diff = now()->diff($this->nextsend);
        if ($diff->days > 0) {
            return $diff->days . ' ' . ($diff->days === 1 ? 'Tag' : 'Tage');
        }
        if ($diff->h > 0) {
            return $diff->h . ' ' . ($diff->h === 1 ? 'Stunde' : 'Stunden');
        }
        return $diff->i . ' ' . ($diff->i === 1 ? 'Minute' : 'Minuten');
    }

    public function calculateNextSend(Carbon $from = null): Carbon
    {
        $from   = ($from ?? now())->copy();
        $config = $this->intervall_config ?? [];

        return match ($this->intervall_typ) {
            'minutes' => $from->addMinutes((int)($config['every'] ?? 1)),
            'hours'   => $from->addHours((int)($config['every'] ?? 1)),
            'days'    => $from->addDays((int)($config['every'] ?? 1)),
            'months'  => $from->addMonths((int)($config['every'] ?? 1)),
            'weekly'  => $this->calcNextWeekly($from, $config),
            'monthly' => $this->calcNextMonthly($from, $config),
            'yearly'  => $this->calcNextYearly($from, $config),
            default   => $from->addDay(),
        };
    }

    private function calcNextWeekly(Carbon $from, array $config): Carbon
    {
        $days = $config['days'] ?? [];
        $time = $config['time'] ?? '08:00';
        [$h, $m] = array_map('intval', explode(':', $time));

        for ($i = 0; $i <= 7; $i++) {
            $candidate = $from->copy()->addDays($i)->setHour($h)->setMinute($m)->setSecond(0);
            $dow       = $candidate->dayOfWeek; // 0=Sun
            $key       = array_search($dow, self::DOW_MAP);
            if ($key !== false && in_array($key, $days) && $candidate->gt($from)) {
                return $candidate;
            }
        }

        // Fallback: next week same time
        return $from->addWeek()->setHour($h)->setMinute($m)->setSecond(0);
    }

    private function calcNextMonthly(Carbon $from, array $config): Carbon
    {
        $nth     = $config['nth'] ?? 1;
        $weekday = $config['weekday'] ?? 'Mo';
        $time    = $config['time'] ?? '08:00';
        [$h, $m] = array_map('intval', explode(':', $time));
        $targetDow = self::DOW_MAP[$weekday] ?? 1;

        $tryMonth = $from->copy()->startOfMonth();
        for ($attempt = 0; $attempt < 13; $attempt++) {
            $occurrence = $this->findNthWeekdayInMonth($tryMonth, $nth, $targetDow);
            $candidate  = $occurrence->copy()->setHour($h)->setMinute($m)->setSecond(0);
            if ($candidate->gt($from)) {
                return $candidate;
            }
            $tryMonth->addMonth()->startOfMonth();
        }

        return $from->addMonth();
    }

    private function findNthWeekdayInMonth(Carbon $monthStart, int|string $nth, int $targetDow): Carbon
    {
        if ($nth === 'last') {
            $day = $monthStart->copy()->endOfMonth();
            while ($day->dayOfWeek !== $targetDow) {
                $day->subDay();
            }
            return $day;
        }

        $day   = $monthStart->copy();
        $count = 0;
        while ((int)$day->format('n') === (int)$monthStart->format('n')) {
            if ($day->dayOfWeek === $targetDow) {
                $count++;
                if ($count === (int)$nth) {
                    return $day->copy();
                }
            }
            $day->addDay();
        }

        return $monthStart->copy(); // fallback
    }

    private function calcNextYearly(Carbon $from, array $config): Carbon
    {
        $day   = (int)($config['day'] ?? 1);
        $month = (int)($config['month'] ?? 1);
        $time  = $config['time'] ?? '08:00';
        [$h, $m] = array_map('intval', explode(':', $time));

        $candidate = Carbon::create($from->year, $month, $day, $h, $m, 0);
        if ($candidate->lte($from)) {
            $candidate->addYear();
        }

        return $candidate;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
