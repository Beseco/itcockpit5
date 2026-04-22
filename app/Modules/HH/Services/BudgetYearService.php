<?php

namespace App\Modules\HH\Services;

use App\Models\User;
use App\Modules\HH\Models\BudgetPosition;
use App\Modules\HH\Models\BudgetYear;
use App\Modules\HH\Models\BudgetYearVersion;
use Illuminate\Support\Facades\DB;

class BudgetYearService
{
    /** Valid status transitions: currentStatus => allowedNextStatus */
    private const ALLOWED_TRANSITIONS = [
        'draft'       => 'preliminary',
        'preliminary' => 'approved',
    ];

    public function __construct(private readonly AuditService $auditService)
    {
    }

    /**
     * Create a new BudgetYear for the given calendar year.
     *
     * Initialises with status `draft` and creates the first BudgetYearVersion
     * (version_number = 1, is_active = true).
     *
     * @throws \RuntimeException if a BudgetYear for that year already exists
     */
    public function create(int $year, User $actor): BudgetYear
    {
        if (BudgetYear::where('year', $year)->exists()) {
            throw new \RuntimeException(
                "Ein Haushaltsjahr für das Jahr {$year} existiert bereits."
            );
        }

        return DB::transaction(function () use ($year, $actor): BudgetYear {
            /** @var BudgetYear $budgetYear */
            $budgetYear = BudgetYear::create([
                'year'       => $year,
                'status'     => 'draft',
                'created_by' => $actor->id,
            ]);

            BudgetYearVersion::create([
                'budget_year_id' => $budgetYear->id,
                'version_number' => 1,
                'is_active'      => true,
                'created_by'     => $actor->id,
                'created_at'     => now(),
            ]);

            $this->auditService->log(
                $actor,
                'BudgetYear',
                $budgetYear->id,
                'status',
                null,
                'draft'
            );

            return $budgetYear;
        });
    }

    /**
     * Create a new version of the given BudgetYear.
     *
     * - Finds the currently active version
     * - Copies all BudgetPositions from the active version into the new version
     * - Sets the previous active version to is_active = false
     * - Creates a new BudgetYearVersion with version_number = previous + 1 and is_active = true
     * - Logs the version creation via AuditService
     *
     * @throws \RuntimeException if no active version exists
     */
    public function createVersion(BudgetYear $budgetYear, User $actor): BudgetYearVersion
    {
        return DB::transaction(function () use ($budgetYear, $actor): BudgetYearVersion {
            /** @var BudgetYearVersion|null $activeVersion */
            $activeVersion = BudgetYearVersion::where('budget_year_id', $budgetYear->id)
                ->where('is_active', true)
                ->first();

            if ($activeVersion === null) {
                throw new \RuntimeException(
                    "Keine aktive Version für das Haushaltsjahr {$budgetYear->year} gefunden."
                );
            }

            // Deactivate the current active version
            $activeVersion->is_active = false;
            $activeVersion->save();

            // Create the new version
            $newVersion = BudgetYearVersion::create([
                'budget_year_id' => $budgetYear->id,
                'version_number' => $activeVersion->version_number + 1,
                'is_active'      => true,
                'created_by'     => $actor->id,
                'created_at'     => now(),
            ]);

            // Copy all positions from the active version into the new version
            $positions = BudgetPosition::where('budget_year_version_id', $activeVersion->id)->get();

            foreach ($positions as $position) {
                $attributes = $position->only([
                    'cost_center_id',
                    'account_id',
                    'project_name',
                    'description',
                    'amount',
                    'start_year',
                    'end_year',
                    'is_recurring',
                    'priority',
                    'category',
                    'status',
                    'origin_position_id',
                    'created_by',
                ]);

                BudgetPosition::create(array_merge($attributes, [
                    'budget_year_version_id' => $newVersion->id,
                ]));
            }

            $this->auditService->log(
                $actor,
                'BudgetYearVersion',
                $newVersion->id,
                'version_number',
                $activeVersion->version_number,
                $newVersion->version_number
            );

            return $newVersion;
        });
    }

    /**
     * Copy all recurring positions from the active version of $source into the active version of $target.
     * Skips positions that already exist (duplicate check: cost_center + account + project_name + amount).
     *
     * @return int number of positions actually created
     * @throws \RuntimeException if either year has no active version
     */
    public function carryOverRecurringPositions(BudgetYear $source, BudgetYear $target, User $actor): int
    {
        $sourceVersion = BudgetYearVersion::where('budget_year_id', $source->id)
            ->where('is_active', true)->first();
        $targetVersion = BudgetYearVersion::where('budget_year_id', $target->id)
            ->where('is_active', true)->first();

        if (!$sourceVersion) {
            throw new \RuntimeException("Keine aktive Version für Quell-Haushaltsjahr {$source->year}.");
        }
        if (!$targetVersion) {
            throw new \RuntimeException("Keine aktive Version für Ziel-Haushaltsjahr {$target->year}.");
        }

        $recurringPositions = BudgetPosition::where('budget_year_version_id', $sourceVersion->id)
            ->where('is_recurring', true)
            ->get();

        $carried = 0;

        DB::transaction(function () use ($recurringPositions, $targetVersion, $actor, &$carried) {
            foreach ($recurringPositions as $pos) {
                $exists = BudgetPosition::where('budget_year_version_id', $targetVersion->id)
                    ->where('cost_center_id', $pos->cost_center_id)
                    ->where('account_id', $pos->account_id)
                    ->whereRaw('LOWER(project_name) = LOWER(?)', [$pos->project_name])
                    ->where('amount', $pos->amount)
                    ->exists();

                if ($exists) continue;

                BudgetPosition::create([
                    'budget_year_version_id' => $targetVersion->id,
                    'cost_center_id'         => $pos->cost_center_id,
                    'account_id'             => $pos->account_id,
                    'project_name'           => $pos->project_name,
                    'description'            => $pos->description,
                    'amount'                 => $pos->amount,
                    'start_year'             => $pos->start_year,
                    'end_year'               => $pos->end_year,
                    'is_recurring'           => true,
                    'priority'               => $pos->priority,
                    'category'               => $pos->category,
                    'status'                 => 'geplant',
                    'created_by'             => $actor->id,
                ]);

                $carried++;
            }
        });

        $this->auditService->log(
            $actor, 'BudgetYear', $target->id,
            'carry_over_recurring', null,
            "Quelle: {$source->year}, übertragen: {$carried}"
        );

        return $carried;
    }

    /**
     * Transition a BudgetYear to a new status.
     *
     * Only `draft → preliminary` and `preliminary → approved` are allowed.
     *
     * @throws \InvalidArgumentException for invalid transitions
     */
    public function transitionStatus(BudgetYear $budgetYear, string $newStatus, User $actor): void
    {
        $currentStatus = $budgetYear->status;

        $allowed = self::ALLOWED_TRANSITIONS[$currentStatus] ?? null;

        if ($allowed !== $newStatus) {
            throw new \InvalidArgumentException(
                "Ungültiger Statusübergang: '{$currentStatus}' → '{$newStatus}'. " .
                "Erlaubte Übergänge: draft → preliminary, preliminary → approved."
            );
        }

        $oldStatus = $currentStatus;

        $budgetYear->status = $newStatus;
        $budgetYear->save();

        $this->auditService->log(
            $actor,
            'BudgetYear',
            $budgetYear->id,
            'status',
            $oldStatus,
            $newStatus
        );
    }
}
