<?php

namespace App\Modules\HH\Tests\Unit;

use App\Models\User;
use App\Modules\HH\Models\Account;
use App\Modules\HH\Models\BudgetPosition;
use App\Modules\HH\Models\BudgetYear;
use App\Modules\HH\Models\BudgetYearVersion;
use App\Modules\HH\Models\CostCenter;
use App\Modules\HH\Services\MultiYearPlanningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiYearPlanningServiceTest extends TestCase
{
    use RefreshDatabase;

    private MultiYearPlanningService $service;
    private User $actor;
    private CostCenter $costCenter;
    private Account $account;
    private BudgetYear $budgetYear;
    private BudgetYearVersion $activeVersion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new MultiYearPlanningService();
        $this->actor   = User::factory()->create();

        $this->costCenter = CostCenter::create([
            'number'    => '143011',
            'name'      => 'IUK',
            'is_active' => true,
        ]);

        $this->account = Account::create([
            'number'    => '0121002',
            'name'      => 'Software',
            'type'      => 'investiv',
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
            'account_id'             => $this->account->id,
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
    // propagateRecurring()
    // =========================================================================

    /** @test */
    public function propagateRecurring_copies_recurring_position_to_next_year(): void
    {
        $this->makePosition(['is_recurring' => true]);

        $this->service->propagateRecurring($this->budgetYear);

        $nextYear = BudgetYear::where('year', 2026)->first();
        $this->assertNotNull($nextYear);

        $nextVersion = BudgetYearVersion::where('budget_year_id', $nextYear->id)
            ->where('is_active', true)
            ->first();
        $this->assertNotNull($nextVersion);

        $this->assertSame(1, BudgetPosition::where('budget_year_version_id', $nextVersion->id)->count());
    }

    /** @test */
    public function propagateRecurring_does_not_copy_non_recurring_positions(): void
    {
        $this->makePosition(['is_recurring' => false]);

        $this->service->propagateRecurring($this->budgetYear);

        $this->assertDatabaseMissing('hh_budget_years', ['year' => 2026]);
    }

    /** @test */
    public function propagateRecurring_sets_origin_position_id_on_copied_position(): void
    {
        $original = $this->makePosition(['is_recurring' => true]);

        $this->service->propagateRecurring($this->budgetYear);

        $nextYear    = BudgetYear::where('year', 2026)->first();
        $nextVersion = BudgetYearVersion::where('budget_year_id', $nextYear->id)->where('is_active', true)->first();
        $copy        = BudgetPosition::where('budget_year_version_id', $nextVersion->id)->first();

        $this->assertSame($original->id, $copy->origin_position_id);
    }

    /** @test */
    public function propagateRecurring_copies_same_attributes_except_version_id(): void
    {
        $original = $this->makePosition([
            'is_recurring' => true,
            'project_name' => 'Recurring Project',
            'amount'       => 5000.00,
            'priority'     => 'mittel',
            'category'     => 'gesetzlich gebunden',
            'status'       => 'geplant',
            'description'  => 'Some description',
        ]);

        $this->service->propagateRecurring($this->budgetYear);

        $nextYear    = BudgetYear::where('year', 2026)->first();
        $nextVersion = BudgetYearVersion::where('budget_year_id', $nextYear->id)->where('is_active', true)->first();

        $this->assertDatabaseHas('hh_budget_positions', [
            'budget_year_version_id' => $nextVersion->id,
            'project_name'           => 'Recurring Project',
            'amount'                 => '5000.00',
            'priority'               => 'mittel',
            'category'               => 'gesetzlich gebunden',
            'status'                 => 'geplant',
            'description'            => 'Some description',
            'is_recurring'           => true,
            'cost_center_id'         => $this->costCenter->id,
            'account_id'             => $this->account->id,
        ]);
    }

    /** @test */
    public function propagateRecurring_copies_all_recurring_positions(): void
    {
        $this->makePosition(['is_recurring' => true, 'project_name' => 'Recurring A']);
        $this->makePosition(['is_recurring' => true, 'project_name' => 'Recurring B']);
        $this->makePosition(['is_recurring' => false, 'project_name' => 'Non-Recurring']);

        $this->service->propagateRecurring($this->budgetYear);

        $nextYear    = BudgetYear::where('year', 2026)->first();
        $nextVersion = BudgetYearVersion::where('budget_year_id', $nextYear->id)->where('is_active', true)->first();

        $this->assertSame(2, BudgetPosition::where('budget_year_version_id', $nextVersion->id)->count());
    }

    /** @test */
    public function propagateRecurring_reuses_existing_next_year_budget_year(): void
    {
        $existingNextYear = BudgetYear::create([
            'year'       => 2026,
            'status'     => 'draft',
            'created_by' => $this->actor->id,
        ]);
        BudgetYearVersion::create([
            'budget_year_id' => $existingNextYear->id,
            'version_number' => 1,
            'is_active'      => true,
            'created_by'     => $this->actor->id,
            'created_at'     => now(),
        ]);

        $this->makePosition(['is_recurring' => true]);

        $this->service->propagateRecurring($this->budgetYear);

        $this->assertSame(1, BudgetYear::where('year', 2026)->count());
    }

    /** @test */
    public function propagateRecurring_creates_active_version_for_next_year_if_missing(): void
    {
        $existingNextYear = BudgetYear::create([
            'year'       => 2026,
            'status'     => 'draft',
            'created_by' => $this->actor->id,
        ]);
        // No version created

        $this->makePosition(['is_recurring' => true]);

        $this->service->propagateRecurring($this->budgetYear);

        $this->assertDatabaseHas('hh_budget_year_versions', [
            'budget_year_id' => $existingNextYear->id,
            'is_active'      => true,
        ]);
    }

    /** @test */
    public function propagateRecurring_does_nothing_when_no_active_version_exists(): void
    {
        $yearWithoutVersion = BudgetYear::create([
            'year'       => 2030,
            'status'     => 'draft',
            'created_by' => $this->actor->id,
        ]);

        $this->service->propagateRecurring($yearWithoutVersion);

        $this->assertDatabaseMissing('hh_budget_years', ['year' => 2031]);
    }

    /** @test */
    public function propagateRecurring_does_nothing_when_no_recurring_positions(): void
    {
        $this->makePosition(['is_recurring' => false]);

        $this->service->propagateRecurring($this->budgetYear);

        $this->assertDatabaseMissing('hh_budget_years', ['year' => 2026]);
    }

    // =========================================================================
    // generateForDateRange()
    // =========================================================================

    /** @test */
    public function generateForDateRange_creates_positions_for_each_year_in_range(): void
    {
        $pos = $this->makePosition(['start_year' => 2025, 'end_year' => 2027]);

        $this->service->generateForDateRange($pos);

        foreach ([2025, 2026, 2027] as $year) {
            $by = BudgetYear::where('year', $year)->first();
            $this->assertNotNull($by, "BudgetYear for {$year} should exist");

            $version = BudgetYearVersion::where('budget_year_id', $by->id)->where('is_active', true)->first();
            $this->assertNotNull($version);

            $this->assertSame(
                1,
                BudgetPosition::where('budget_year_version_id', $version->id)
                    ->where('origin_position_id', $pos->id)
                    ->count(),
                "Expected 1 position for year {$year}"
            );
        }
    }

    /** @test */
    public function generateForDateRange_creates_exactly_end_minus_start_plus_one_positions(): void
    {
        $pos = $this->makePosition(['start_year' => 2025, 'end_year' => 2029]);

        $this->service->generateForDateRange($pos);

        $count = BudgetPosition::where('origin_position_id', $pos->id)->count();
        $this->assertSame(5, $count); // 2029 - 2025 + 1 = 5
    }

    /** @test */
    public function generateForDateRange_sets_origin_position_id_on_all_copies(): void
    {
        $pos = $this->makePosition(['start_year' => 2025, 'end_year' => 2026]);

        $this->service->generateForDateRange($pos);

        $copies = BudgetPosition::where('origin_position_id', $pos->id)->get();
        $this->assertCount(2, $copies);

        foreach ($copies as $copy) {
            $this->assertSame($pos->id, $copy->origin_position_id);
        }
    }

    /** @test */
    public function generateForDateRange_copies_same_attributes_except_version_id(): void
    {
        $pos = $this->makePosition([
            'start_year'   => 2025,
            'end_year'     => 2025,
            'project_name' => 'Range Project',
            'amount'       => 3000.00,
            'priority'     => 'niedrig',
            'category'     => 'freiwillige Leistung',
            'description'  => 'Range description',
        ]);

        $this->service->generateForDateRange($pos);

        $by      = BudgetYear::where('year', 2025)->first();
        $version = BudgetYearVersion::where('budget_year_id', $by->id)->where('is_active', true)->first();

        $this->assertDatabaseHas('hh_budget_positions', [
            'budget_year_version_id' => $version->id,
            'origin_position_id'     => $pos->id,
            'project_name'           => 'Range Project',
            'amount'                 => '3000.00',
            'priority'               => 'niedrig',
            'category'               => 'freiwillige Leistung',
            'description'            => 'Range description',
        ]);
    }

    /** @test */
    public function generateForDateRange_works_for_single_year_range(): void
    {
        $pos = $this->makePosition(['start_year' => 2025, 'end_year' => 2025]);

        $this->service->generateForDateRange($pos);

        $this->assertSame(1, BudgetPosition::where('origin_position_id', $pos->id)->count());
    }

    /** @test */
    public function generateForDateRange_throws_when_end_year_less_than_start_year(): void
    {
        $pos = $this->makePosition(['start_year' => 2026, 'end_year' => 2024]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Endjahr/');

        $this->service->generateForDateRange($pos);
    }

    /** @test */
    public function generateForDateRange_does_nothing_when_start_year_is_null(): void
    {
        $pos = $this->makePosition(['start_year' => null, 'end_year' => 2027]);

        $this->service->generateForDateRange($pos);

        $this->assertSame(0, BudgetPosition::where('origin_position_id', $pos->id)->count());
    }

    /** @test */
    public function generateForDateRange_does_nothing_when_end_year_is_null(): void
    {
        $pos = $this->makePosition(['start_year' => 2025, 'end_year' => null]);

        $this->service->generateForDateRange($pos);

        $this->assertSame(0, BudgetPosition::where('origin_position_id', $pos->id)->count());
    }

    /** @test */
    public function generateForDateRange_reuses_existing_budget_year(): void
    {
        $existing = BudgetYear::create([
            'year'       => 2026,
            'status'     => 'draft',
            'created_by' => $this->actor->id,
        ]);
        BudgetYearVersion::create([
            'budget_year_id' => $existing->id,
            'version_number' => 1,
            'is_active'      => true,
            'created_by'     => $this->actor->id,
            'created_at'     => now(),
        ]);

        $pos = $this->makePosition(['start_year' => 2026, 'end_year' => 2026]);

        $this->service->generateForDateRange($pos);

        $this->assertSame(1, BudgetYear::where('year', 2026)->count());
    }

    /** @test */
    public function generateForDateRange_creates_active_version_if_budget_year_has_none(): void
    {
        $existing = BudgetYear::create([
            'year'       => 2028,
            'status'     => 'draft',
            'created_by' => $this->actor->id,
        ]);
        // No version

        $pos = $this->makePosition(['start_year' => 2028, 'end_year' => 2028]);

        $this->service->generateForDateRange($pos);

        $this->assertDatabaseHas('hh_budget_year_versions', [
            'budget_year_id' => $existing->id,
            'is_active'      => true,
        ]);
    }
}
