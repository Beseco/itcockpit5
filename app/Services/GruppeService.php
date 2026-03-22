<?php

namespace App\Services;

use App\Models\Gruppe;
use App\Models\User;

class GruppeService
{
    /**
     * Synchronisiert die Rollen aller Mitglieder einer Gruppe.
     * Wenn die Rollen der Gruppe geändert werden, erhalten alle Mitglieder
     * die kombinierten Rollen aus allen ihren Gruppen + direkte Rollen.
     */
    public function syncMemberRoles(Gruppe $gruppe): void
    {
        foreach ($gruppe->users as $user) {
            $this->syncUserRoles($user);
        }
    }

    /**
     * Synchronisiert die Rollen eines Users basierend auf seinen Gruppen.
     * Direkte Rollen (die nicht aus einer Gruppe stammen) werden beibehalten.
     */
    public function syncUserRoles(User $user): void
    {
        // Alle Rollen aus allen Gruppen des Users sammeln
        $user->load('gruppen.roles');
        $groupRoleIds = $user->gruppen->flatMap(fn($g) => $g->roles->pluck('id'))->unique()->values();

        // Direkte Rollen des Users (ohne Gruppen-Rollen)
        // Wir behalten alle Rollen; Gruppen-Rollen kommen on-top
        $currentRoleIds = $user->roles->pluck('id');
        $allRoleIds = $currentRoleIds->merge($groupRoleIds)->unique()->values();

        $user->syncRoles($allRoleIds->toArray());
    }

    /**
     * Wenn ein User zu einer Gruppe hinzugefügt wird: Gruppen-Rollen vererben.
     */
    public function onUserAdded(User $user, Gruppe $gruppe): void
    {
        $this->syncUserRoles($user);
    }

    /**
     * Wenn ein User aus einer Gruppe entfernt wird: Rollen neu berechnen.
     */
    public function onUserRemoved(User $user): void
    {
        $this->syncUserRoles($user);
    }
}
