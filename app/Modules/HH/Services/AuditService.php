<?php

namespace App\Modules\HH\Services;

use App\Models\User;
use App\Modules\HH\Models\AuditEntry;
use App\Modules\HH\Models\BudgetPosition;
use App\Modules\HH\Models\BudgetYearVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AuditService
{
    /**
     * Log an immutable audit entry.
     * Never calls update() on existing entries.
     */
    public function log(
        User $actor,
        string $entityType,
        int $entityId,
        string $field,
        mixed $oldValue,
        mixed $newValue
    ): void {
        AuditEntry::create([
            'user_id'     => $actor->id,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'field'       => $field,
            'old_value'   => $oldValue !== null ? (string) $oldValue : null,
            'new_value'   => $newValue !== null ? (string) $newValue : null,
            'created_at'  => now(),
        ]);
    }

    /**
     * Retrieve audit entries with optional filters.
     *
     * Supported filters:
     *   - budget_year_id  (int)    – entries related to that BudgetYear or its BudgetPositions
     *   - cost_center_id  (int)    – entries related to that CostCenter or BudgetPositions of it
     *   - from            (string) – created_at >= value
     *   - to              (string) – created_at <= value
     *
     * @param  array{budget_year_id?: int, cost_center_id?: int, from?: string, to?: string}  $filters
     */
    public function getEntries(array $filters = []): Collection
    {
        $query = AuditEntry::query()->orderBy('created_at', 'desc');

        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        if (!empty($filters['budget_year_id'])) {
            $budgetYearId = (int) $filters['budget_year_id'];

            // Position IDs that belong to any version of this budget year
            $positionIds = BudgetPosition::query()
                ->whereIn(
                    'budget_year_version_id',
                    BudgetYearVersion::query()
                        ->where('budget_year_id', $budgetYearId)
                        ->select('id')
                )
                ->pluck('id');

            $query->where(function ($q) use ($budgetYearId, $positionIds) {
                // Direct entries on the BudgetYear entity
                $q->where(function ($inner) use ($budgetYearId) {
                    $inner->where('entity_type', 'BudgetYear')
                          ->where('entity_id', $budgetYearId);
                })
                // Entries on BudgetYearVersion entities of this year
                ->orWhere(function ($inner) use ($budgetYearId) {
                    $inner->where('entity_type', 'BudgetYearVersion')
                          ->whereIn(
                              'entity_id',
                              BudgetYearVersion::query()
                                  ->where('budget_year_id', $budgetYearId)
                                  ->select('id')
                          );
                })
                // Entries on BudgetPosition entities belonging to this year
                ->orWhere(function ($inner) use ($positionIds) {
                    $inner->where('entity_type', 'BudgetPosition')
                          ->whereIn('entity_id', $positionIds);
                });
            });
        }

        if (!empty($filters['cost_center_id'])) {
            $costCenterId = (int) $filters['cost_center_id'];

            // Position IDs that belong to this cost center
            $positionIds = BudgetPosition::query()
                ->where('cost_center_id', $costCenterId)
                ->pluck('id');

            $query->where(function ($q) use ($costCenterId, $positionIds) {
                // Direct entries on the CostCenter entity
                $q->where(function ($inner) use ($costCenterId) {
                    $inner->where('entity_type', 'CostCenter')
                          ->where('entity_id', $costCenterId);
                })
                // Entries on BudgetPosition entities of this cost center
                ->orWhere(function ($inner) use ($positionIds) {
                    $inner->where('entity_type', 'BudgetPosition')
                          ->whereIn('entity_id', $positionIds);
                });
            });
        }

        return $query->get();
    }
}
