<?php

namespace App\Http\Controllers;

use App\Models\AccountCode;
use App\Models\CostCenter;
use App\Models\Dienstleister;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Modules\HH\Services\OrderBudgetService;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected OrderBudgetService $orderBudgetService,
    ) {}

    /**
     * Listenansicht + Dashboard (Obligo, KST-Summen, optional KST-Detail)
     */
    public function index(Request $request)
    {
        $this->authorize('orders.view');

        $filterBudgetYear = (int) $request->get('budget_year', now()->year);

        $obligo = Order::where('status', '!=', 6)
            ->when($filterBudgetYear, fn ($q) => $q->where('budget_year', $filterBudgetYear))
            ->sum('price_gross');

        $kstSummen = Order::join('it_cost_centers', 'it_orders.cost_center_id', '=', 'it_cost_centers.id')
            ->where('it_orders.status', '!=', 6)
            ->where('it_orders.budget_year', $filterBudgetYear)
            ->selectRaw('it_cost_centers.id, it_cost_centers.number, it_cost_centers.description, SUM(it_orders.price_gross) as summe')
            ->groupBy('it_cost_centers.id', 'it_cost_centers.number', 'it_cost_centers.description')
            ->get();

        // ConvertEmptyStringsToNull: has() unterscheidet "nicht vorhanden" von "leer gesendet"
        $filterStatus      = $request->get('filter_status') ?? '';
        $filterDateFrom    = $request->get('date_from') ?? '';
        $filterDateTo      = $request->get('date_to') ?? '';
        $filterOwn         = $request->boolean('filter_own');
        $filterAccountCode = (int) $request->get('filter_account_code_id', 0);
        $filterCostCenter  = (int) $request->get('filter_cost_center_id', 0);
        $filterAmountMin   = $request->filled('amount_min') ? (float) str_replace(',', '.', $request->get('amount_min')) : null;
        $filterAmountMax   = $request->filled('amount_max') ? (float) str_replace(',', '.', $request->get('amount_max')) : null;
        $search            = trim((string) ($request->get('search') ?? ''));
        $sortField         = in_array($request->get('sort'), ['order_date', 'price_gross', 'subject', 'status'])
                                ? $request->get('sort') : 'order_date';
        $sortDir           = $request->get('dir') === 'asc' ? 'asc' : 'desc';

        $query = Order::with(['vendor', 'costCenter', 'accountCode', 'hhBudgetPosition'])
            ->where('budget_year', $filterBudgetYear)
            ->orderBy($sortField, $sortDir);

        if ($filterStatus === 'nicht_angeordnet') {
            $query->where('status', '!=', 6);
        } elseif (filled($filterStatus)) {
            $query->where('status', (int) $filterStatus);
        }
        if ($filterOwn) {
            $query->where('buyer_user_id', Auth::id());
        }
        if (filled($filterDateFrom)) {
            $query->where('order_date', '>=', $filterDateFrom);
        }
        if (filled($filterDateTo)) {
            $query->where('order_date', '<=', $filterDateTo);
        }
        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('buyer_username', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn ($v) => $v->where('firmenname', 'like', "%{$search}%"));
            });
        }
        if ($filterAccountCode > 0) {
            $query->where('account_code_id', $filterAccountCode);
        }
        if ($filterCostCenter > 0) {
            $query->where('cost_center_id', $filterCostCenter);
        }
        if ($filterAmountMin !== null) {
            $query->where('price_gross', '>=', $filterAmountMin);
        }
        if ($filterAmountMax !== null) {
            $query->where('price_gross', '<=', $filterAmountMax);
        }

        $perPage = in_array((int) $request->get('per_page', 25), [25, 50, 100, 250]) ? (int) $request->get('per_page', 25) : 25;
        $orders = $query->paginate($perPage)->withQueryString();

        $kstDetails    = null;
        $kstAccounts   = [];
        $hhBudgetData  = [];
        $costCenterId  = (int) $request->get('cost_center_id', 0);

        if ($costCenterId > 0) {
            $kstDetails = CostCenter::find($costCenterId);

            if ($kstDetails) {
                $kstAccounts = Order::join('it_account_codes', 'it_orders.account_code_id', '=', 'it_account_codes.id')
                    ->selectRaw('it_account_codes.id, it_account_codes.code, it_account_codes.description, SUM(it_orders.price_gross) as summe')
                    ->where('it_orders.cost_center_id', $costCenterId)
                    ->where('it_orders.budget_year', $filterBudgetYear)
                    ->where('it_orders.status', '!=', 6)
                    ->groupBy('it_account_codes.id', 'it_account_codes.code', 'it_account_codes.description')
                    ->orderBy('it_account_codes.code')
                    ->get();

                $hhBudgetData = $this->orderBudgetService->getHhBudgetForOrderCostCenter($filterBudgetYear, $kstDetails);
            }
        }

        $availableBudgetYears = [now()->year - 1, now()->year, now()->year + 1];
        $allAccountCodes      = AccountCode::orderBy('code')->get();
        $allCostCenters       = CostCenter::orderBy('number')->get();

        return view('orders.index', compact(
            'obligo', 'kstSummen', 'orders', 'kstDetails', 'kstAccounts', 'hhBudgetData',
            'filterStatus', 'filterDateFrom', 'filterDateTo', 'filterOwn', 'search',
            'filterAccountCode', 'allAccountCodes',
            'filterCostCenter', 'allCostCenters',
            'filterAmountMin', 'filterAmountMax',
            'sortField', 'sortDir',
            'perPage', 'filterBudgetYear', 'availableBudgetYears'
        ));
    }

    /**
     * Formular: Neue Bestellung
     */
    public function create()
    {
        $this->authorize('orders.create');

        $vendors             = Dienstleister::orderBy('firmenname')->get();
        $costCenters         = CostCenter::orderBy('number')->get();
        $accountCodes        = AccountCode::orderBy('code')->get();
        $statusLabels        = Order::STATUS_LABELS;
        $availableBudgetYears = [now()->year - 1, now()->year, now()->year + 1];
        $defaultBudgetYear   = now()->year;

        return view('orders.create', compact(
            'vendors', 'costCenters', 'accountCodes', 'statusLabels',
            'availableBudgetYears', 'defaultBudgetYear'
        ));
    }

    /**
     * Neue Bestellung speichern
     */
    public function store(Request $request)
    {
        $this->authorize('orders.create');

        $validated = $this->validateOrder($request);

        $validated['buyer_username'] = Auth::user()->name;
        $validated['buyer_user_id']  = Auth::id();
        $validated['order_date']     = $validated['order_date'] ?? now()->toDateString();

        $order = Order::create($validated);

        $this->auditLogger->log('Order', 'Bestellung erstellt', [
            'order_id'    => $order->id,
            'subject'     => $order->subject,
            'status'      => Order::STATUS_LABELS[$order->status] ?? $order->status,
            'budget_year' => $order->budget_year,
        ]);

        return redirect()->route('orders.index')->with('success', 'Bestellung erfolgreich gespeichert.');
    }

    /**
     * Formular: Bestellung bearbeiten
     */
    public function edit(Order $order)
    {
        $this->authorizeOrderAccess($order);

        $vendors             = Dienstleister::orderBy('firmenname')->get();
        $costCenters         = CostCenter::orderBy('number')->get();
        $accountCodes        = AccountCode::orderBy('code')->get();
        $statusLabels        = Order::STATUS_LABELS;
        $availableBudgetYears = [now()->year - 1, now()->year, now()->year + 1];
        $defaultBudgetYear   = $order->budget_year ?? now()->year;

        return view('orders.edit', compact(
            'order', 'vendors', 'costCenters', 'accountCodes', 'statusLabels',
            'availableBudgetYears', 'defaultBudgetYear'
        ));
    }

    /**
     * Bestellung aktualisieren (inkl. Audit-Log bei Status-Änderung)
     */
    public function update(Request $request, Order $order)
    {
        $this->authorizeOrderAccess($order);

        $validated = $this->validateOrder($request);

        $oldStatus = $order->status;
        $newStatus = (int) $validated['status'];
        $oldLabel  = Order::STATUS_LABELS[$oldStatus] ?? (string) $oldStatus;
        $newLabel  = Order::STATUS_LABELS[$newStatus] ?? (string) $newStatus;

        if ($oldStatus !== $newStatus) {
            OrderHistory::create([
                'order_id'   => $order->id,
                'changed_by' => Auth::user()->name,
                'field'      => 'status',
                'old_value'  => $oldLabel,
                'new_value'  => $newLabel,
            ]);

            $validated['status_updated_at'] = now();
        }

        $order->update($validated);

        $this->auditLogger->log('Order', 'Bestellung aktualisiert', [
            'order_id' => $order->id,
            'subject'  => $order->subject,
        ]);

        return redirect()->route('orders.index')->with('success', 'Bestellung erfolgreich aktualisiert.');
    }

    /**
     * Bestellung löschen
     */
    public function destroy(Order $order)
    {
        $this->authorizeOrderDelete($order);

        $data = ['order_id' => $order->id, 'subject' => $order->subject];

        $order->history()->delete();
        $order->delete();

        $this->auditLogger->log('Order', 'Bestellung gelöscht', $data);

        return redirect()->route('orders.index')->with('success', 'Bestellung erfolgreich gelöscht.');
    }

    private function authorizeOrderAccess(Order $order): void
    {
        $user = Auth::user();
        if ($user->hasModulePermission('orders', 'edit')) return;
        if ($user->hasModulePermission('orders', 'create') && $order->isOwnedBy($user->id)) return;
        abort(403);
    }

    private function authorizeOrderDelete(Order $order): void
    {
        $user = Auth::user();
        if ($user->hasModulePermission('orders', 'delete')) return;
        if ($user->hasModulePermission('orders', 'create') && $order->isOwnedBy($user->id)) return;
        abort(403);
    }

    private function validateOrder(Request $request): array
    {
        $currentYear = now()->year;

        $validated = $request->validate([
            'subject'         => ['required', 'string', 'max:255'],
            'quantity'        => ['required', 'integer', 'min:1'],
            'price_gross'     => ['required', 'string'],
            'order_date'      => ['nullable', 'date'],
            'vendor_id'       => ['nullable', 'integer', 'exists:dienstleister,id'],
            'cost_center_id'  => ['required', 'integer', 'exists:it_cost_centers,id'],
            'account_code_id' => ['required', 'integer', 'exists:it_account_codes,id'],
            'status'          => ['required', 'integer', 'between:1,6'],
            'bemerkungen'     => ['nullable', 'string'],
            'budget_year'            => ['required', 'integer', 'between:' . ($currentYear - 1) . ',' . ($currentYear + 1)],
            'hh_budget_position_id'  => ['nullable', 'integer', 'exists:hh_budget_positions,id'],
        ]);

        $validated['price_gross'] = (float) str_replace(',', '.', $validated['price_gross']);

        if (empty($validated['order_date'])) {
            $validated['order_date'] = now()->toDateString();
        }

        return $validated;
    }
}
