<?php

namespace App\Modules\HH\Tests\Unit;

use App\Models\User;
use App\Modules\HH\Models\Account;
use App\Modules\HH\Models\AuditEntry;
use App\Modules\HH\Models\BudgetPosition;
use App\Modules\HH\Models\BudgetYear;
use App\Modules\HH\Models\BudgetYearVersion;
use App\Modules\HH\Models\CostCenter;
use App\Modules\HH\Services\AuditService;
use App\Modules\HH\Services\BudgetYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetYearServiceTest extends TestCase
{
    use RefreshDatabase;

    private BudgetYearService $service;
    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new BudgetYearService(new AuditService());
        $this->actor   = User::factory()->create();
    }

    // -------------------------------------------------------------------------
    // create()
    // -------------------------------------------------------------------------

    /** @test */
    public function create_returns_a_budget_year_with_draft_status(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);

        $this->assertInstanceOf(BudgetYear::class, $budgetYear);
        $this->assertSame(2025, $budgetYear->year);
        $this->assertSame('draft', $budgetYear->status);
        $this->assertSame($this->actor->id, $budgetYear->created_by);
    }

    /** @test */
    public function create_persists_budget_year_to_database(): void
    {
        $this->service->create(2026, $this->actor);

        $this->assertDatabaseHas('hh_budget_years', [
            'year'   => 2026,
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function create_also_creates_first_version_with_version_number_1_and_is_active_true(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);

        $this->assertDatabaseHas('hh_budget_year_versions', [
            'budget_year_id' => $budgetYear->id,
            'version_number' => 1,
            'is_active'      => true,
            'created_by'     => $this->actor->id,
        ]);
    }

    /** @test */
    public function create_throws_runtime_exception_when_year_already_exists(): void
    {
        $this->service->create(2025, $this->actor);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/2025/');

        $this->service->create(2025, $this->actor);
    }

    /** @test */
    public function create_logs_audit_entry_for_new_budget_year(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);

        $this->assertDatabaseHas('hh_audit_entries', [
            'user_id'     => $this->actor->id,
            'entity_type' => 'BudgetYear',
            'entity_id'   => $budgetYear->id,
            'field'       => 'status',
            'old_value'   => null,
            'new_value'   => 'draft',
        ]);
    }

    /** @test */
    public function create_allows_different_years(): void
    {
        $by2024 = $this->service->create(2024, $this->actor);
        $by2025 = $this->service->create(2025, $this->actor);

        $this->assertSame(2024, $by2024->year);
        $this->assertSame(2025, $by2025->year);
        $this->assertDatabaseCount('hh_budget_years', 2);
    }

    // -------------------------------------------------------------------------
    // transitionStatus()
    // -------------------------------------------------------------------------

    /** @test */
    public function transitionStatus_allows_draft_to_preliminary(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);

        $this->service->transitionStatus($budgetYear, 'preliminary', $this->actor);

        $this->assertSame('preliminary', $budgetYear->status);
        $this->assertDatabaseHas('hh_budget_years', ['id' => $budgetYear->id, 'status' => 'preliminary']);
    }

    /** @test */
    public function transitionStatus_allows_preliminary_to_approved(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);
        $this->service->transitionStatus($budgetYear, 'preliminary', $this->actor);

        $this->service->transitionStatus($budgetYear, 'approved', $this->actor);

        $this->assertSame('approved', $budgetYear->status);
        $this->assertDatabaseHas('hh_budget_years', ['id' => $budgetYear->id, 'status' => 'approved']);
    }

    /** @test */
    public function transitionStatus_logs_audit_entry_for_status_change(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);

        $this->service->transitionStatus($budgetYear, 'preliminary', $this->actor);

        $this->assertDatabaseHas('hh_audit_entries', [
            'user_id'     => $this->actor->id,
            'entity_type' => 'BudgetYear',
            'entity_id'   => $budgetYear->id,
            'field'       => 'status',
            'old_value'   => 'draft',
            'new_value'   => 'preliminary',
        ]);
    }

    /** @test */
    public function transitionStatus_logs_audit_entry_for_preliminary_to_approved(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);
        $this->service->transitionStatus($budgetYear, 'preliminary', $this->actor);

        $this->service->transitionStatus($budgetYear, 'approved', $this->actor);

        $this->assertDatabaseHas('hh_audit_entries', [
            'entity_type' => 'BudgetYear',
            'entity_id'   => $budgetYear->id,
            'field'       => 'status',
            'old_value'   => 'preliminary',
            'new_value'   => 'approved',
        ]);
    }

    /** @test */
    public function transitionStatus_throws_for_draft_to_approved(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->transitionStatus($budgetYear, 'approved', $this->actor);
    }

    /** @test */
    public function transitionStatus_throws_for_approved_to_draft(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);
        $this->service->transitionStatus($budgetYear, 'preliminary', $this->actor);
        $this->service->transitionStatus($budgetYear, 'approved', $this->actor);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->transitionStatus($budgetYear, 'draft', $this->actor);
    }

    /** @test */
    public function transitionStatus_throws_for_approved_to_preliminary(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);
        $this->service->transitionStatus($budgetYear, 'preliminary', $this->actor);
        $this->service->transitionStatus($budgetYear, 'approved', $this->actor);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->transitionStatus($budgetYear, 'preliminary', $this->actor);
    }

    /** @test */
    public function transitionStatus_throws_for_preliminary_to_draft(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);
        $this->service->transitionStatus($budgetYear, 'preliminary', $this->actor);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->transitionStatus($budgetYear, 'draft', $this->actor);
    }

    /** @test */
    public function transitionStatus_throws_for_invalid_status_string(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->transitionStatus($budgetYear, 'unknown', $this->actor);
    }

    /** @test */
    public function transitionStatus_exception_message_contains_current_and_target_status(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);

        try {
            $this->service->transitionStatus($budgetYear, 'approved', $this->actor);
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('draft', $e->getMessage());
            $this->assertStringContainsString('approved', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // createVersion()
    // -------------------------------------------------------------------------

    /** @test */
    public function createVersion_returns_new_budget_year_version(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);

        $newVersion = $this->service->createVersion($budgetYear, $this->actor);

        $this->assertInstanceOf(BudgetYearVersion::class, $newVersion);
        $this->assertSame($budgetYear->id, $newVersion->budget_year_id);
        $this->assertTrue($newVersion->is_active);
    }

    /** @test */
    public function createVersion_increments_version_number(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);

        $v2 = $this->service->createVersion($budgetYear, $this->actor);
        $v3 = $this->service->createVersion($budgetYear, $this->actor);

        $this->assertSame(2, $v2->version_number);
        $this->assertSame(3, $v3->version_number);
    }

    /** @test */
    public function createVersion_sets_previous_version_to_inactive(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);
        $firstVersion = BudgetYearVersion::where('budget_year_id', $budgetYear->id)
            ->where('is_active', true)
            ->first();

        $this->service->createVersion($budgetYear, $this->actor);

        $firstVersion->refresh();
        $this->assertFalse($firstVersion->is_active);
    }

    /** @test */
    public function createVersion_only_one_version_is_active_after_creation(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);

        $this->service->createVersion($budgetYear, $this->actor);
        $this->service->createVersion($budgetYear, $this->actor);

        $activeCount = BudgetYearVersion::where('budget_year_id', $budgetYear->id)
            ->where('is_active', true)
            ->count();

        $this->assertSame(1, $activeCount);
    }

    /** @test */
    public function createVersion_copies_all_positions_from_active_version(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);
        $activeVersion = BudgetYearVersion::where('budget_year_id', $budgetYear->id)
            ->where('is_active', true)
            ->first();

        $costCenter = CostCenter::create(['number' => '143011', 'name' => 'IUK', 'is_active' => true]);
        $account    = Account::create(['number' => '0121002', 'name' => 'Software', 'type' => 'investiv', 'is_active' => true]);

        BudgetPosition::create([
            'budget_year_version_id' => $activeVersion->id,
            'cost_center_id'         => $costCenter->id,
            'account_id'             => $account->id,
            'project_name'           => 'Projekt A',
            'amount'                 => 5000.00,
            'priority'               => 'hoch',
            'category'               => 'Pflichtaufgabe',
            'status'                 => 'geplant',
            'is_recurring'           => false,
            'created_by'             => $this->actor->id,
        ]);
        BudgetPosition::create([
            'budget_year_version_id' => $activeVersion->id,
            'cost_center_id'         => $costCenter->id,
            'account_id'             => $account->id,
            'project_name'           => 'Projekt B',
            'amount'                 => 3000.00,
            'priority'               => 'mittel',
            'category'               => 'freiwillige Leistung',
            'status'                 => 'geplant',
            'is_recurring'           => false,
            'created_by'             => $this->actor->id,
        ]);

        $newVersion = $this->service->createVersion($budgetYear, $this->actor);

        $this->assertSame(2, BudgetPosition::where('budget_year_version_id', $newVersion->id)->count());
    }

    /** @test */
    public function createVersion_copied_positions_have_same_attributes(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);
        $activeVersion = BudgetYearVersion::where('budget_year_id', $budgetYear->id)
            ->where('is_active', true)
            ->first();

        $costCenter = CostCenter::create(['number' => '143011', 'name' => 'IUK', 'is_active' => true]);
        $account    = Account::create(['number' => '0121002', 'name' => 'Software', 'type' => 'investiv', 'is_active' => true]);

        BudgetPosition::create([
            'budget_year_version_id' => $activeVersion->id,
            'cost_center_id'         => $costCenter->id,
            'account_id'             => $account->id,
            'project_name'           => 'Projekt A',
            'description'            => 'Beschreibung',
            'amount'                 => 7500.00,
            'priority'               => 'niedrig',
            'category'               => 'gesetzlich gebunden',
            'status'                 => 'angepasst',
            'is_recurring'           => true,
            'created_by'             => $this->actor->id,
        ]);

        $newVersion = $this->service->createVersion($budgetYear, $this->actor);

        $this->assertDatabaseHas('hh_budget_positions', [
            'budget_year_version_id' => $newVersion->id,
            'project_name'           => 'Projekt A',
            'description'            => 'Beschreibung',
            'amount'                 => '7500.00',
            'priority'               => 'niedrig',
            'category'               => 'gesetzlich gebunden',
            'status'                 => 'angepasst',
            'is_recurring'           => true,
        ]);
    }

    /** @test */
    public function createVersion_with_no_positions_creates_empty_new_version(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);

        $newVersion = $this->service->createVersion($budgetYear, $this->actor);

        $this->assertSame(0, BudgetPosition::where('budget_year_version_id', $newVersion->id)->count());
    }

    /** @test */
    public function createVersion_logs_audit_entry(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);

        $newVersion = $this->service->createVersion($budgetYear, $this->actor);

        $this->assertDatabaseHas('hh_audit_entries', [
            'user_id'     => $this->actor->id,
            'entity_type' => 'BudgetYearVersion',
            'entity_id'   => $newVersion->id,
            'field'       => 'version_number',
            'old_value'   => '1',
            'new_value'   => '2',
        ]);
    }

    /** @test */
    public function createVersion_persists_new_version_to_database(): void
    {
        $budgetYear = $this->service->create(2025, $this->actor);

        $newVersion = $this->service->createVersion($budgetYear, $this->actor);

        $this->assertDatabaseHas('hh_budget_year_versions', [
            'id'             => $newVersion->id,
            'budget_year_id' => $budgetYear->id,
            'version_number' => 2,
            'is_active'      => true,
            'created_by'     => $this->actor->id,
        ]);
    }
}
