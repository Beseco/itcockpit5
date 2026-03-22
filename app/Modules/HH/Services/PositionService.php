<?php

namespace App\Modules\HH\Services;

use App\Models\User;
use App\Modules\HH\Models\BudgetPosition;
use App\Modules\HH\Models\BudgetYear;
use App\Modules\HH\Models\BudgetYearVersion;
use App\Modules\HH\Models\CostCenter;
use App\Modules\HH\Models\Account;

class PositionService
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,
        private readonly AuditService $auditService,
    ) {
    }

    /**
     * Create a new BudgetPosition.
     *
     * Validates:
     * - CostCenter is active
     * - Account is active
     * - User has write access (Teamleiter or higher) on the CostCenter
     * - BudgetYear is not `approved`
     * - For `preliminary` status: only Leiter can create
     *
     * @param  array{
     *   budget_year_version_id: int,
     *   cost_center_id: int,
     *   account_id: int,
     *   project_name: string,
     *   amount: float,
     *   priority: string,
     *   category: string,
     *   status: string,
     *   description?: string|null,
     *   start_year?: int|null,
     *   end_year?: int|null,
     *   is_recurring?: bool,
     *   origin_position_id?: int|null,
     * } $data
     *
     * @throws \InvalidArgumentException for validation violations
     * @throws \RuntimeException for authorization violations
     */
    public function create(array $data, User $actor): BudgetPosition
    {
        $costCenter = CostCenter::findOrFail($data['cost_center_id']);
        $account    = Account::findOrFail($data['account_id']);
        $version    = BudgetYearVersion::with('budgetYear')->findOrFail($data['budget_year_version_id']);
        $budgetYear = $version->budgetYear;

        $this->assertCostCenterActive($costCenter);
        $this->assertAccountActive($account);
        $this->assertBudgetYearNotApproved($budgetYear);
        $this->assertWriteAccess($actor, $costCenter, $budgetYear);

        $position = BudgetPosition::create(array_merge($data, [
            'created_by' => $actor->id,
        ]));

        $this->auditService->log(
            $actor,
            'BudgetPosition',
            $position->id,
            'status',
            null,
            $position->status
        );

        return $position;
    }

    /**
     * Update an existing BudgetPosition.
     *
     * Validates:
     * - BudgetYear is not `approved`
     * - User has write access on the CostCenter
     * - For `preliminary` status: only Leiter can edit
     *
     * Logs amount changes via AuditService.
     *
     * @throws \InvalidArgumentException for validation violations
     * @throws \RuntimeException for authorization violations
     */
    public function update(BudgetPosition $pos, array $data, User $actor): BudgetPosition
    {
        $pos->loadMissing(['budgetYearVersion.budgetYear', 'costCenter']);

        $budgetYear = $pos->budgetYearVersion->budgetYear;
        $costCenter = $pos->costCenter;

        $this->assertBudgetYearNotApproved($budgetYear);
        $this->assertWriteAccess($actor, $costCenter, $budgetYear);

        $oldAmount = $pos->amount;

        $pos->fill($data);
        $pos->save();

        // Log amount change if it changed
        if (isset($data['amount']) && (string) $oldAmount !== (string) $pos->amount) {
            $this->auditService->log(
                $actor,
                'BudgetPosition',
                $pos->id,
                'amount',
                $oldAmount,
                $pos->amount
            );
        }

        return $pos;
    }

    /**
     * Delete a BudgetPosition.
     *
     * Validates:
     * - BudgetYear is not `approved`
     * - Mitarbeiter cannot delete (only Teamleiter or Leiter can delete)
     *
     * @throws \InvalidArgumentException if BudgetYear is approved
     * @throws \RuntimeException if user lacks delete permission
     */
    public function delete(BudgetPosition $pos, User $actor): void
    {
        $pos->loadMissing(['budgetYearVersion.budgetYear', 'costCenter']);

        $budgetYear = $pos->budgetYearVersion->budgetYear;
        $costCenter = $pos->costCenter;

        $this->assertBudgetYearNotApproved($budgetYear);
        $this->assertDeleteAccess($actor, $costCenter);

        $this->auditService->log(
            $actor,
            'BudgetPosition',
            $pos->id,
            'status',
            $pos->status,
            null
        );

        $pos->delete();
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function assertCostCenterActive(CostCenter $costCenter): void
    {
        if (! $costCenter->is_active) {
            throw new \InvalidArgumentException(
                "Die Kostenstelle '{$costCenter->number}' ist inaktiv und kann keiner neuen Haushaltsposition zugeordnet werden."
            );
        }
    }

    private function assertAccountActive(Account $account): void
    {
        if (! $account->is_active) {
            throw new \InvalidArgumentException(
                "Das Sachkonto '{$account->number}' ist inaktiv und kann keiner neuen Haushaltsposition zugeordnet werden."
            );
        }
    }

    private function assertBudgetYearNotApproved(BudgetYear $budgetYear): void
    {
        if ($budgetYear->status === 'approved') {
            throw new \InvalidArgumentException(
                "Das Haushaltsjahr {$budgetYear->year} ist genehmigt (approved) und kann nicht mehr bearbeitet werden."
            );
        }
    }

    /**
     * Assert that the actor has write access for the given cost center and budget year status.
     *
     * - `draft`:       Teamleiter and above (minRole = Teamleiter)
     * - `preliminary`: only Leiter
     */
    private function assertWriteAccess(User $actor, CostCenter $costCenter, BudgetYear $budgetYear): void
    {
        if ($budgetYear->status === 'preliminary') {
            // Only Leiter may write in preliminary status
            if (! $this->authorizationService->isLeiter($actor)) {
                throw new \RuntimeException(
                    "Im Status 'preliminary' dürfen nur Leiter Haushaltspositionen bearbeiten."
                );
            }
            return;
        }

        // draft: Mitarbeiter or higher required (Req 4.4, 9.5)
        if (! $this->authorizationService->canAccessCostCenter($actor, $costCenter, 'Mitarbeiter')) {
            throw new \RuntimeException(
                "Sie haben keine Schreibberechtigung für die Kostenstelle '{$costCenter->number}'."
            );
        }
    }

    /**
     * Assert that the actor may delete positions (Teamleiter or higher).
     * Mitarbeiter are not allowed to delete.
     */
    private function assertDeleteAccess(User $actor, CostCenter $costCenter): void
    {
        if (! $this->authorizationService->canAccessCostCenter($actor, $costCenter, 'Teamleiter')) {
            throw new \RuntimeException(
                "Mitarbeiter dürfen keine Haushaltspositionen löschen."
            );
        }
    }
}
