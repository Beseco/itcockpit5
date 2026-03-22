<?php

namespace App\Modules\HH\Tests\Unit;

use App\Models\User;
use App\Modules\HH\Models\CostCenter;
use App\Modules\HH\Models\UserCostCenterRole;
use App\Modules\HH\Services\AuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthorizationService $service;
    private CostCenter $costCenter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AuthorizationService();

        $this->costCenter = CostCenter::create([
            'number'    => '143011',
            'name'      => 'IUK',
            'is_active' => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // isLeiter
    // -------------------------------------------------------------------------

    /** @test */
    public function isLeiter_returns_false_when_user_has_no_role_assignments(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->service->isLeiter($user));
    }

    /** @test */
    public function isLeiter_returns_false_when_user_has_non_leiter_role(): void
    {
        $user = User::factory()->create();
        UserCostCenterRole::create([
            'user_id'        => $user->id,
            'cost_center_id' => $this->costCenter->id,
            'role'           => 'Teamleiter',
        ]);

        $this->assertFalse($this->service->isLeiter($user));
    }

    /** @test */
    public function isLeiter_returns_true_when_user_has_leiter_role_on_any_cost_center(): void
    {
        $user = User::factory()->create();
        UserCostCenterRole::create([
            'user_id'        => $user->id,
            'cost_center_id' => $this->costCenter->id,
            'role'           => 'Leiter',
        ]);

        $this->assertTrue($this->service->isLeiter($user));
    }

    /** @test */
    public function isLeiter_returns_true_when_user_has_leiter_on_different_cost_center(): void
    {
        $other = CostCenter::create([
            'number'    => '999999',
            'name'      => 'Other',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        UserCostCenterRole::create([
            'user_id'        => $user->id,
            'cost_center_id' => $other->id,
            'role'           => 'Leiter',
        ]);

        $this->assertTrue($this->service->isLeiter($user));
    }

    // -------------------------------------------------------------------------
    // getUserRoleForCostCenter
    // -------------------------------------------------------------------------

    /** @test */
    public function getUserRoleForCostCenter_returns_null_when_no_assignment_exists(): void
    {
        $user = User::factory()->create();

        $this->assertNull($this->service->getUserRoleForCostCenter($user, $this->costCenter));
    }

    /** @test */
    public function getUserRoleForCostCenter_returns_assigned_role(): void
    {
        $user = User::factory()->create();
        UserCostCenterRole::create([
            'user_id'        => $user->id,
            'cost_center_id' => $this->costCenter->id,
            'role'           => 'Mitarbeiter',
        ]);

        $this->assertSame('Mitarbeiter', $this->service->getUserRoleForCostCenter($user, $this->costCenter));
    }

    /** @test */
    public function getUserRoleForCostCenter_returns_null_for_different_cost_center(): void
    {
        $other = CostCenter::create([
            'number'    => '888888',
            'name'      => 'Other CC',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        UserCostCenterRole::create([
            'user_id'        => $user->id,
            'cost_center_id' => $this->costCenter->id,
            'role'           => 'Teamleiter',
        ]);

        // User has a role on $this->costCenter but NOT on $other
        $this->assertNull($this->service->getUserRoleForCostCenter($user, $other));
    }

    /** @test */
    public function getUserRoleForCostCenter_returns_correct_role_for_each_role_value(): void
    {
        $roles = ['Leiter', 'Teamleiter', 'Mitarbeiter', 'Audit_Zugang'];

        foreach ($roles as $role) {
            $cc   = CostCenter::create(['number' => 'CC' . $role, 'name' => $role, 'is_active' => true]);
            $user = User::factory()->create();
            UserCostCenterRole::create([
                'user_id'        => $user->id,
                'cost_center_id' => $cc->id,
                'role'           => $role,
            ]);

            $this->assertSame($role, $this->service->getUserRoleForCostCenter($user, $cc));
        }
    }

    // -------------------------------------------------------------------------
    // canAccessCostCenter
    // -------------------------------------------------------------------------

    /** @test */
    public function canAccessCostCenter_returns_false_when_user_has_no_assignment(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->service->canAccessCostCenter($user, $this->costCenter, 'Audit_Zugang'));
    }

    /** @test */
    public function canAccessCostCenter_returns_true_when_role_meets_minimum(): void
    {
        $user = User::factory()->create();
        UserCostCenterRole::create([
            'user_id'        => $user->id,
            'cost_center_id' => $this->costCenter->id,
            'role'           => 'Mitarbeiter',
        ]);

        $this->assertTrue($this->service->canAccessCostCenter($user, $this->costCenter, 'Mitarbeiter'));
        $this->assertTrue($this->service->canAccessCostCenter($user, $this->costCenter, 'Audit_Zugang'));
    }

    /** @test */
    public function canAccessCostCenter_returns_false_when_role_is_below_minimum(): void
    {
        $user = User::factory()->create();
        UserCostCenterRole::create([
            'user_id'        => $user->id,
            'cost_center_id' => $this->costCenter->id,
            'role'           => 'Audit_Zugang',
        ]);

        $this->assertFalse($this->service->canAccessCostCenter($user, $this->costCenter, 'Mitarbeiter'));
        $this->assertFalse($this->service->canAccessCostCenter($user, $this->costCenter, 'Teamleiter'));
        $this->assertFalse($this->service->canAccessCostCenter($user, $this->costCenter, 'Leiter'));
    }

    /** @test */
    public function canAccessCostCenter_leiter_has_global_access_to_any_cost_center(): void
    {
        $other = CostCenter::create([
            'number'    => '777777',
            'name'      => 'Unassigned CC',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        // Leiter role assigned to a DIFFERENT cost center
        UserCostCenterRole::create([
            'user_id'        => $user->id,
            'cost_center_id' => $this->costCenter->id,
            'role'           => 'Leiter',
        ]);

        // Should still have access to $other even without explicit assignment
        $this->assertTrue($this->service->canAccessCostCenter($user, $other, 'Leiter'));
        $this->assertTrue($this->service->canAccessCostCenter($user, $other, 'Teamleiter'));
        $this->assertTrue($this->service->canAccessCostCenter($user, $other, 'Mitarbeiter'));
        $this->assertTrue($this->service->canAccessCostCenter($user, $other, 'Audit_Zugang'));
    }

    /** @test */
    public function canAccessCostCenter_teamleiter_cannot_access_with_leiter_minimum(): void
    {
        $user = User::factory()->create();
        UserCostCenterRole::create([
            'user_id'        => $user->id,
            'cost_center_id' => $this->costCenter->id,
            'role'           => 'Teamleiter',
        ]);

        $this->assertFalse($this->service->canAccessCostCenter($user, $this->costCenter, 'Leiter'));
    }

    /** @test */
    public function canAccessCostCenter_teamleiter_can_access_with_mitarbeiter_minimum(): void
    {
        $user = User::factory()->create();
        UserCostCenterRole::create([
            'user_id'        => $user->id,
            'cost_center_id' => $this->costCenter->id,
            'role'           => 'Teamleiter',
        ]);

        $this->assertTrue($this->service->canAccessCostCenter($user, $this->costCenter, 'Mitarbeiter'));
        $this->assertTrue($this->service->canAccessCostCenter($user, $this->costCenter, 'Audit_Zugang'));
        $this->assertTrue($this->service->canAccessCostCenter($user, $this->costCenter, 'Teamleiter'));
    }

    /** @test */
    public function canAccessCostCenter_full_role_hierarchy_is_respected(): void
    {
        $roles = ['Audit_Zugang', 'Mitarbeiter', 'Teamleiter', 'Leiter'];

        foreach ($roles as $assignedRole) {
            $cc   = CostCenter::create(['number' => 'H' . $assignedRole, 'name' => $assignedRole, 'is_active' => true]);
            $user = User::factory()->create();
            UserCostCenterRole::create([
                'user_id'        => $user->id,
                'cost_center_id' => $cc->id,
                'role'           => $assignedRole,
            ]);

            $roleOrder = ['Audit_Zugang' => 1, 'Mitarbeiter' => 2, 'Teamleiter' => 3, 'Leiter' => 4];

            foreach ($roles as $minRole) {
                $expected = $roleOrder[$assignedRole] >= $roleOrder[$minRole];
                $this->assertSame(
                    $expected,
                    $this->service->canAccessCostCenter($user, $cc, $minRole),
                    "Expected canAccessCostCenter with role={$assignedRole}, minRole={$minRole} to be " . ($expected ? 'true' : 'false')
                );
            }
        }
    }
}
