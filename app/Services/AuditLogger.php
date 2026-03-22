<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Log an action to the audit log.
     *
     * @param string $module The module context (e.g., 'Core', 'User', 'Announcement')
     * @param string $action The action description (e.g., 'User created', 'Login successful')
     * @param array $payload Additional data to store (before/after values, etc.)
     * @param int|null $userId Optional user ID (defaults to current authenticated user)
     * @return AuditLog
     */
    public function log(string $module, string $action, array $payload = [], ?int $userId = null): AuditLog
    {
        $auditLog = new AuditLog([
            'user_id' => $userId ?? Auth::id(),
            'module' => $module,
            'action' => $action,
            'payload' => $payload,
        ]);
        
        $auditLog->created_at = now();
        $auditLog->save();
        
        return $auditLog;
    }

    /**
     * Log a user-related action.
     *
     * @param string $action The action description (e.g., 'created', 'updated', 'deleted')
     * @param User $user The user being acted upon
     * @param array $changes Optional array of changes (before/after values)
     * @return AuditLog
     */
    public function logUserAction(string $action, User $user, array $changes = []): AuditLog
    {
        $payload = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => $user->name,
        ];

        if (!empty($changes)) {
            $payload['changes'] = $changes;
        }

        return $this->log('User', "User {$action}", $payload);
    }

    /**
     * Log an announcement-related action.
     *
     * @param string $action The action description (e.g., 'created', 'updated', 'deleted', 'marked as fixed')
     * @param Announcement $announcement The announcement being acted upon
     * @return AuditLog
     */
    public function logAnnouncementAction(string $action, Announcement $announcement): AuditLog
    {
        $payload = [
            'announcement_id' => $announcement->id,
            'type' => $announcement->type,
            'message' => $announcement->message,
        ];

        return $this->log('Announcement', "Announcement {$action}", $payload);
    }

    /**
     * Log a module-related action.
     *
     * @param string $module The module name
     * @param string $action The action description (e.g., 'enabled', 'disabled', 'configured')
     * @param array $data Additional data about the action
     * @return AuditLog
     */
    public function logModuleAction(string $module, string $action, array $data = []): AuditLog
    {
        return $this->log($module, $action, $data);
    }
}
