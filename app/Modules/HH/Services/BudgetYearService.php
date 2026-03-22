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
