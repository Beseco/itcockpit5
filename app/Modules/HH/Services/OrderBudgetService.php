<?php

namespace App\Modules\HH\Services;

use App\Models\AccountCode as ItAccountCode;
use App\Models\CostCenter as ItCostCenter;
use App\Models\Order;
use App\Modules\HH\Models\Account as HhAccount;
use App\Modules\HH\Models\BudgetPosition;
use App\Modules\HH\Models\BudgetYearVersion;
use App\Modules\HH\Models\CostCenter as HhCostCenter;

class OrderBudgetService
{
    /**
     * Obligo (Summe offener Bestellungen) für ein HH-Jahr, eine Kostenstelle und ein Sachkonto.
     * Verknüpfung: hh_cost_centers.number = it_cost_centers.number
     *              hh_accounts.number      = it_account_codes.code
     */
    public function getObligoForAccount(int $year, HhCostCenter $cc, HhAccount $account): float
    {
        $itCostCenter  = ItCostCenter::where('number', $cc->number)->first();
        $itAccountCode = ItAccountCode::where('code', $account->number)->first();

        if (! $itCostCenter || ! $itAccountCode) {
            return 0.0;
        }

        return (float) Order::where('budget_year', $year)
            ->where('cost_center_id', $itCostCenter->id)
            ->where('account_code_id', $itAccountCode->id)
            ->where('status', '!=', 6)
            ->sum('price_gross');
    }

    /**
     * Obligo für ein HH-Jahr und eine Kostenstelle (alle Sachkonten).
     */
    public function getObligoForCostCenter(int $year, HhCostCenter $cc): float
    {
        $itCostCenter = ItCostCenter::where('number', $cc->number)->first();

        if (! $itCostCenter) {
            return 0.0;
        }

        return (float) Order::where('budget_year', $year)
            ->where('cost_center_id', $itCostCenter->id)
            ->where('status', '!=', 6)
            ->sum('price_gross');
    }

    /**
     * Ausgaben (bezahlte Rechnungen, Status 6) für ein HH-Jahr und ein Sachkonto.
     */
    public function getAusgabenForAccount(int $year, HhCostCenter $cc, HhAccount $account): float
    {
        $itCostCenter  = ItCostCenter::where('number', $cc->number)->first();
        $itAccountCode = ItAccountCode::where('code', $account->number)->first();

        if (! $itCostCenter || ! $itAccountCode) {
            return 0.0;
        }

        return (float) Order::where('budget_year', $year)
            ->where('cost_center_id', $itCostCenter->id)
            ->where('account_code_id', $itAccountCode->id)
            ->where('status', 6)
            ->sum('price_gross');
    }

    /**
     * Ausgaben (bezahlte Rechnungen, Status 6) für ein HH-Jahr und eine Kostenstelle (alle Sachkonten).
     */
    public function getAusgabenForCostCenter(int $year, HhCostCenter $cc): float
    {
        $itCostCenter = ItCostCenter::where('number', $cc->number)->first();

        if (! $itCostCenter) {
            return 0.0;
        }

        return (float) Order::where('budget_year', $year)
            ->where('cost_center_id', $itCostCenter->id)
            ->where('status', 6)
            ->sum('price_gross');
    }

    /**
     * HH-Budget-Aufschlüsselung für die KST-Detailansicht in Orders.
     * Gibt pro Sachkonto: geplantes Budget, Obligo und verfügbares Budget zurück.
     * Verwendet die aktive HH-Version des angegebenen Jahres.
     */
    public function getHhBudgetForOrderCostCenter(int $year, ItCostCenter $itCostCenter): array
    {
        $hhCostCenter = HhCostCenter::where('number', $itCostCenter->number)->first();
        if (! $hhCostCenter) {
            return [];
        }

        $activeVersion = BudgetYearVersion::whereHas('budgetYear', fn ($q) => $q->where('year', $year))
            ->where('is_active', true)
            ->first();

        if (! $activeVersion) {
            return [];
        }

        $positions = BudgetPosition::where('budget_year_version_id', $activeVersion->id)
            ->where('cost_center_id', $hhCostCenter->id)
            ->with('account')
            ->get()
            ->groupBy('account_id');

        $result = [];
        foreach ($positions as $accountId => $group) {
            $hhAccount     = $group->first()->account;
            $itAccountCode = ItAccountCode::where('code', $hhAccount->number)->first();
            $planned       = (float) $group->sum('amount');
            $obligo   = 0.0;
            $ausgaben = 0.0;
            if ($itAccountCode) {
                $obligo = (float) Order::where('budget_year', $year)
                    ->where('cost_center_id', $itCostCenter->id)
                    ->where('account_code_id', $itAccountCode->id)
                    ->where('status', '!=', 6)
                    ->sum('price_gross');
                $ausgaben = (float) Order::where('budget_year', $year)
                    ->where('cost_center_id', $itCostCenter->id)
                    ->where('account_code_id', $itAccountCode->id)
                    ->where('status', 6)
                    ->sum('price_gross');
            }

            $result[] = [
                'account_number' => $hhAccount->number,
                'account_name'   => $hhAccount->name,
                'planned'        => $planned,
                'obligo'         => $obligo,
                'ausgaben'       => $ausgaben,
                'available'      => $planned - $obligo - $ausgaben,
            ];
        }

        usort($result, fn ($a, $b) => $a['account_number'] <=> $b['account_number']);

        return $result;
    }
}
