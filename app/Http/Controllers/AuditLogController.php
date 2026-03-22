<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('module.access:audit');
    }

    /**
     * Display a listing of audit logs with optional filters.
     */
    public function index(Request $request)
    {
        $this->authorize('audit.view');

        $query = AuditLog::with('user');

        // Filter by module if provided
        if ($request->filled('module')) {
            $query->byModule($request->module);
        }

        // Filter by user if provided
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        // Filter by date range if provided
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        // Get distinct modules for filter dropdown
        $modules = AuditLog::select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        // Get all users for filter dropdown
        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $logs = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('audit-logs.index', compact('logs', 'modules', 'users'));
    }

    /**
     * Display the specified audit log entry.
     */
    public function show(AuditLog $auditLog)
    {
        $this->authorize('audit.view');

        $auditLog->load('user');
        
        return view('audit-logs.show', compact('auditLog'));
    }
}
