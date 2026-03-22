<?php

namespace App\Modules\HH\Tests\Unit;

use App\Models\User;
use App\Modules\HH\Models\Account;
use App\Modules\HH\Models\BudgetPosition;
use App\Modules\HH\Models\BudgetYear;
use App\Modules\HH\Models\BudgetYearVersion;
use App\Modules\HH\Models\CostCenter;
use App\Modules\HH\Models\UserCostCenterRole;
use App\Modules\HH\Services\AuditService;
use App\Modules\HH\Services\AuthorizationService;
use App\Modules\HH\Services\PositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PositionService $service;
    private User $leiter;
    private User $teamleiter;
    private User $mitarbeiter;
    private User $auditUser;
    private CostCenter $costCenter;
    private Account $account;
    private BudgetYear $draftYear;
    private BudgetYear $preliminaryYear;
    private BudgetYear $approvedYear;
    private BudgetYearVersion $draftVersion;
    private BudgetYearVersion $preliminaryVersion;
    private BudgetYearVersion $approvedVersion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PositionService(
            new AuthorizationService(),
            new AuditService(),
        );

        // Users
        $this->leiter      = User::factory()->create();
        $this->teamleiter  = User::factory()->create();
        $this->mitarbeiter = User::factory()->create();
        $this->auditUser   = User::factory()->create();

        // Cost center and account (both active by default)
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

        // Assign roles
        UserCostCenterRole::create(['user_id' => $this->leiter->id,      'cost_center_id' => $this->costCenter->id, 'role' => 'Leiter']);
        UserCostCenterRole::create(['user_id' => $this->teamleiter->id,  'cost_center_id' => $this->costCenter->id, 'role' => 'Teamleiter']);
        UserCostCenterRole::create(['user_id' => $this->mitarbeiter->id, 'cost_center_id' => $this->costCenter->id, 'role' => 'Mitarbeiter']);
        UserCostCenterRole::create(['user_id' => $this->auditUser->id,   'cost_center_id' => $this->costCenter->id, 'role' => 'Audit_Zugang']);

        // Budget years
        $this->draftYear = BudgetYear::create(['year' => 2025, 'status' => 'draft',       'created_by' => $this->leiter->id]);
        $this->preliminaryYear = BudgetYear::create(['year' => 2026, 'status' => 'preliminary', 'created_by' => $this->leiter->id]);
        $this->approvedYear    = BudgetYear::create(['year' => 2027, 'status' => 'approved',    'created_by' => $this->leiter->id]);

        // Versions
        $this->draftVersion = BudgetYearVersion::create([
            'budget_year_id' => $this->draftYear->id,
            'version_number' => 1,
            'is_active'      => true,
            'created_by'     => $this->leiter->id,
            'created_at'     => now(),
        ]);
        $this->preliminaryVersion = BudgetYearVersion::create([
            'budget_year_id' => $this->preliminaryYear->id,
            'version_number' => 1,
            'is_active'      => true,
            'created_by'     => $this->leiter->id,
            'created_at'     => now(),
        ]);
        $this->approvedVersion = BudgetYearVersion::create([
            'budget_year_id' => $this->approvedYear->id,
            'version_number' => 1,
            'is_active'      => true,
            'created_by'     => $this->leiter->id,
            'created_at'     => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function baseData(int $versionId): array
    {
        return [
            'budget_year_version_id' => $versionId,
            'cost_center_id'         => $this->costCenter->id,
            'account_id'             => $this->account->id,
            'project_name'           => 'Test Project',
            'amount'                 => 1000.00,
            'priority'               => 'hoch',
            'category'               => 'Pflichtaufgabe',
            'status'                 => 'geplant',
            'is_recurring'           => false,
        ];
    }

    private function createPosition(BudgetYearVersion $version, User $actor = null): BudgetPosition
    {
        return $this->service->create($this->baseData($version->id), $actor ?? $this->teamleiter);
    }

    // =========================================================================
    // create()
    // =========================================================================

    /** @test */
    public function create_returns_budget_position_for_teamleiter_in_draft_year(): void
    {
        $pos = $this->createPosition($this->draftVersion, $this->teamleiter);

        $this->assertInstanceOf(BudgetPosition::class, $pos);
        $this->assertSame($this->costCenter->id, $pos->cost_center_id);
        $this->assertSame($this->account->id, $pos->account_id);
        $this->assertSame('Test Project', $pos->project_name);
    }

    /** @test */
    public function create_persists_position_to_database(): void
    {
        $this->createPosition($this->draftVersion, $this->teamleiter);

        $this->assertDatabaseHas('hh_budget_positions', [
            'cost_center_id' => $this->costCenter->id,
            'account_id'     => $this->account->id,
            'project_name'   => 'Test Project',
        ]);
    }

    /** @test */
    public function create_sets_created_by_to_actor_id(): void
    {
        $pos = $this->createPosition($this->draftVersion, $this->teamleiter);

        $this->assertSame($this->teamleiter->id, $pos->created_by);
    }

    /** @test */
    public function create_logs_audit_entry_on_creation(): void
    {
        $pos = $this->createPosition($this->draftVersion, $this->teamleiter);

        $this->assertDatabaseHas('hh_audit_entries', [
            'user_id'     => $this->teamleiter->id,
            'entity_type' => 'BudgetPosition',
            'entity_id'   => $pos->id,
            'field'       => 'status',
            'old_value'   => null,
            'new_value'   => 'geplant',
        ]);
    }

    /** @test */
    public function create_allows_mitarbeiter_in_draft_year(): void
    {
        $pos = $this->createPosition($this->draftVersion, $this->mitarbeiter);

        $this->assertInstanceOf(BudgetPosition::class, $pos);
    }

    /** @test */
    public function create_allows_leiter_in_draft_year(): void
    {
        $pos = $this->createPosition($this->draftVersion, $this->leiter);

        $this->assertInstanceOf(BudgetPosition::class, $pos);
    }

    /** @test */
    public function create_allows_leiter_in_preliminary_year(): void
    {
        $pos = $this->createPosition($this->preliminaryVersion, $this->leiter);

        $this->assertInstanceOf(BudgetPosition::class, $pos);
    }

    // -------------------------------------------------------------------------
    // create() – authorization failures
    // -------------------------------------------------------------------------

    /** @test */
    public function create_throws_for_audit_zugang_user_in_draft_year(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->createPosition($this->draftVersion, $this->auditUser);
    }

    /** @test */
    public function create_throws_for_user_without_any_role_in_draft_year(): void
    {
        $noRoleUser = User::factory()->create();

        $this->expectException(\RuntimeException::class);

        $this->createPosition($this->draftVersion, $noRoleUser);
    }

    /** @test */
    public function create_throws_for_teamleiter_in_preliminary_year(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->createPosition($this->preliminaryVersion, $this->teamleiter);
    }

    /** @test */
    public function create_throws_for_mitarbeiter_in_preliminary_year(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->createPosition($this->preliminaryVersion, $this->mitarbeiter);
    }

    // -------------------------------------------------------------------------
    // create() – approved year
    // -------------------------------------------------------------------------

    /** @test */
    public function create_throws_for_approved_year_regardless_of_role(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createPosition($this->approvedVersion, $this->leiter);
    }

    /** @test */
    public function create_exception_message_mentions_approved_for_approved_year(): void
    {
        try {
            $this->createPosition($this->approvedVersion, $this->leiter);
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('approved', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // create() – inactive cost center / account
    // -------------------------------------------------------------------------

    /** @test */
    public function create_throws_for_inactive_cost_center(): void
    {
        $inactiveCc = CostCenter::create(['number' => '999001', 'name' => 'Inactive CC', 'is_active' => false]);
        UserCostCenterRole::create(['user_id' => $this->teamleiter->id, 'cost_center_id' => $inactiveCc->id, 'role' => 'Teamleiter']);

        $data = $this->baseData($this->draftVersion->id);
        $data['cost_center_id'] = $inactiveCc->id;

        $this->expectException(\InvalidArgumentException::class);

        $this->service->create($data, $this->teamleiter);
    }

    /** @test */
    public function create_throws_for_inactive_account(): void
    {
        $inactiveAcc = Account::create(['number' => '9990001', 'name' => 'Inactive Acc', 'type' => 'konsumtiv', 'is_active' => false]);

        $data = $this->baseData($this->draftVersion->id);
        $data['account_id'] = $inactiveAcc->id;

        $this->expectException(\InvalidArgumentException::class);

        $this->service->create($data, $this->teamleiter);
    }

    // =========================================================================
    // update()
    // =========================================================================

    /** @test */
    public function update_returns_updated_position(): void
    {
        $pos = $this->createPosition($this->draftVersion, $this->teamleiter);

        $updated = $this->service->update($pos, ['project_name' => 'Updated Name'], $this->teamleiter);

        $this->assertSame('Updated Name', $updated->project_name);
    }

    /** @test */
    public function update_persists_changes_to_database(): void
    {
        $pos = $this->createPosition($this->draftVersion, $this->teamleiter);

        $this->service->update($pos, ['project_name' => 'Persisted Name'], $this->teamleiter);

        $this->assertDatabaseHas('hh_budget_positions', [
            'id'           => $pos->id,
            'project_name' => 'Persisted Name',
        ]);
    }

    /** @test */
    public function update_logs_audit_entry_when_amount_changes(): void
    {
        $pos = $this->createPosition($this->draftVersion, $this->teamleiter);

        $this->service->update($pos, ['amount' => 2000.00], $this->teamleiter);

        $this->assertDatabaseHas('hh_audit_entries', [
            'user_id'     => $this->teamleiter->id,
            'entity_type' => 'BudgetPosition',
            'entity_id'   => $pos->id,
            'field'       => 'amount',
            'new_value'   => '2000.00',
        ]);
    }

    /** @test */
    public function update_does_not_log_audit_entry_when_amount_does_not_change(): void
    {
        $pos = $this->createPosition($this->draftVersion, $this->teamleiter);

        // Clear audit entries from create
        \App\Modules\HH\Models\AuditEntry::query()->delete();

        $this->service->update($pos, ['project_name' => 'No Amount Change'], $this->teamleiter);

        $this->assertDatabaseMissing('hh_audit_entries', [
            'entity_type' => 'BudgetPosition',
            'entity_id'   => $pos->id,
            'field'       => 'amount',
        ]);
    }

    /** @test */
    public function update_logs_old_and_new_amount_values(): void
    {
        $pos = $this->createPosition($this->draftVersion, $this->teamleiter);
        $oldAmount = $pos->amount;

        $this->service->update($pos, ['amount' => 5000.00], $this->teamleiter);

        $this->assertDatabaseHas('hh_audit_entries', [
            'entity_type' => 'BudgetPosition',
            'entity_id'   => $pos->id,
            'field'       => 'amount',
            'old_value'   => (string) $oldAmount,
            'new_value'   => '5000.00',
        ]);
    }

    /** @test */
    public function update_allows_leiter_in_preliminary_year(): void
    {
        $pos = $this->createPosition($this->preliminaryVersion, $this->leiter);

        $updated = $this->service->update($pos, ['project_name' => 'Leiter Update'], $this->leiter);

        $this->assertSame('Leiter Update', $updated->project_name);
    }

    // -------------------------------------------------------------------------
    // update() – authorization failures
    // -------------------------------------------------------------------------

    /** @test */
    public function update_throws_for_teamleiter_in_preliminary_year(): void
    {
        $pos = $this->createPosition($this->preliminaryVersion, $this->leiter);

        $this->expectException(\RuntimeException::class);

        $this->service->update($pos, ['project_name' => 'Should Fail'], $this->teamleiter);
    }

    /** @test */
    public function update_throws_for_mitarbeiter_in_preliminary_year(): void
    {
        $pos = $this->createPosition($this->preliminaryVersion, $this->leiter);

        $this->expectException(\RuntimeException::class);

        $this->service->update($pos, ['project_name' => 'Should Fail'], $this->mitarbeiter);
    }

    /** @test */
    public function update_throws_for_user_without_write_access_in_draft_year(): void
    {
        $pos = $this->createPosition($this->draftVersion, $this->teamleiter);

        $noRoleUser = User::factory()->create();

        $this->expectException(\RuntimeException::class);

        $this->service->update($pos, ['project_name' => 'Should Fail'], $noRoleUser);
    }

    // -------------------------------------------------------------------------
    // update() – approved year
    // -------------------------------------------------------------------------

    /** @test */
    public function update_throws_for_approved_year(): void
    {
        // Manually create a position in the approved version (bypassing service)
        $pos = BudgetPosition::create(array_merge($this->baseData($this->approvedVersion->id), [
            'created_by' => $this->leiter->id,
        ]));

        $this->expectException(\InvalidArgumentException::class);

        $this->service->update($pos, ['project_name' => 'Should Fail'], $this->leiter);
    }

    // =========================================================================
    // delete()
    // =========================================================================

    /** @test */
    public function delete_removes_position_from_database(): void
    {
        $pos = $this->createPosition($this->draftVersion, $this->teamleiter);
        $posId = $pos->id;

        $this->service->delete($pos, $this->teamleiter);

        $this->assertDatabaseMissing('hh_budget_positions', ['id' => $posId]);
    }

    /** @test */
    public function delete_logs_audit_entry(): void
    {
        $pos = $this->createPosition($this->draftVersion, $this->teamleiter);
        $posId = $pos->id;

        $this->service->delete($pos, $this->teamleiter);

        $this->assertDatabaseHas('hh_audit_entries', [
            'user_id'     => $this->teamleiter->id,
            'entity_type' => 'BudgetPosition',
            'entity_id'   => $posId,
            'field'       => 'status',
            'new_value'   => null,
        ]);
    }

    /** @test */
    public function delete_allows_leiter_to_delete(): void
    {
        $pos = $this->createPosition($this->draftVersion, $this->leiter);
        $posId = $pos->id;

        $this->service->delete($pos, $this->leiter);

        $this->assertDatabaseMissing('hh_budget_positions', ['id' => $posId]);
    }

    /** @test */
    public function delete_allows_teamleiter_to_delete(): void
    {
        $pos = $this->createPosition($this->draftVersion, $this->teamleiter);
        $posId = $pos->id;

        $this->service->delete($pos, $this->teamleiter);

        $this->assertDatabaseMissing('hh_budget_positions', ['id' => $posId]);
    }

    // -------------------------------------------------------------------------
    // delete() – authorization failures
    // -------------------------------------------------------------------------

    /** @test */
    public function delete_throws_for_mitarbeiter(): void
    {
        $pos = $this->createPosition($this->draftVersion, $this->teamleiter);

        $this->expectException(\RuntimeException::class);

        $this->service->delete($pos, $this->mitarbeiter);
    }

    /** @test */
    public function delete_throws_for_audit_zugang_user(): void
    {
        $pos = $this->createPosition($this->draftVersion, $this->teamleiter);

        $this->expectException(\RuntimeException::class);

        $this->service->delete($pos, $this->auditUser);
    }

    /** @test */
    public function delete_throws_for_user_without_any_role(): void
    {
        $pos = $this->createPosition($this->draftVersion, $this->teamleiter);
        $noRoleUser = User::factory()->create();

        $this->expectException(\RuntimeException::class);

        $this->service->delete($pos, $noRoleUser);
    }

    // -------------------------------------------------------------------------
    // delete() – approved year
    // -------------------------------------------------------------------------

    /** @test */
    public function delete_throws_for_approved_year(): void
    {
        $pos = BudgetPosition::create(array_merge($this->baseData($this->approvedVersion->id), [
            'created_by' => $this->leiter->id,
        ]));

        $this->expectException(\InvalidArgumentException::class);

        $this->service->delete($pos, $this->leiter);
    }

    /** @test */
    public function delete_exception_message_mentions_approved_for_approved_year(): void
    {
        $pos = BudgetPosition::create(array_merge($this->baseData($this->approvedVersion->id), [
            'created_by' => $this->leiter->id,
        ]));

        try {
            $this->service->delete($pos, $this->leiter);
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('approved', $e->getMessage());
        }
    }
}
