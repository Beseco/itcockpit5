<?php

namespace App\Modules\HH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HH\Models\BudgetYear;
use App\Modules\HH\Services\AuthorizationService;
use App\Modules\HH\Services\BudgetYearService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetYearController extends Controller
{
    public function __construct(
        private AuthorizationService $authService,
        private BudgetYearService $budgetYearService,
    ) {}

    private function isApi(Request $request): bool
    {
        $name = $request->route()?->getName() ?? '';
        return str_starts_with($name, 'api.');
    }

    /**
     * List all budget years with their versions.
     */
    public function index(Request $request): JsonResponse|View
    {
        $budgetYears = BudgetYear::with('versions')->orderBy('year')->get();

        if ($this->isApi($request)) {
            return response()->json($budgetYears);
        }

        return view('hh::budget-years.index', compact('budgetYears'));
    }

    /**
     * Create a new budget year (Leiter only).
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        if (! $this->authService->isLeiter($request->user())) {
            if ($this->isApi($request)) {
                return response()->json(['message' => 'Zugriff verweigert. Nur Leiter dürfen Haushaltsjahre anlegen.'], 403);
            }
            return back()->with('error', 'Zugriff verweigert.');
        }

        $validated = $request->validate([
            'year' => ['required', 'integer', 'digits:4'],
        ]);

        try {
            $budgetYear = $this->budgetYearService->create($validated['year'], $request->user());
        } catch (\RuntimeException $e) {
            if ($this->isApi($request)) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        if ($this->isApi($request)) {
            return response()->json($budgetYear->load('versions'), 201);
        }

        return redirect()->route('hh.budget-years.index')->with('success', "Haushaltsjahr {$budgetYear->year} wurde angelegt.");
    }

    /**
     * Show a single budget year with its versions.
     */
    public function show(BudgetYear $budgetYear): JsonResponse
    {
        return response()->json($budgetYear->load('versions'));
    }

    /**
     * Transition a budget year to a new status (Leiter only).
     */
    public function transition(Request $request, BudgetYear $budgetYear): JsonResponse
    {
        if (! $this->authService->isLeiter($request->user())) {
            return response()->json(['message' => 'Zugriff verweigert. Nur Leiter dürfen den Status ändern.'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:preliminary,approved'],
        ]);

        try {
            $this->budgetYearService->transitionStatus($budgetYear, $validated['status'], $request->user());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($budgetYear->fresh()->load('versions'));
    }
}
