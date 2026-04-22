<?php

namespace App\Modules\HH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HH\Models\BudgetPosition;
use App\Modules\HH\Models\BudgetYearVersion;
use App\Modules\HH\Services\AuthorizationService;
use App\Modules\HH\Services\PositionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetPositionController extends Controller
{
    public function __construct(
        private AuthorizationService $authService,
        private PositionService $positionService,
    ) {}

    private function isApi(Request $request): bool
    {
        $name = $request->route()?->getName() ?? '';
        return str_starts_with($name, 'api.');
    }

    public function index(Request $request, BudgetYearVersion $version): JsonResponse|View
    {
        $user = $request->user();
        $positions = $version->budgetPositions()
            ->with(['costCenter', 'account'])
            ->get()
            ->filter(fn(BudgetPosition $p) => $this->authService->canAccessCostCenter($user, $p->costCenter, 'Audit_Zugang'))
            ->values();

        if ($this->isApi($request)) {
            return response()->json($positions);
        }

        $isLeiter = $this->authService->isLeiter($user);
        $canWrite = $isLeiter;
        $canDelete = $isLeiter;
        return view('hh::positions.index', compact('version', 'positions', 'canWrite', 'canDelete'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'budget_year_version_id' => ['required', 'integer', 'exists:hh_budget_year_versions,id'],
            'cost_center_id'         => ['required', 'integer', 'exists:hh_cost_centers,id'],
            'account_id'             => ['required', 'integer', 'exists:hh_accounts,id'],
            'project_name'           => ['required', 'string', 'max:255'],
            'amount'                 => ['required', 'numeric', 'min:0.01'],
            'priority'               => ['required', 'in:hoch,mittel,niedrig'],
            'category'               => ['required', 'in:Pflichtaufgabe,gesetzlich gebunden,freiwillige Leistung'],
            'status'                 => ['required', 'in:geplant,angepasst,gestrichen'],
            'description'            => ['nullable', 'string'],
            'start_year'             => ['nullable', 'integer'],
            'end_year'               => ['nullable', 'integer'],
            'is_recurring'           => ['nullable', 'boolean'],
        ]);

        try {
            $position = $this->positionService->create($validated, $request->user());
        } catch (\InvalidArgumentException $e) {
            if ($this->isApi($request)) return response()->json(['message' => $e->getMessage()], 422);
            return back()->with('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            if ($this->isApi($request)) return response()->json(['message' => $e->getMessage()], 403);
            return back()->with('error', $e->getMessage());
        }

        if ($this->isApi($request)) {
            return response()->json($position->load(['costCenter', 'account']), 201);
        }
        $redirectTo = $request->input('_redirect_to');
        return ($redirectTo ? redirect($redirectTo) : redirect()->route('hh.versions.positions.index', $position->budget_year_version_id))
            ->with('success', 'Position wurde angelegt.');
    }

    public function update(Request $request, BudgetPosition $position): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'budget_year_version_id' => ['sometimes', 'integer', 'exists:hh_budget_year_versions,id'],
            'cost_center_id'         => ['sometimes', 'integer', 'exists:hh_cost_centers,id'],
            'account_id'             => ['sometimes', 'integer', 'exists:hh_accounts,id'],
            'project_name'           => ['sometimes', 'string', 'max:255'],
            'amount'                 => ['sometimes', 'numeric', 'min:0.01'],
            'priority'               => ['sometimes', 'in:hoch,mittel,niedrig'],
            'category'               => ['sometimes', 'in:Pflichtaufgabe,gesetzlich gebunden,freiwillige Leistung'],
            'status'                 => ['sometimes', 'in:geplant,angepasst,gestrichen'],
            'description'            => ['nullable', 'string'],
            'start_year'             => ['nullable', 'integer'],
            'end_year'               => ['nullable', 'integer'],
            'is_recurring'           => ['nullable', 'boolean'],
        ]);

        try {
            $position = $this->positionService->update($position, $validated, $request->user());
        } catch (\InvalidArgumentException $e) {
            if ($this->isApi($request)) return response()->json(['message' => $e->getMessage()], 422);
            return back()->with('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            if ($this->isApi($request)) return response()->json(['message' => $e->getMessage()], 403);
            return back()->with('error', $e->getMessage());
        }

        if ($this->isApi($request)) {
            return response()->json($position->load(['costCenter', 'account']));
        }
        $redirectTo = $request->input('_redirect_to');
        return ($redirectTo ? redirect($redirectTo) : redirect()->route('hh.versions.positions.index', $position->budget_year_version_id))
            ->with('success', 'Position wurde aktualisiert.');
    }

    public function destroy(Request $request, BudgetPosition $position): JsonResponse|RedirectResponse
    {
        $versionId = $position->budget_year_version_id;

        try {
            $this->positionService->delete($position, $request->user());
        } catch (\InvalidArgumentException $e) {
            if ($this->isApi($request)) return response()->json(['message' => $e->getMessage()], 422);
            return back()->with('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            if ($this->isApi($request)) return response()->json(['message' => $e->getMessage()], 403);
            return back()->with('error', $e->getMessage());
        }

        if ($this->isApi($request)) {
            return response()->json(null, 204);
        }
        $redirectTo = $request->input('_redirect_to');
        return ($redirectTo ? redirect($redirectTo) : redirect()->route('hh.versions.positions.index', $versionId))
            ->with('success', 'Position wurde gelöscht.');
    }
}
