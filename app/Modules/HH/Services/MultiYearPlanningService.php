<?php

namespace App\Modules\HH\Services;

use App\Modules\HH\Models\BudgetPosition;
use App\Modules\HH\Models\BudgetYear;
use App\Modules\HH\Models\BudgetYearVersion;
use Illuminate\Support\Facades\DB;

class MultiYearPlanningService
{
    /**
     * Propagate all recurring positions from the given BudgetYear into the next year.
     *
     * For each position with is_recurring = true in the active version of $by,
     * finds or creates the BudgetYear for year + 1, ensures it has an active version,
     * and copies the position into that version with origin_position_id set.
     *
     * Requirements: 5.1, 5.4
     */
    public function propagateRecurring(BudgetYear $by): void
    {
        $activeVersion = BudgetYearVersion::where('budget_year_id', $by->id)
            ->where('is_active', true)
            ->first();

        if ($activeVersion === null) {
            return;
        }

        $recurringPositions = BudgetPosition::where('budget_year_version_id', $activeVersion->id)
            ->where('is_recurring', true)
            ->get();

        if ($recurringPositions->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($recurringPositions, $by): void {
            $nextYear = $by->year + 1;

            $nextBudgetYear = BudgetYear::firstOrCreate(
                ['year' => $nextYear],
                ['status' => 'draft', 'created_by' => $recurringPositions->first()->created_by]
            );

            $nextActiveVersion = $this->ensureActiveVersion($nextBudgetYear, $recurringPositions->first()->created_by);

            foreach ($recurringPositions as $position) {
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
                    'created_by',
                ]);

                BudgetPosition::create(array_merge($attributes, [
                    'budget_year_version_id' => $nextActiveVersion->id,
                    'origin_position_id'     => $position->id,
                ]));
            }
        });
    }

    /**
     * Generate positions for each year in the range [start_year, end_year] of the given position.
     *
     * Only applies if both start_year and end_year are set.
     * Validates that end_year >= start_year.
     * For each year in the range, finds or creates a BudgetYear and copies the position
     * into its active version with origin_position_id set.
     *
     * Requirements: 5.2, 5.3, 5.4
     *
     * @throws \InvalidArgumentException if end_year < start_year
     */
    public function generateForDateRange(BudgetPosition $pos): void
    {
        if ($pos->start_year === null || $pos->end_year === null) {
            return;
        }

        if ($pos->end_year < $pos->start_year) {
            throw new \InvalidArgumentException(
                "Das Endjahr ({$pos->end_year}) darf nicht kleiner als das Startjahr ({$pos->start_year}) sein."
            );
        }

        DB::transaction(function () use ($pos): void {
            for ($year = $pos->start_year; $year <= $pos->end_year; $year++) {
                $budgetYear = BudgetYear::firstOrCreate(
                    ['year' => $year],
                    ['status' => 'draft', 'created_by' => $pos->created_by]
                );

                $activeVersion = $this->ensureActiveVersion($budgetYear, $pos->created_by);

                $attributes = $pos->only([
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
                    'created_by',
                ]);

                BudgetPosition::create(array_merge($attributes, [
                    'budget_year_version_id' => $activeVersion->id,
                    'origin_position_id'     => $pos->id,
                ]));
            }
        });
    }

    /**
     * Ensure the given BudgetYear has an active version, creating one if missing.
     */
    private function ensureActiveVersion(BudgetYear $budgetYear, int $createdBy): BudgetYearVersion
    {
        $activeVersion = BudgetYearVersion::where('budget_year_id', $budgetYear->id)
            ->where('is_active', true)
            ->first();

        if ($activeVersion === null) {
            $activeVersion = BudgetYearVersion::create([
                'budget_year_id' => $budgetYear->id,
                'version_number' => 1,
                'is_active'      => true,
                'created_by'     => $createdBy,
                'created_at'     => now(),
            ]);
        }

        return $activeVersion;
    }
}
