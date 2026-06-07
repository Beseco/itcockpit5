<?php

namespace App\Modules\AdUsers\Http\Controllers;

use App\Models\AuditLog;
use App\Modules\AdUsers\Models\AdUser;
use App\Modules\AdUsers\Models\AdUserGroupChangeLog;
use App\Modules\Onboarding\Services\AdProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AdUserManageController extends Controller
{
    public function __construct(private AdProvisioningService $provisioner) {}

    /** AJAX: Benutzer zu AD-Gruppe hinzufügen */
    public function addGroup(AdUser $user, Request $request): JsonResponse
    {
        $request->validate([
            'group_dn'   => ['required', 'string', 'max:1000'],
            'group_name' => ['required', 'string', 'max:255'],
        ]);

        if (!$user->distinguished_name) {
            return response()->json(['error' => 'Kein Distinguished Name für diesen Benutzer vorhanden.'], 422);
        }

        try {
            $this->provisioner->addUserToGroup($user->distinguished_name, $request->input('group_dn'));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $log = AdUserGroupChangeLog::create([
            'samaccountname'       => $user->samaccountname,
            'user_dn'              => $user->distinguished_name,
            'group_dn'             => $request->input('group_dn'),
            'group_name'           => $request->input('group_name'),
            'action'               => 'add',
            'performed_by_user_id' => auth()->id(),
        ]);

        $this->updateRawDataMemberOf($user, $request->input('group_dn'), 'add');

        AuditLog::create([
            'user_id' => auth()->id(),
            'module'  => 'adusers',
            'action'  => 'group_added',
            'payload' => [
                'samaccountname' => $user->samaccountname,
                'group_dn'       => $request->input('group_dn'),
                'group_name'     => $request->input('group_name'),
            ],
        ]);

        return response()->json([
            'success' => true,
            'log'     => $this->formatLog($log->load('performedBy')),
        ]);
    }

    /** AJAX: Benutzer aus AD-Gruppe entfernen */
    public function removeGroup(AdUser $user, Request $request): JsonResponse
    {
        $request->validate([
            'group_dn'   => ['required', 'string', 'max:1000'],
            'group_name' => ['required', 'string', 'max:255'],
        ]);

        if (!$user->distinguished_name) {
            return response()->json(['error' => 'Kein Distinguished Name für diesen Benutzer vorhanden.'], 422);
        }

        try {
            $this->provisioner->removeUserFromGroup($user->distinguished_name, $request->input('group_dn'));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $log = AdUserGroupChangeLog::create([
            'samaccountname'       => $user->samaccountname,
            'user_dn'              => $user->distinguished_name,
            'group_dn'             => $request->input('group_dn'),
            'group_name'           => $request->input('group_name'),
            'action'               => 'remove',
            'performed_by_user_id' => auth()->id(),
        ]);

        $this->updateRawDataMemberOf($user, $request->input('group_dn'), 'remove');

        AuditLog::create([
            'user_id' => auth()->id(),
            'module'  => 'adusers',
            'action'  => 'group_removed',
            'payload' => [
                'samaccountname' => $user->samaccountname,
                'group_dn'       => $request->input('group_dn'),
                'group_name'     => $request->input('group_name'),
            ],
        ]);

        return response()->json([
            'success' => true,
            'log'     => $this->formatLog($log->load('performedBy')),
        ]);
    }

    /** AJAX: Gruppenänderung rückgängig machen */
    public function revertChange(AdUser $user, AdUserGroupChangeLog $log): JsonResponse
    {
        if ($log->samaccountname !== $user->samaccountname) {
            return response()->json(['error' => 'Eintrag gehört nicht zu diesem Benutzer.'], 403);
        }

        if ($log->isReverted()) {
            return response()->json(['error' => 'Diese Änderung wurde bereits rückgängig gemacht.'], 422);
        }

        if (!$user->distinguished_name) {
            return response()->json(['error' => 'Kein Distinguished Name vorhanden.'], 422);
        }

        try {
            if ($log->action === 'add') {
                // War ein Hinzufügen → jetzt entfernen
                $this->provisioner->removeUserFromGroup($user->distinguished_name, $log->group_dn);
                $this->updateRawDataMemberOf($user, $log->group_dn, 'remove');
            } else {
                // War ein Entfernen → jetzt hinzufügen
                $this->provisioner->addUserToGroup($user->distinguished_name, $log->group_dn);
                $this->updateRawDataMemberOf($user, $log->group_dn, 'add');
            }
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $log->update([
            'reverted_at'           => now(),
            'reverted_by_user_id'   => auth()->id(),
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'module'  => 'adusers',
            'action'  => 'group_change_reverted',
            'payload' => [
                'samaccountname'   => $user->samaccountname,
                'group_dn'         => $log->group_dn,
                'original_action'  => $log->action,
            ],
        ]);

        return response()->json([
            'success'           => true,
            'reverted_at'       => $log->reverted_at->format('d.m.Y H:i'),
            'reverted_by_name'  => auth()->user()->name,
            'group_action'      => $log->action === 'add' ? 'remove' : 'add',
        ]);
    }

    /** AJAX: AD-Gruppen suchen (für Hinzufügen-Picker) */
    public function searchGroups(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 2) return response()->json([]);

        try {
            $groups = $this->provisioner->searchGroups($q);
            return response()->json($groups);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ─── private ─────────────────────────────────────────────────────────────

    /** raw_data.memberof im DB-Eintrag aktualisieren damit Seite nach Reload korrekt ist */
    private function updateRawDataMemberOf(AdUser $user, string $groupDn, string $action): void
    {
        $rawData  = $user->raw_data ?? [];
        $memberOf = $rawData['memberof'] ?? ['count' => 0];
        $count    = (int)($memberOf['count'] ?? 0);

        if ($action === 'add') {
            $memberOf[$count] = $groupDn;
            $memberOf['count'] = $count + 1;
        } else {
            $target   = strtolower($groupDn);
            $filtered = [];
            for ($i = 0; $i < $count; $i++) {
                if (strtolower($memberOf[$i] ?? '') !== $target) {
                    $filtered[] = $memberOf[$i];
                }
            }
            $memberOf = array_merge(['count' => count($filtered)], array_values($filtered));
        }

        $rawData['memberof'] = $memberOf;
        $user->update(['raw_data' => $rawData]);
    }

    private function formatLog(AdUserGroupChangeLog $log): array
    {
        return [
            'id'             => $log->id,
            'action'         => $log->action,
            'action_label'   => $log->actionLabel(),
            'group_name'     => $log->group_name,
            'group_dn'       => $log->group_dn,
            'performed_by'   => $log->performedBy?->name ?? 'Unbekannt',
            'performed_at'   => $log->created_at->format('d.m.Y H:i'),
            'reverted_at'    => $log->reverted_at?->format('d.m.Y H:i'),
            'reverted_by'    => $log->revertedBy?->name,
            'is_reverted'    => $log->isReverted(),
        ];
    }
}
