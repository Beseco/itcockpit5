<?php

namespace App\Modules\HH\Services;

use App\Modules\HH\Models\Account;
use App\Modules\HH\Models\BudgetPosition;
use App\Modules\HH\Models\BudgetYear;
use App\Modules\HH\Models\BudgetYearVersion;
use App\Modules\HH\Models\CostCenter;

class BudgetCalculationService
{
    /**
     * Returns the available budget for a given BudgetYear, CostCenter and Account.
     *
     * Available budget = planned budget (sum of position amounts) minus order sum.
     * Since there is no orders table yet, the order sum defaults to 0.
     * A negative return value means the budget has been exceeded.
     * Uses the active version of the BudgetYear.
     *
     * Requirements: 8.1, 8.3
     */
    public function getAvailableBudget(BudgetYear $by, CostCenter $cc, Account $acc, float $orderSum = 0.0): float
    {
        $activeVersion = BudgetYearVersion::where('budget_year_id', $by->id)
            ->where('is_active', true)
            ->first();

        if ($activeVersion === null) {
            return 0.0 - $orderSum;
        }

        $planned = (float) BudgetPosition::where('budget_year_version_id', $activeVersion->id)
            ->where('cost_center_id', $cc->id)
            ->where('account_id', $acc->id)
            ->sum('amount');

        return $planned - $orderSum;
    }

    /**
     * Returns budget totals for a given BudgetYear.
     *
     * Returns an array with keys:
     *   - total:      sum of all position amounts in the active version
     *   - investiv:   sum of amounts where account type = 'investiv'
     *   - konsumtiv:  sum of amounts where account type = 'konsumtiv'
     *
     * Requirements: 7.1, 7.2
     */
    public function getTotals(BudgetYear $by): array
    {
        $activeVersion = BudgetYearVersion::where('budget_year_id', $by->id)
            ->where('is_active', true)
            ->first();

        if ($activeVersion === null) {
            return ['total' => 0.0, 'investiv' => 0.0, 'konsumtiv' => 0.0];
        }

        $positions = BudgetPosition::where('budget_year_version_id', $activeVersion->id)
            ->join('hh_accounts', 'hh_budget_positions.account_id', '=', 'hh_accounts.id')
            ->selectRaw('SUM(hh_budget_positions.amount) as total')
            ->selectRaw("SUM(CASE WHEN hh_accounts.type = 'investiv' THEN hh_budget_positions.amount ELSE 0 END) as investiv")
            ->selectRaw("SUM(CASE WHEN hh_accounts.type = 'konsumtiv' THEN hh_budget_positions.amount ELSE 0 END) as konsumtiv")
            ->first();

        return [
            'total'     => (float) ($positions->total ?? 0),
            'investiv'  => (float) ($positions->investiv ?? 0),
            'konsumtiv' => (float) ($positions->konsumtiv ?? 0),
        ];
    }

    /**
     * Returns the investive share as a percentage of the total budget.
     *
     * Formula: (investiv / total) * 100
     * Returns 0 if total = 0.
     *
     * Requirements: 7.4, 7.5
     */
    public function getTotalsForCostCenter(BudgetYear $by, CostCenter $cc): array
    {
        $activeVersion = BudgetYearVersion::where('budget_year_id', $by->id)
            ->where('is_active', true)
            ->first();

        if ($activeVersion === null) {
            return ['total' => 0.0, 'investiv' => 0.0, 'konsumtiv' => 0.0];
        }

        $positions = BudgetPosition::where('budget_year_version_id', $activeVersion->id)
            ->where('cost_center_id', $cc->id)
            ->join('hh_accounts', 'hh_budget_positions.account_id', '=', 'hh_accounts.id')
            ->selectRaw('SUM(hh_budget_positions.amount) as total')
            ->selectRaw("SUM(CASE WHEN hh_accounts.type = 'investiv' THEN hh_budget_positions.amount ELSE 0 END) as investiv")
            ->selectRaw("SUM(CASE WHEN hh_accounts.type = 'konsumtiv' THEN hh_budget_positions.amount ELSE 0 END) as konsumtiv")
            ->first();

        return [
            'total'     => (float) ($positions->total ?? 0),
            'investiv'  => (float) ($positions->investiv ?? 0),
            'konsumtiv' => (float) ($positions->konsumtiv ?? 0),
        ];
    }

    public function getInvestiveShare(BudgetYear $by): float
    {
        $totals = $this->getTotals($by);

        if ($totals['total'] == 0.0) {
            return 0.0;
        }

        return ($totals['investiv'] / $totals['total']) * 100;
    }
}
