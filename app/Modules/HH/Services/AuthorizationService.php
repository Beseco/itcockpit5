<?php

namespace App\Modules\HH\Services;

use App\Models\User;
use App\Modules\HH\Models\CostCenter;
use App\Modules\HH\Models\UserCostCenterRole;

class AuthorizationService
{
    /**
     * Role hierarchy: higher value = more permissions.
     */
    private const ROLE_ORDER = [
        'Audit_Zugang' => 1,
        'Mitarbeiter'  => 2,
        'Teamleiter'   => 3,
        'Leiter'       => 4,
    ];

    /**
     * Check whether a user meets the minimum required role for a given cost center.
     *
     * A user with the role "Leiter" (on any cost center) has global full access
     * and therefore always passes this check.
     *
     * @param User       $user    The user to check.
     * @param CostCenter $cc      The cost center to check access for.
     * @param string     $minRole The minimum role required (e.g. 'Mitarbeiter').
     */
    public function canAccessCostCenter(User $user, CostCenter $cc, string $minRole): bool
    {
        // Leiter has global full access regardless of cost center assignment
        if ($this->isLeiter($user)) {
            return true;
        }

        $role = $this->getUserRoleForCostCenter($user, $cc);

        if ($role === null) {
            return false;
        }

        $userLevel = self::ROLE_ORDER[$role] ?? 0;
        $minLevel  = self::ROLE_ORDER[$minRole] ?? 0;

        return $userLevel >= $minLevel;
    }

    /**
     * Return the role a user has for a specific cost center, or null if none assigned.
     *
     * @param User       $user The user to look up.
     * @param CostCenter $cc   The cost center to look up.
     */
    public function getUserRoleForCostCenter(User $user, CostCenter $cc): ?string
    {
        $assignment = UserCostCenterRole::where('user_id', $user->id)
            ->where('cost_center_id', $cc->id)
            ->first();

        return $assignment?->role;
    }

    /**
     * Check whether a user has write access to HH:
     * - Superadministrator/Admin (global roles), OR
     * - Spatie permission "hh.edit" (z.B. Gruppenleiter), OR
     * - HH-interne Kostenstellen-Rolle "Leiter"
     */
    public function isLeiter(User $user): bool
    {
        if ($user->hasRole(['Superadministrator', 'Admin'])) {
            return true;
        }

        if ($user->hasPermissionTo('hh.edit')) {
            return true;
        }

        return UserCostCenterRole::where('user_id', $user->id)
            ->where('role', 'Leiter')
            ->exists();
    }
}
