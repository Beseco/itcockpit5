<?php

namespace App\Modules\Network\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Network\Models\Vlan;
use App\Modules\Network\Models\VlanComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VlanCommentController extends Controller
{
    /**
     * Store a newly created VLAN comment.
     *
     * Creates a new comment associated with the authenticated user
     * and the specified VLAN.
     */
    public function store(Request $request, Vlan $vlan): RedirectResponse
    {
        // Validate comment text
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        // Create comment associated with authenticated user
        $comment = VlanComment::create([
            'vlan_id' => $vlan->id,
            'user_id' => auth()->id(),
            'comment' => $validated['comment'],
        ]);

        // Log the action
        $auditLogger = app(\App\Services\AuditLogger::class);
        $auditLogger->logModuleAction('Network', 'VLAN comment created', [
            'comment_id' => $comment->id,
            'vlan_id' => $vlan->id,
            'vlan_name' => $vlan->vlan_name,
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Comment added successfully.');
    }

    /**
     * Remove the specified VLAN comment.
     *
     * Deletes a comment if the user is the author or has super-admin role.
     */
    public function destroy(VlanComment $comment): RedirectResponse
    {
        $user = auth()->user();

        // Check authorization (author or super-admin)
        if (!$comment->canDelete($user)) {
            abort(403, 'You can only delete your own comments.');
        }

        $commentId = $comment->id;
        $vlanId = $comment->vlan_id;

        // Log the action before deletion
        $auditLogger = app(\App\Services\AuditLogger::class);
        $auditLogger->logModuleAction('Network', 'VLAN comment deleted', [
            'comment_id' => $commentId,
            'vlan_id' => $vlanId,
            'deleted_by_user_id' => auth()->id(),
        ]);

        // Delete the comment
        $comment->delete();

        return redirect()
            ->back()
            ->with('success', 'Comment deleted successfully.');
    }
}
