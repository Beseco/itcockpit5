<?php

namespace App\Modules\HH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HH\Models\BudgetYear;
use App\Modules\HH\Services\AuthorizationService;
use App\Modules\HH\Services\BudgetYearService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetYearVersionController extends Controller
{
    public function __construct(
        private AuthorizationService $authService,
        private BudgetYearService $budgetYearService,
    ) {}

    /**
     * Create a new version for the given budget year (Leiter only).
     */
    public function store(Request $request, BudgetYear $budgetYear): JsonResponse
    {
        if (! $this->authService->isLeiter($request->user())) {
            return response()->json(['message' => 'Zugriff verweigert. Nur Leiter dürfen neue Versionen erstellen.'], 403);
        }

        try {
            $version = $this->budgetYearService->createVersion($budgetYear, $request->user());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($version, 201);
    }
}
