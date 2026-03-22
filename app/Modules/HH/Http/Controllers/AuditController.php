<?php

namespace App\Modules\HH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HH\Models\UserCostCenterRole;
use App\Modules\HH\Services\AuditService;
use App\Modules\HH\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private AuthorizationService $authService,
    ) {}

    public function index(Request $request): JsonResponse|View
    {
        $user = $request->user();

        $isLeiter = $this->authService->isLeiter($user);
        $hasAuditAccess = UserCostCenterRole::where('user_id', $user->id)
            ->where('role', 'Audit_Zugang')
            ->exists();

        if (!$isLeiter && !$hasAuditAccess) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Zugriff verweigert.'], 403);
            }
            abort(403, 'Zugriff verweigert.');
        }

        $filters = $request->only(['budget_year_id', 'cost_center_id', 'from', 'to']);
        $entries = $this->auditService->getEntries($filters);

        if ($request->expectsJson()) {
            return response()->json($entries);
        }

        $budgetYears = \App\Modules\HH\Models\BudgetYear::orderBy('year')->get();
        $costCenters = \App\Modules\HH\Models\CostCenter::orderBy('number')->get();

        return view('hh::audit.index', compact('entries', 'budgetYears', 'costCenters'));
    }
}
