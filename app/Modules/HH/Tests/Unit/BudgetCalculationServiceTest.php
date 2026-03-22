<?php

namespace App\Modules\HH\Tests\Unit;

use App\Models\User;
use App\Modules\HH\Models\Account;
use App\Modules\HH\Models\BudgetPosition;
use App\Modules\HH\Models\BudgetYear;
use App\Modules\HH\Models\BudgetYearVersion;
use App\Modules\HH\Models\CostCenter;
use App\Modules\HH\Services\BudgetCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private BudgetCalculationService $service;
    private User $actor;
    private CostCenter $costCenter;
    private Account $accountInvestiv;
    private Account $accountKonsumtiv;
    private BudgetYear $budgetYear;
    private BudgetYearVersion $activeVersion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new BudgetCalculationService();
        $this->actor   = User::factory()->create();

        $this->costCenter = CostCenter::create([
            'number'    => '143011',
            'name'      => 'IUK',
            'is_active' => true,
        ]);

        $this->accountInvestiv = Account::create([
            'number'    => '0121001',
            'name'      => 'Hardware',
            'type'      => 'investiv',
            'is_active' => true,
        ]);

        $this->accountKonsumtiv = Account::create([
            'number'    => '0121002',
            'name'      => 'Software',
            'type'      => 'konsumtiv',
            'is_active' => true,
        ]);

        $this->budgetYear = BudgetYear::create([
            'year'       => 2025,
            'status'     => 'draft',
            'created_by' => $this->actor->id,
        ]);

        $this->activeVersion = BudgetYearVersion::create([
            'budget_year_id' => $this->budgetYear->id,
            'version_number' => 1,
            'is_active'      => true,
            'created_by'     => $this->actor->id,
            'created_at'     => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makePosition(array $overrides = []): BudgetPosition
    {
        return BudgetPosition::create(array_merge([
            'budget_year_version_id' => $this->activeVersion->id,
            'cost_center_id'         => $this->costCenter->id,
            'account_id'             => $this->accountInvestiv->id,
            'project_name'           => 'Test Project',
            'amount'                 => 1000.00,
            'priority'               => 'hoch',
            'category'               => 'Pflichtaufgabe',
            'status'                 => 'geplant',
            'is_recurring'           => false,
            'created_by'             => $this->actor->id,
        ], $overrides));
    }

    // =========================================================================
    // getAvailableBudget()
    // =========================================================================

    /** @test */
    public function getAvailableBudget_returns_planned_amount_when_no_orders(): void
    {
        $this->makePosition(['amount' => 5000.00]);

        $result = $this->service->getAvailableBudget($this->budgetYear, $this->costCenter, $this->accountInvestiv);

        $this->assertEqualsWithDelta(5000.0, $result, 0.001);
    }

    /** @test */
    public function getAvailableBudget_sums_multiple_positions_for_same_combination(): void
    {
        $this->makePosition(['amount' => 3000.00]);
        $this->makePosition(['amount' => 2000.00]);

        $result = $this->service->getAvailableBudget($this->budgetYear, $this->costCenter, $this->accountInvestiv);

        $this->assertEqualsWithDelta(5000.0, $result, 0.001);
    }

    /** @test */
    public function getAvailableBudget_subtracts_order_sum(): void
    {
        $this->makePosition(['amount' => 5000.00]);

        $result = $this->service->getAvailableBudget($this->budgetYear, $this->costCenter, $this->accountInvestiv, 1500.0);

        $this->assertEqualsWithDelta(3500.0, $result, 0.001);
    }

    /** @test */
    public function getAvailableBudget_returns_negative_when_orders_exceed_budget(): void
    {
        $this->makePosition(['amount' => 1000.00]);

        $result = $this->service->getAvailableBudget($this->budgetYear, $this->costCenter, $this->accountInvestiv, 1500.0);

        $this->assertLessThan(0, $result);
        $this->assertEqualsWithDelta(-500.0, $result, 0.001);
    }

    /** @test */
    public function getAvailableBudget_returns_zero_when_no_positions_and_no_orders(): void
    {
        $result = $this->service->getAvailableBudget($this->budgetYear, $this->costCenter, $this->accountInvestiv);

        $this->assertEqualsWithDelta(0.0, $result, 0.001);
    }

    /** @test */
    public function getAvailableBudget_only_considers_matching_cost_center_and_account(): void
    {
        $otherCostCenter = CostCenter::create([
            'number'    => '999999',
            'name'      => 'Other',
            'is_active' => true,
        ]);

        // Position for a different cost center – should not be counted
        $this->makePosition(['amount' => 9000.00, 'cost_center_id' => $otherCostCenter->id]);
        // Position for the target combination
        $this->makePosition(['amount' => 2000.00]);

        $result = $this->service->getAvailableBudget($this->budgetYear, $this->costCenter, $this->accountInvestiv);

        $this->assertEqualsWithDelta(2000.0, $result, 0.001);
    }

    /** @test */
    public function getAvailableBudget_returns_zero_when_no_active_version_exists(): void
    {
        $yearWithoutVersion = BudgetYear::create([
            'year'       => 2030,
            'status'     => 'draft',
            'created_by' => $this->actor->id,
        ]);

        $result = $this->service->getAvailableBudget($yearWithoutVersion, $this->costCenter, $this->accountInvestiv);

        $this->assertEqualsWithDelta(0.0, $result, 0.001);
    }

    /** @test */
    public function getAvailableBudget_uses_active_version_only(): void
    {
        // Add a position to the active version
        $this->makePosition(['amount' => 4000.00]);

        // Create an inactive version with a different amount
        $inactiveVersion = BudgetYearVersion::create([
            'budget_year_id' => $this->budgetYear->id,
            'version_number' => 2,
            'is_active'      => false,
            'created_by'     => $this->actor->id,
            'created_at'     => now(),
        ]);
        BudgetPosition::create([
            'budget_year_version_id' => $inactiveVersion->id,
            'cost_center_id'         => $this->costCenter->id,
            'account_id'             => $this->accountInvestiv->id,
            'project_name'           => 'Inactive Version Project',
            'amount'                 => 99000.00,
            'priority'               => 'hoch',
            'category'               => 'Pflichtaufgabe',
            'status'                 => 'geplant',
            'is_recurring'           => false,
            'created_by'             => $this->actor->id,
        ]);

        $result = $this->service->getAvailableBudget($this->budgetYear, $this->costCenter, $this->accountInvestiv);

        $this->assertEqualsWithDelta(4000.0, $result, 0.001);
    }

    // =========================================================================
    // getTotals()
    // =========================================================================

    /** @test */
    public function getTotals_returns_correct_keys(): void
    {
        $result = $this->service->getTotals($this->budgetYear);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('investiv', $result);
        $this->assertArrayHasKey('konsumtiv', $result);
    }

    /** @test */
    public function getTotals_returns_zeros_when_no_positions(): void
    {
        $result = $this->service->getTotals($this->budgetYear);

        $this->assertEqualsWithDelta(0.0, $result['total'], 0.001);
        $this->assertEqualsWithDelta(0.0, $result['investiv'], 0.001);
        $this->assertEqualsWithDelta(0.0, $result['konsumtiv'], 0.001);
    }

    /** @test */
    public function getTotals_returns_zeros_when_no_active_version(): void
    {
        $yearWithoutVersion = BudgetYear::create([
            'year'       => 2030,
            'status'     => 'draft',
            'created_by' => $this->actor->id,
        ]);

        $result = $this->service->getTotals($yearWithoutVersion);

        $this->assertEqualsWithDelta(0.0, $result['total'], 0.001);
        $this->assertEqualsWithDelta(0.0, $result['investiv'], 0.001);
        $this->assertEqualsWithDelta(0.0, $result['konsumtiv'], 0.001);
    }

    /** @test */
    public function getTotals_sums_investiv_positions_correctly(): void
    {
        $this->makePosition(['amount' => 3000.00, 'account_id' => $this->accountInvestiv->id]);
        $this->makePosition(['amount' => 2000.00, 'account_id' => $this->accountInvestiv->id]);

        $result = $this->service->getTotals($this->budgetYear);

        $this->assertEqualsWithDelta(5000.0, $result['investiv'], 0.001);
        $this->assertEqualsWithDelta(0.0, $result['konsumtiv'], 0.001);
        $this->assertEqualsWithDelta(5000.0, $result['total'], 0.001);
    }

    /** @test */
    public function getTotals_sums_konsumtiv_positions_correctly(): void
    {
        $this->makePosition(['amount' => 1500.00, 'account_id' => $this->accountKonsumtiv->id]);

        $result = $this->service->getTotals($this->budgetYear);

        $this->assertEqualsWithDelta(1500.0, $result['konsumtiv'], 0.001);
        $this->assertEqualsWithDelta(0.0, $result['investiv'], 0.001);
        $this->assertEqualsWithDelta(1500.0, $result['total'], 0.001);
    }

    /** @test */
    public function getTotals_sums_mixed_positions_correctly(): void
    {
        $this->makePosition(['amount' => 4000.00, 'account_id' => $this->accountInvestiv->id]);
        $this->makePosition(['amount' => 1000.00, 'account_id' => $this->accountKonsumtiv->id]);

        $result = $this->service->getTotals($this->budgetYear);

        $this->assertEqualsWithDelta(4000.0, $result['investiv'], 0.001);
        $this->assertEqualsWithDelta(1000.0, $result['konsumtiv'], 0.001);
        $this->assertEqualsWithDelta(5000.0, $result['total'], 0.001);
    }

    /** @test */
    public function getTotals_total_equals_investiv_plus_konsumtiv(): void
    {
        $this->makePosition(['amount' => 3000.00, 'account_id' => $this->accountInvestiv->id]);
        $this->makePosition(['amount' => 2500.00, 'account_id' => $this->accountKonsumtiv->id]);

        $result = $this->service->getTotals($this->budgetYear);

        $this->assertEqualsWithDelta(
            $result['investiv'] + $result['konsumtiv'],
            $result['total'],
            0.001
        );
    }

    /** @test */
    public function getTotals_uses_active_version_only(): void
    {
        $this->makePosition(['amount' => 1000.00, 'account_id' => $this->accountInvestiv->id]);

        $inactiveVersion = BudgetYearVersion::create([
            'budget_year_id' => $this->budgetYear->id,
            'version_number' => 2,
            'is_active'      => false,
            'created_by'     => $this->actor->id,
            'created_at'     => now(),
        ]);
        BudgetPosition::create([
            'budget_year_version_id' => $inactiveVersion->id,
            'cost_center_id'         => $this->costCenter->id,
            'account_id'             => $this->accountInvestiv->id,
            'project_name'           => 'Inactive',
            'amount'                 => 99000.00,
            'priority'               => 'hoch',
            'category'               => 'Pflichtaufgabe',
            'status'                 => 'geplant',
            'is_recurring'           => false,
            'created_by'             => $this->actor->id,
        ]);

        $result = $this->service->getTotals($this->budgetYear);

        $this->assertEqualsWithDelta(1000.0, $result['total'], 0.001);
    }

    // =========================================================================
    // getInvestiveShare()
    // =========================================================================

    /** @test */
    public function getInvestiveShare_returns_zero_when_total_is_zero(): void
    {
        $result = $this->service->getInvestiveShare($this->budgetYear);

        $this->assertEqualsWithDelta(0.0, $result, 0.001);
    }

    /** @test */
    public function getInvestiveShare_returns_100_when_all_investiv(): void
    {
        $this->makePosition(['amount' => 5000.00, 'account_id' => $this->accountInvestiv->id]);

        $result = $this->service->getInvestiveShare($this->budgetYear);

        $this->assertEqualsWithDelta(100.0, $result, 0.001);
    }

    /** @test */
    public function getInvestiveShare_returns_zero_when_all_konsumtiv(): void
    {
        $this->makePosition(['amount' => 5000.00, 'account_id' => $this->accountKonsumtiv->id]);

        $result = $this->service->getInvestiveShare($this->budgetYear);

        $this->assertEqualsWithDelta(0.0, $result, 0.001);
    }

    /** @test */
    public function getInvestiveShare_calculates_correct_percentage(): void
    {
        $this->makePosition(['amount' => 3000.00, 'account_id' => $this->accountInvestiv->id]);
        $this->makePosition(['amount' => 1000.00, 'account_id' => $this->accountKonsumtiv->id]);

        // investiv = 3000, total = 4000 → 75%
        $result = $this->service->getInvestiveShare($this->budgetYear);

        $this->assertEqualsWithDelta(75.0, $result, 0.001);
    }

    /** @test */
    public function getInvestiveShare_returns_50_for_equal_split(): void
    {
        $this->makePosition(['amount' => 2000.00, 'account_id' => $this->accountInvestiv->id]);
        $this->makePosition(['amount' => 2000.00, 'account_id' => $this->accountKonsumtiv->id]);

        $result = $this->service->getInvestiveShare($this->budgetYear);

        $this->assertEqualsWithDelta(50.0, $result, 0.001);
    }

    /** @test */
    public function getInvestiveShare_returns_zero_when_no_active_version(): void
    {
        $yearWithoutVersion = BudgetYear::create([
            'year'       => 2030,
            'status'     => 'draft',
            'created_by' => $this->actor->id,
        ]);

        $result = $this->service->getInvestiveShare($yearWithoutVersion);

        $this->assertEqualsWithDelta(0.0, $result, 0.001);
    }
}
