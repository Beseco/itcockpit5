<?php
namespace App\Modules\HH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HH\Models\Account;
use App\Modules\HH\Models\BudgetPosition;
use App\Modules\HH\Models\BudgetYear;
use App\Modules\HH\Models\CostCenter;
use App\Modules\HH\Services\AuthorizationService;
use App\Modules\HH\Services\BudgetCalculationService;
use App\Modules\HH\Services\OrderBudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class DashboardController extends Controller
{
    const COOKIE_YEAR = 'hh_year_id';
    const COOKIE_CC   = 'hh_cost_center_id';
    const COOKIE_DAYS = 30;
    const DEFAULT_CC_NUMBER = '143011';

    public function __construct(
        private BudgetCalculationService $calculationService,
        private AuthorizationService $authService,
        private OrderBudgetService $orderBudgetService,
    ) {}

    private function isApi(Request $request): bool
    {
        return str_starts_with($request->route()?->getName() ?? '', 'api.');
    }

    public function index(Request $request): RedirectResponse
    {
        // Haushaltsjahr: Cookie → aktuelles Jahr → neuestes Jahr
        $budgetYear = null;

        if ($yearId = $request->cookie(self::COOKIE_YEAR)) {
            $budgetYear = BudgetYear::find($yearId);
        }

        if (!$budgetYear) {
            $budgetYear = BudgetYear::where('year', now()->year)->first()
                ?? BudgetYear::orderByDesc('year')->first();
        }

        if (!$budgetYear) {
            return redirect()->route('hh.budget-years.index')
                ->with('error', 'Noch kein Haushaltsjahr vorhanden.');
        }

        // Kostenstelle: Cookie → Standard 143011
        $ccId = $request->cookie(self::COOKIE_CC);
        if (!$ccId) {
            $defaultCc = CostCenter::where('number', self::DEFAULT_CC_NUMBER)->first();
            $ccId = $defaultCc?->id;
        }

        $url = route('hh.dashboard.show', $budgetYear);
        if ($ccId) {
            $url .= '?cost_center_id=' . $ccId;
        }

        return redirect($url);
    }

    public function show(Request $request, BudgetYear $budgetYear): JsonResponse|View
    {
        $budgetYear->load('versions');
        $allBudgetYears = BudgetYear::orderByDesc('year')->get();
        $allCostCenters = CostCenter::where('is_active', true)->orderBy('number')->get();
        $allAccounts    = Account::orderBy('number')->get();
        $activeVersion  = $budgetYear->versions->firstWhere('is_active', true);

        // Kostenstelle: Query-Parameter → Cookie → Standard 143011 (early resolve for totals)
        $selectedCostCenterId = $request->query('cost_center_id')
            ?? $request->cookie(self::COOKIE_CC)
            ?? CostCenter::where('number', self::DEFAULT_CC_NUMBER)->value('id');

        $selectedCostCenter = $selectedCostCenterId
            ? CostCenter::find($selectedCostCenterId)
            : null;

        if ($selectedCostCenter) {
            $totals = $this->calculationService->getTotalsForCostCenter($budgetYear, $selectedCostCenter);
        } else {
            $totals = $this->calculationService->getTotals($budgetYear);
        }
        $totals['investive_share'] = $totals['total'] > 0
            ? ($totals['investiv'] / $totals['total']) * 100
            : 0.0;

        $accountsWithTotals = collect();
        $ccObligo           = 0.0;
        if ($activeVersion && $selectedCostCenter) {
            $ccObligo = $this->orderBudgetService->getObligoForCostCenter($budgetYear->year, $selectedCostCenter);
            $accountsWithTotals = $allAccounts->map(function (Account $acc) use ($activeVersion, $selectedCostCenter, $budgetYear) {
                $positions = BudgetPosition::where('budget_year_version_id', $activeVersion->id)
                    ->where('cost_center_id', $selectedCostCenter->id)
                    ->where('account_id', $acc->id)
                    ->with(['costCenter', 'account'])
                    ->get();
                $planned = (float) $positions->sum('amount');
                $obligo  = $this->orderBudgetService->getObligoForAccount($budgetYear->year, $selectedCostCenter, $acc);
                return [
                    'account'   => $acc,
                    'total'     => $planned,
                    'obligo'    => $obligo,
                    'available' => $planned - $obligo,
                    'count'     => $positions->count(),
                    'positions' => $positions,
                ];
            });
        }

        if ($this->isApi($request)) {
            return response()->json([
                'budget_year_id' => $budgetYear->id,
                'year'           => $budgetYear->year,
                'status'         => $budgetYear->status,
                'totals'         => $totals,
            ]);
        }

        $canWrite = $this->authService->isLeiter($request->user());

        // Cookies setzen (30 Tage)
        $minutes = self::COOKIE_DAYS * 24 * 60;
        Cookie::queue(self::COOKIE_YEAR, $budgetYear->id, $minutes, '/', null, false, false);
        if ($selectedCostCenter) {
            Cookie::queue(self::COOKIE_CC, $selectedCostCenter->id, $minutes, '/', null, false, false);
        }

        $ccAvailable = ($totals['total'] ?? 0) - $ccObligo;

        return view('hh::dashboard', compact(
            'budgetYear', 'allBudgetYears', 'allCostCenters', 'allAccounts',
            'selectedCostCenter', 'activeVersion', 'totals', 'accountsWithTotals', 'canWrite',
            'ccObligo', 'ccAvailable'
        ));
    }

    public function search(Request $request, BudgetYear $budgetYear): View
    {
        $q = trim($request->input('q', ''));
        $budgetYear->load('versions');
        $activeVersion = $budgetYear->versions->firstWhere('is_active', true);
        $allBudgetYears = BudgetYear::orderByDesc('year')->get();

        $priority   = $request->input('priority', '');
        $status     = $request->input('status', '');
        $amountMin  = $request->input('amount_min', '');
        $amountMax  = $request->input('amount_max', '');

        $hasFilter = $q !== '' || $priority !== '' || $status !== '' || $amountMin !== '' || $amountMax !== '';

        $sortField = in_array($request->input('sort'), ['project_name', 'priority', 'status', 'amount'])
            ? $request->input('sort') : 'project_name';
        $sortDir = $request->input('dir') === 'desc' ? 'desc' : 'asc';

        $hasFilter = $q !== '' || $priority !== '' || $status !== '' || $amountMin !== '' || $amountMax !== '';

        $positions = collect();
        if ($activeVersion && $hasFilter) {
            $query = BudgetPosition::where('budget_year_version_id', $activeVersion->id);

            if ($q !== '') {
                $query->where(function ($qb) use ($q) {
                    $qb->where('project_name', 'like', "%{$q}%")
                       ->orWhere('description', 'like', "%{$q}%");
                });
            }
            if ($priority  !== '') $query->where('priority', $priority);
            if ($status    !== '') $query->where('status', $status);
            if ($amountMin !== '') $query->where('amount', '>=', (float) str_replace(',', '.', $amountMin));
            if ($amountMax !== '') $query->where('amount', '<=', (float) str_replace(',', '.', $amountMax));

            $positions = $query->with(['costCenter', 'account'])
                ->orderBy($sortField, $sortDir)
                ->get()
                ->filter(fn($p) => $this->authService->canAccessCostCenter($request->user(), $p->costCenter, 'Audit_Zugang'))
                ->values();
        }

        return view('hh::search', compact(
            'budgetYear', 'allBudgetYears', 'positions', 'q', 'priority', 'status',
            'amountMin', 'amountMax', 'hasFilter', 'activeVersion', 'sortField', 'sortDir'
        ));
    }

    public function accountPositions(Request $request, BudgetYear $budgetYear, CostCenter $costCenter, Account $account): View
    {
        $budgetYear->load('versions');
        $activeVersion = $budgetYear->versions->firstWhere('is_active', true);
        $allAccounts   = Account::orderBy('number')->get();
        $positions = collect();
        if ($activeVersion) {
            $positions = BudgetPosition::where('budget_year_version_id', $activeVersion->id)
                ->where('cost_center_id', $costCenter->id)
                ->where('account_id', $account->id)
                ->with(['costCenter', 'account'])
                ->orderBy('project_name')
                ->get();
        }
        $sortField = in_array($request->input('sort'), ['project_name', 'amount', 'priority', 'status'])
            ? $request->input('sort') : 'project_name';
        $sortDir = $request->input('dir') === 'desc' ? 'desc' : 'asc';

        if ($positions->isNotEmpty()) {
            $positions = $sortDir === 'asc'
                ? $positions->sortBy($sortField)->values()
                : $positions->sortByDesc($sortField)->values();
        }

        $canWrite      = $this->authService->isLeiter($request->user());
        $plannedTotal  = (float) $positions->sum('amount');
        $obligo        = $this->orderBudgetService->getObligoForAccount($budgetYear->year, $costCenter, $account);
        $availableBudget = $plannedTotal - $obligo;

        return view('hh::dashboard-account-positions', compact(
            'budgetYear', 'costCenter', 'account', 'activeVersion',
            'positions', 'allAccounts', 'canWrite', 'sortField', 'sortDir',
            'plannedTotal', 'obligo', 'availableBudget'
        ));
    }
}
