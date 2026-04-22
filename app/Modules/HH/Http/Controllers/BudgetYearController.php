<?php

namespace App\Modules\HH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HH\Models\BudgetPosition;
use App\Modules\HH\Models\BudgetYear;
use App\Modules\HH\Services\AuthorizationService;
use App\Modules\HH\Services\BudgetYearService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

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
        $isLeiter    = $this->authService->isLeiter($request->user());

        if ($this->isApi($request)) {
            return response()->json($budgetYears);
        }

        return view('hh::budget-years.index', compact('budgetYears', 'isLeiter'));
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

    public function update(Request $request, BudgetYear $budgetYear): RedirectResponse
    {
        if (! $this->authService->isLeiter($request->user())) {
            return back()->with('error', 'Zugriff verweigert.');
        }

        $validated = $request->validate([
            'year'   => ['required', 'integer', 'digits:4', Rule::unique('hh_budget_years', 'year')->ignore($budgetYear->id)],
            'status' => ['required', 'in:draft,preliminary,approved'],
        ]);

        $budgetYear->update([
            'year'   => $validated['year'],
            'status' => $validated['status'],
        ]);

        return redirect()->route('hh.budget-years.index')
            ->with('success', "Haushaltsjahr {$validated['year']} wurde gespeichert.");
    }

    public function destroy(Request $request, BudgetYear $budgetYear): RedirectResponse
    {
        if (! $this->authService->isLeiter($request->user())) {
            return back()->with('error', 'Zugriff verweigert.');
        }

        $hasPositions = BudgetPosition::whereHas('budgetYearVersion', function ($q) use ($budgetYear) {
            $q->where('budget_year_id', $budgetYear->id);
        })->exists();

        if ($hasPositions) {
            return back()->with('error', "Haushaltsjahr {$budgetYear->year} kann nicht gelöscht werden – es enthält noch Positionen.");
        }

        $year = $budgetYear->year;
        $budgetYear->versions()->delete();
        $budgetYear->delete();

        return redirect()->route('hh.budget-years.index')
            ->with('success', "Haushaltsjahr {$year} wurde gelöscht.");
    }

    /**
     * Transition a budget year to a new status (Leiter only).
     */
    public function transition(Request $request, BudgetYear $budgetYear): JsonResponse|RedirectResponse
    {
        if (! $this->authService->isLeiter($request->user())) {
            if ($this->isApi($request)) {
                return response()->json(['message' => 'Zugriff verweigert. Nur Leiter dürfen den Status ändern.'], 403);
            }
            return back()->with('error', 'Zugriff verweigert.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:preliminary,approved'],
        ]);

        try {
            $this->budgetYearService->transitionStatus($budgetYear, $validated['status'], $request->user());
        } catch (\InvalidArgumentException $e) {
            if ($this->isApi($request)) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        if ($this->isApi($request)) {
            return response()->json($budgetYear->fresh()->load('versions'));
        }

        $labels = ['preliminary' => 'Vorläufig', 'approved' => 'Genehmigt'];
        $label  = $labels[$validated['status']] ?? $validated['status'];

        return redirect()->route('hh.budget-years.index')
            ->with('success', "Haushaltsjahr {$budgetYear->year} wurde auf \"{$label}\" gesetzt.");
    }
}
