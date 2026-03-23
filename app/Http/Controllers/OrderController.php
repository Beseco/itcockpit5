<?php

namespace App\Http\Controllers;

use App\Models\AccountCode;
use App\Models\CostCenter;
use App\Models\Dienstleister;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Listenansicht + Dashboard (Obligo, KST-Summen, optional KST-Detail)
     */
    public function index(Request $request)
    {
        $this->authorize('orders.view');

        $obligo = Order::where('status', '!=', 6)->sum('price_gross');

        $kstSummen = Order::join('it_cost_centers', 'it_orders.cost_center_id', '=', 'it_cost_centers.id')
            ->where('it_orders.status', '!=', 6)
            ->selectRaw('it_cost_centers.id, it_cost_centers.number, it_cost_centers.description, SUM(it_orders.price_gross) as summe')
            ->groupBy('it_cost_centers.id', 'it_cost_centers.number', 'it_cost_centers.description')
            ->get();

        $filterStatus   = $request->get('filter_status', '');
        $filterDateFrom = $request->get('date_from', '');
        $filterDateTo   = $request->get('date_to', '');

        $query = Order::with(['vendor', 'costCenter', 'accountCode'])
            ->orderBy('order_date', 'desc');

        if ($filterStatus !== '') {
            $query->where('status', (int) $filterStatus);
        }
        if ($filterDateFrom !== '') {
            $query->where('order_date', '>=', $filterDateFrom);
        }
        if ($filterDateTo !== '') {
            $query->where('order_date', '<=', $filterDateTo);
        }

        $orders = $query->paginate(25)->withQueryString();

        $kstDetails   = null;
        $kstAccounts  = [];
        $costCenterId = (int) $request->get('cost_center_id', 0);

        if ($costCenterId > 0) {
            $kstDetails = CostCenter::find($costCenterId);

            if ($kstDetails) {
                $kstAccounts = Order::join('it_account_codes', 'it_orders.account_code_id', '=', 'it_account_codes.id')
                    ->selectRaw('it_account_codes.id, it_account_codes.code, it_account_codes.description, SUM(it_orders.price_gross) as summe')
                    ->where('it_orders.cost_center_id', $costCenterId)
                    ->groupBy('it_account_codes.id', 'it_account_codes.code', 'it_account_codes.description')
                    ->orderBy('it_account_codes.code')
                    ->get();
            }
        }

        return view('orders.index', compact(
            'obligo', 'kstSummen', 'orders', 'kstDetails', 'kstAccounts',
            'filterStatus', 'filterDateFrom', 'filterDateTo'
        ));
    }

    /**
     * Formular: Neue Bestellung
     */
    public function create()
    {
        $this->authorize('orders.create');

        $vendors      = Dienstleister::orderBy('firmenname')->get();
        $costCenters  = CostCenter::orderBy('number')->get();
        $accountCodes = AccountCode::orderBy('code')->get();
        $statusLabels = Order::STATUS_LABELS;

        return view('orders.create', compact('vendors', 'costCenters', 'accountCodes', 'statusLabels'));
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
            'order_id' => $order->id,
            'subject'  => $order->subject,
            'status'   => Order::STATUS_LABELS[$order->status] ?? $order->status,
        ]);

        return redirect()->route('orders.index')->with('success', 'Bestellung erfolgreich gespeichert.');
    }

    /**
     * Formular: Bestellung bearbeiten
     */
    public function edit(Order $order)
    {
        $this->authorizeOrderAccess($order);

        $vendors      = Dienstleister::orderBy('firmenname')->get();
        $costCenters  = CostCenter::orderBy('number')->get();
        $accountCodes = AccountCode::orderBy('code')->get();
        $statusLabels = Order::STATUS_LABELS;

        return view('orders.edit', compact('order', 'vendors', 'costCenters', 'accountCodes', 'statusLabels'));
    }

    /**
     * Bestellung aktualisieren (inkl. Audit-Log bei Status-Änderung)
     */
    public function update(Request $request, Order $order)
    {
        $this->authorizeOrderAccess($order);

        $validated = $this->validateOrder($request);

        // Audit-Log: Status-Änderung tracken
        $oldStatus    = $order->status;
        $newStatus    = (int) $validated['status'];
        $oldLabel     = Order::STATUS_LABELS[$oldStatus] ?? (string) $oldStatus;
        $newLabel     = Order::STATUS_LABELS[$newStatus] ?? (string) $newStatus;

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

    /**
     * Prüft ob der User die Bestellung bearbeiten darf:
     * - orders.edit → alle Bestellungen
     * - orders.create + eigene Bestellung → nur eigene
     */
    private function authorizeOrderAccess(Order $order): void
    {
        $user = Auth::user();

        if ($user->can('orders.edit')) {
            return;
        }

        if ($user->can('orders.create') && $order->isOwnedBy($user->id)) {
            return;
        }

        abort(403);
    }

    /**
     * Prüft ob der User die Bestellung löschen darf:
     * - orders.delete → alle Bestellungen
     * - orders.create + eigene Bestellung → nur eigene
     */
    private function authorizeOrderDelete(Order $order): void
    {
        $user = Auth::user();

        if ($user->can('orders.delete')) {
            return;
        }

        if ($user->can('orders.create') && $order->isOwnedBy($user->id)) {
            return;
        }

        abort(403);
    }

    /**
     * Gemeinsame Validierungslogik
     */
    private function validateOrder(Request $request): array
    {
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
        ]);

        // Komma → Punkt für Dezimalzahlen
        $validated['price_gross'] = (float) str_replace(',', '.', $validated['price_gross']);

        if (empty($validated['order_date'])) {
            $validated['order_date'] = now()->toDateString();
        }

        return $validated;
    }
}
