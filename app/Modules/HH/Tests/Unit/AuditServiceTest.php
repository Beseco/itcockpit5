<?php

namespace App\Modules\HH\Tests\Unit;

use App\Models\User;
use App\Modules\HH\Models\AuditEntry;
use App\Modules\HH\Models\BudgetPosition;
use App\Modules\HH\Models\BudgetYear;
use App\Modules\HH\Models\BudgetYearVersion;
use App\Modules\HH\Models\CostCenter;
use App\Modules\HH\Models\Account;
use App\Modules\HH\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditService $service;
    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AuditService();
        $this->actor   = User::factory()->create();
    }

    // -------------------------------------------------------------------------
    // log()
    // -------------------------------------------------------------------------

    /** @test */
    public function log_creates_an_audit_entry_in_the_database(): void
    {
        $this->service->log($this->actor, 'BudgetYear', 1, 'status', 'draft', 'preliminary');

        $this->assertDatabaseHas('hh_audit_entries', [
            'user_id'     => $this->actor->id,
            'entity_type' => 'BudgetYear',
            'entity_id'   => 1,
            'field'       => 'status',
            'old_value'   => 'draft',
            'new_value'   => 'preliminary',
        ]);
    }

    /** @test */
    public function log_stores_null_old_value_as_null(): void
    {
        $this->service->log($this->actor, 'BudgetYear', 1, 'status', null, 'draft');

        $this->assertDatabaseHas('hh_audit_entries', [
            'entity_type' => 'BudgetYear',
            'entity_id'   => 1,
            'field'       => 'status',
            'old_value'   => null,
            'new_value'   => 'draft',
        ]);
    }

    /** @test */
    public function log_stores_null_new_value_as_null(): void
    {
        $this->service->log($this->actor, 'BudgetPosition', 5, 'amount', '1000.00', null);

        $this->assertDatabaseHas('hh_audit_entries', [
            'entity_type' => 'BudgetPosition',
            'entity_id'   => 5,
            'field'       => 'amount',
            'old_value'   => '1000.00',
            'new_value'   => null,
        ]);
    }

    /** @test */
    public function log_casts_non_string_values_to_string(): void
    {
        $this->service->log($this->actor, 'BudgetPosition', 3, 'amount', 500, 750);

        $this->assertDatabaseHas('hh_audit_entries', [
            'entity_type' => 'BudgetPosition',
            'entity_id'   => 3,
            'field'       => 'amount',
            'old_value'   => '500',
            'new_value'   => '750',
        ]);
    }

    /** @test */
    public function log_creates_multiple_independent_entries(): void
    {
        $this->service->log($this->actor, 'BudgetYear', 1, 'status', 'draft', 'preliminary');
        $this->service->log($this->actor, 'BudgetYear', 1, 'status', 'preliminary', 'approved');

        $this->assertDatabaseCount('hh_audit_entries', 2);
    }

    /** @test */
    public function log_does_not_update_existing_entries(): void
    {
        $this->service->log($this->actor, 'BudgetYear', 1, 'status', 'draft', 'preliminary');

        $entry = AuditEntry::first();
        $originalCreatedAt = $entry->created_at;

        // Log another entry – the first one must remain unchanged
        $this->service->log($this->actor, 'BudgetYear', 1, 'status', 'preliminary', 'approved');

        $entry->refresh();
        $this->assertEquals($originalCreatedAt, $entry->created_at);
        $this->assertSame('preliminary', $entry->new_value);
    }

    /** @test */
    public function audit_entry_update_throws_logic_exception(): void
    {
        $this->service->log($this->actor, 'BudgetYear', 1, 'status', 'draft', 'preliminary');

        $entry = AuditEntry::first();

        $this->expectException(LogicException::class);

        $entry->update(['new_value' => 'tampered']);
    }

    // -------------------------------------------------------------------------
    // getEntries() – basic
    // -------------------------------------------------------------------------

    /** @test */
    public function getEntries_returns_all_entries_when_no_filters_given(): void
    {
        $this->service->log($this->actor, 'BudgetYear', 1, 'status', null, 'draft');
        $this->service->log($this->actor, 'BudgetPosition', 2, 'amount', '100', '200');

        $entries = $this->service->getEntries();

        $this->assertCount(2, $entries);
    }

    /** @test */
    public function getEntries_returns_entries_ordered_by_created_at_desc(): void
    {
        $this->service->log($this->actor, 'BudgetYear', 1, 'status', null, 'draft');
        // Simulate a later entry
        $this->travel(1)->seconds();
        $this->service->log($this->actor, 'BudgetYear', 1, 'status', 'draft', 'preliminary');

        $entries = $this->service->getEntries();

        $this->assertSame('preliminary', $entries->first()->new_value);
        $this->assertSame('draft', $entries->last()->new_value);
    }

    /** @test */
    public function getEntries_returns_empty_collection_when_no_entries_exist(): void
    {
        $entries = $this->service->getEntries();

        $this->assertCount(0, $entries);
    }

    // -------------------------------------------------------------------------
    // getEntries() – from / to filters
    // -------------------------------------------------------------------------

    /** @test */
    public function getEntries_filters_by_from_datetime(): void
    {
        $this->service->log($this->actor, 'BudgetYear', 1, 'status', null, 'draft');

        $this->travel(2)->seconds();
        $cutoff = now()->toDateTimeString();
        $this->travel(1)->seconds();

        $this->service->log($this->actor, 'BudgetYear', 1, 'status', 'draft', 'preliminary');

        $entries = $this->service->getEntries(['from' => $cutoff]);

        $this->assertCount(1, $entries);
        $this->assertSame('preliminary', $entries->first()->new_value);
    }

    /** @test */
    public function getEntries_filters_by_to_datetime(): void
    {
        $this->service->log($this->actor, 'BudgetYear', 1, 'status', null, 'draft');

        $this->travel(2)->seconds();
        $cutoff = now()->toDateTimeString();
        $this->travel(1)->seconds();

        $this->service->log($this->actor, 'BudgetYear', 1, 'status', 'draft', 'preliminary');

        $entries = $this->service->getEntries(['to' => $cutoff]);

        $this->assertCount(1, $entries);
        $this->assertSame('draft', $entries->first()->new_value);
    }

    /** @test */
    public function getEntries_filters_by_from_and_to_combined(): void
    {
        $this->service->log($this->actor, 'BudgetYear', 1, 'status', null, 'draft');

        $this->travel(2)->seconds();
        $from = now()->toDateTimeString();
        $this->travel(1)->seconds();

        $this->service->log($this->actor, 'BudgetYear', 1, 'status', 'draft', 'preliminary');

        $this->travel(2)->seconds();
        $to = now()->toDateTimeString();
        $this->travel(1)->seconds();

        $this->service->log($this->actor, 'BudgetYear', 1, 'status', 'preliminary', 'approved');

        $entries = $this->service->getEntries(['from' => $from, 'to' => $to]);

        $this->assertCount(1, $entries);
        $this->assertSame('preliminary', $entries->first()->new_value);
    }

    // -------------------------------------------------------------------------
    // getEntries() – budget_year_id filter
    // -------------------------------------------------------------------------

    /** @test */
    public function getEntries_filters_by_budget_year_id_returns_direct_budget_year_entries(): void
    {
        $budgetYear = BudgetYear::create(['year' => 2025, 'status' => 'draft', 'created_by' => $this->actor->id]);
        $otherYear  = BudgetYear::create(['year' => 2026, 'status' => 'draft', 'created_by' => $this->actor->id]);

        $this->service->log($this->actor, 'BudgetYear', $budgetYear->id, 'status', null, 'draft');
        $this->service->log($this->actor, 'BudgetYear', $otherYear->id, 'status', null, 'draft');

        $entries = $this->service->getEntries(['budget_year_id' => $budgetYear->id]);

        $this->assertCount(1, $entries);
        $this->assertSame($budgetYear->id, $entries->first()->entity_id);
    }

    /** @test */
    public function getEntries_filters_by_budget_year_id_includes_budget_position_entries(): void
    {
        $budgetYear = BudgetYear::create(['year' => 2025, 'status' => 'draft', 'created_by' => $this->actor->id]);
        $version    = BudgetYearVersion::create([
            'budget_year_id' => $budgetYear->id,
            'version_number' => 1,
            'is_active'      => true,
            'created_by'     => $this->actor->id,
            'created_at'     => now(),
        ]);

        $costCenter = CostCenter::create(['number' => '143011', 'name' => 'IUK', 'is_active' => true]);
        $account    = Account::create(['number' => '0121002', 'name' => 'Software', 'type' => 'investiv', 'is_active' => true]);

        $position = BudgetPosition::create([
            'budget_year_version_id' => $version->id,
            'cost_center_id'         => $costCenter->id,
            'account_id'             => $account->id,
            'project_name'           => 'Test Project',
            'amount'                 => 1000.00,
            'priority'               => 'hoch',
            'category'               => 'Pflichtaufgabe',
            'status'                 => 'geplant',
            'is_recurring'           => false,
            'created_by'             => $this->actor->id,
        ]);

        // Log entry for the position (belongs to this budget year)
        $this->service->log($this->actor, 'BudgetPosition', $position->id, 'amount', '500', '1000');

        // Log entry for an unrelated entity
        $this->service->log($this->actor, 'BudgetYear', 999, 'status', null, 'draft');

        $entries = $this->service->getEntries(['budget_year_id' => $budgetYear->id]);

        $this->assertCount(1, $entries);
        $this->assertSame('BudgetPosition', $entries->first()->entity_type);
        $this->assertSame($position->id, $entries->first()->entity_id);
    }

    /** @test */
    public function getEntries_filters_by_budget_year_id_excludes_positions_from_other_years(): void
    {
        $year1 = BudgetYear::create(['year' => 2025, 'status' => 'draft', 'created_by' => $this->actor->id]);
        $year2 = BudgetYear::create(['year' => 2026, 'status' => 'draft', 'created_by' => $this->actor->id]);

        $version1 = BudgetYearVersion::create([
            'budget_year_id' => $year1->id, 'version_number' => 1,
            'is_active' => true, 'created_by' => $this->actor->id, 'created_at' => now(),
        ]);
        $version2 = BudgetYearVersion::create([
            'budget_year_id' => $year2->id, 'version_number' => 1,
            'is_active' => true, 'created_by' => $this->actor->id, 'created_at' => now(),
        ]);

        $costCenter = CostCenter::create(['number' => '143011', 'name' => 'IUK', 'is_active' => true]);
        $account    = Account::create(['number' => '0121002', 'name' => 'Software', 'type' => 'investiv', 'is_active' => true]);

        $pos1 = BudgetPosition::create([
            'budget_year_version_id' => $version1->id, 'cost_center_id' => $costCenter->id,
            'account_id' => $account->id, 'project_name' => 'P1', 'amount' => 100,
            'priority' => 'hoch', 'category' => 'Pflichtaufgabe', 'status' => 'geplant',
            'is_recurring' => false, 'created_by' => $this->actor->id,
        ]);
        $pos2 = BudgetPosition::create([
            'budget_year_version_id' => $version2->id, 'cost_center_id' => $costCenter->id,
            'account_id' => $account->id, 'project_name' => 'P2', 'amount' => 200,
            'priority' => 'hoch', 'category' => 'Pflichtaufgabe', 'status' => 'geplant',
            'is_recurring' => false, 'created_by' => $this->actor->id,
        ]);

        $this->service->log($this->actor, 'BudgetPosition', $pos1->id, 'amount', '50', '100');
        $this->service->log($this->actor, 'BudgetPosition', $pos2->id, 'amount', '100', '200');

        $entries = $this->service->getEntries(['budget_year_id' => $year1->id]);

        $this->assertCount(1, $entries);
        $this->assertSame($pos1->id, $entries->first()->entity_id);
    }

    // -------------------------------------------------------------------------
    // getEntries() – cost_center_id filter
    // -------------------------------------------------------------------------

    /** @test */
    public function getEntries_filters_by_cost_center_id_returns_direct_cost_center_entries(): void
    {
        $cc1 = CostCenter::create(['number' => '111111', 'name' => 'CC1', 'is_active' => true]);
        $cc2 = CostCenter::create(['number' => '222222', 'name' => 'CC2', 'is_active' => true]);

        $this->service->log($this->actor, 'CostCenter', $cc1->id, 'is_active', '1', '0');
        $this->service->log($this->actor, 'CostCenter', $cc2->id, 'is_active', '1', '0');

        $entries = $this->service->getEntries(['cost_center_id' => $cc1->id]);

        $this->assertCount(1, $entries);
        $this->assertSame($cc1->id, $entries->first()->entity_id);
    }

    /** @test */
    public function getEntries_filters_by_cost_center_id_includes_budget_position_entries(): void
    {
        $cc = CostCenter::create(['number' => '143011', 'name' => 'IUK', 'is_active' => true]);
        $account = Account::create(['number' => '0121002', 'name' => 'Software', 'type' => 'investiv', 'is_active' => true]);

        $budgetYear = BudgetYear::create(['year' => 2025, 'status' => 'draft', 'created_by' => $this->actor->id]);
        $version = BudgetYearVersion::create([
            'budget_year_id' => $budgetYear->id, 'version_number' => 1,
            'is_active' => true, 'created_by' => $this->actor->id, 'created_at' => now(),
        ]);

        $position = BudgetPosition::create([
            'budget_year_version_id' => $version->id, 'cost_center_id' => $cc->id,
            'account_id' => $account->id, 'project_name' => 'Test', 'amount' => 500,
            'priority' => 'mittel', 'category' => 'Pflichtaufgabe', 'status' => 'geplant',
            'is_recurring' => false, 'created_by' => $this->actor->id,
        ]);

        $this->service->log($this->actor, 'BudgetPosition', $position->id, 'amount', '400', '500');
        // Unrelated entry
        $this->service->log($this->actor, 'BudgetYear', 999, 'status', null, 'draft');

        $entries = $this->service->getEntries(['cost_center_id' => $cc->id]);

        $this->assertCount(1, $entries);
        $this->assertSame('BudgetPosition', $entries->first()->entity_type);
    }

    /** @test */
    public function getEntries_filters_by_cost_center_id_excludes_positions_from_other_cost_centers(): void
    {
        $cc1 = CostCenter::create(['number' => '111111', 'name' => 'CC1', 'is_active' => true]);
        $cc2 = CostCenter::create(['number' => '222222', 'name' => 'CC2', 'is_active' => true]);
        $account = Account::create(['number' => '0121002', 'name' => 'Software', 'type' => 'investiv', 'is_active' => true]);

        $budgetYear = BudgetYear::create(['year' => 2025, 'status' => 'draft', 'created_by' => $this->actor->id]);
        $version = BudgetYearVersion::create([
            'budget_year_id' => $budgetYear->id, 'version_number' => 1,
            'is_active' => true, 'created_by' => $this->actor->id, 'created_at' => now(),
        ]);

        $pos1 = BudgetPosition::create([
            'budget_year_version_id' => $version->id, 'cost_center_id' => $cc1->id,
            'account_id' => $account->id, 'project_name' => 'P1', 'amount' => 100,
            'priority' => 'hoch', 'category' => 'Pflichtaufgabe', 'status' => 'geplant',
            'is_recurring' => false, 'created_by' => $this->actor->id,
        ]);
        $pos2 = BudgetPosition::create([
            'budget_year_version_id' => $version->id, 'cost_center_id' => $cc2->id,
            'account_id' => $account->id, 'project_name' => 'P2', 'amount' => 200,
            'priority' => 'hoch', 'category' => 'Pflichtaufgabe', 'status' => 'geplant',
            'is_recurring' => false, 'created_by' => $this->actor->id,
        ]);

        $this->service->log($this->actor, 'BudgetPosition', $pos1->id, 'amount', '50', '100');
        $this->service->log($this->actor, 'BudgetPosition', $pos2->id, 'amount', '100', '200');

        $entries = $this->service->getEntries(['cost_center_id' => $cc1->id]);

        $this->assertCount(1, $entries);
        $this->assertSame($pos1->id, $entries->first()->entity_id);
    }

    // -------------------------------------------------------------------------
    // getEntries() – combined filters
    // -------------------------------------------------------------------------

    /** @test */
    public function getEntries_can_combine_budget_year_id_and_from_filters(): void
    {
        $budgetYear = BudgetYear::create(['year' => 2025, 'status' => 'draft', 'created_by' => $this->actor->id]);

        $this->service->log($this->actor, 'BudgetYear', $budgetYear->id, 'status', null, 'draft');

        $this->travel(2)->seconds();
        $from = now()->toDateTimeString();
        $this->travel(1)->seconds();

        $this->service->log($this->actor, 'BudgetYear', $budgetYear->id, 'status', 'draft', 'preliminary');

        $entries = $this->service->getEntries([
            'budget_year_id' => $budgetYear->id,
            'from'           => $from,
        ]);

        $this->assertCount(1, $entries);
        $this->assertSame('preliminary', $entries->first()->new_value);
    }

    /** @test */
    public function getEntries_returns_empty_when_no_entries_match_budget_year_id(): void
    {
        $this->service->log($this->actor, 'BudgetYear', 1, 'status', null, 'draft');

        $entries = $this->service->getEntries(['budget_year_id' => 9999]);

        $this->assertCount(0, $entries);
    }

    /** @test */
    public function getEntries_returns_empty_when_no_entries_match_cost_center_id(): void
    {
        $this->service->log($this->actor, 'CostCenter', 1, 'is_active', '1', '0');

        $entries = $this->service->getEntries(['cost_center_id' => 9999]);

        $this->assertCount(0, $entries);
    }
}
