<?php

namespace App\Modules\HH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HH\Http\Requests\StoreCostCenterRequest;
use App\Modules\HH\Http\Requests\UpdateCostCenterRequest;
use App\Modules\HH\Models\CostCenter;
use App\Modules\HH\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CostCenterController extends Controller
{
    public function __construct(private AuthorizationService $authService) {}

    private function isApi(Request $request): bool
    {
        $name = $request->route()?->getName() ?? '';
        return str_starts_with($name, 'api.');
    }

    public function index(Request $request): JsonResponse|View
    {
        $costCenters = CostCenter::orderBy('number')->get();
        if ($this->isApi($request)) {
            return response()->json($costCenters);
        }
        $isLeiter = $this->authService->isLeiter($request->user());
        return view('hh::cost-centers.index', compact('costCenters', 'isLeiter'));
    }

    public function store(StoreCostCenterRequest $request): JsonResponse|RedirectResponse
    {
        if (! $this->authService->isLeiter($request->user())) {
            if ($this->isApi($request)) {
                return response()->json(['message' => 'Zugriff verweigert.'], 403);
            }
            return back()->with('error', 'Zugriff verweigert.');
        }
        $costCenter = CostCenter::create($request->validated());
        if ($this->isApi($request)) {
            return response()->json($costCenter, 201);
        }
        return redirect()->route('hh.cost-centers.index')->with('success', 'Kostenstelle wurde angelegt.');
    }

    public function update(UpdateCostCenterRequest $request, CostCenter $costCenter): JsonResponse|RedirectResponse
    {
        if (! $this->authService->isLeiter($request->user())) {
            if ($this->isApi($request)) {
                return response()->json(['message' => 'Zugriff verweigert.'], 403);
            }
            return back()->with('error', 'Zugriff verweigert.');
        }
        $costCenter->update($request->validated());
        if ($this->isApi($request)) {
            return response()->json($costCenter);
        }
        return redirect()->route('hh.cost-centers.index')->with('success', 'Kostenstelle wurde aktualisiert.');
    }

    public function destroy(Request $request, CostCenter $costCenter): JsonResponse|RedirectResponse
    {
        if (! $this->authService->isLeiter($request->user())) {
            if ($this->isApi($request)) {
                return response()->json(['message' => 'Zugriff verweigert.'], 403);
            }
            return back()->with('error', 'Zugriff verweigert.');
        }
        if ($costCenter->budgetPositions()->exists()) {
            if ($this->isApi($request)) {
                return response()->json(['message' => 'Kostenstelle hat zugeordnete Positionen.'], 422);
            }
            return back()->with('error', 'Kostenstelle kann nicht gelöscht werden, da ihr Positionen zugeordnet sind.');
        }
        $costCenter->delete();
        if ($this->isApi($request)) {
            return response()->json(null, 204);
        }
        return redirect()->route('hh.cost-centers.index')->with('success', 'Kostenstelle wurde gelöscht.');
    }
}
