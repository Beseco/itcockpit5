<?php

namespace App\Modules\HH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HH\Http\Requests\StoreAccountRequest;
use App\Modules\HH\Http\Requests\UpdateAccountRequest;
use App\Modules\HH\Models\Account;
use App\Modules\HH\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(private AuthorizationService $authService) {}

    private function isApi(Request $request): bool
    {
        $name = $request->route()?->getName() ?? '';
        return str_starts_with($name, 'api.');
    }

    public function index(Request $request): JsonResponse|View
    {
        $accounts = Account::orderBy('number')->get();
        if ($this->isApi($request)) {
            return response()->json($accounts);
        }
        $isLeiter = $this->authService->isLeiter($request->user());
        return view('hh::accounts.index', compact('accounts', 'isLeiter'));
    }

    public function store(StoreAccountRequest $request): JsonResponse|RedirectResponse
    {
        if (! $this->authService->isLeiter($request->user())) {
            if ($this->isApi($request)) {
                return response()->json(['message' => 'Zugriff verweigert.'], 403);
            }
            return back()->with('error', 'Zugriff verweigert.');
        }
        $account = Account::create($request->validated());
        if ($this->isApi($request)) {
            return response()->json($account, 201);
        }
        return redirect()->route('hh.accounts.index')->with('success', 'Sachkonto wurde angelegt.');
    }

    public function update(UpdateAccountRequest $request, Account $account): JsonResponse|RedirectResponse
    {
        if (! $this->authService->isLeiter($request->user())) {
            if ($this->isApi($request)) {
                return response()->json(['message' => 'Zugriff verweigert.'], 403);
            }
            return back()->with('error', 'Zugriff verweigert.');
        }
        $account->update($request->validated());
        if ($this->isApi($request)) {
            return response()->json($account);
        }
        return redirect()->route('hh.accounts.index')->with('success', 'Sachkonto wurde aktualisiert.');
    }

    public function destroy(Request $request, Account $account): JsonResponse|RedirectResponse
    {
        if (! $this->authService->isLeiter($request->user())) {
            if ($this->isApi($request)) {
                return response()->json(['message' => 'Zugriff verweigert.'], 403);
            }
            return back()->with('error', 'Zugriff verweigert.');
        }
        if ($account->budgetPositions()->exists()) {
            if ($this->isApi($request)) {
                return response()->json(['message' => 'Sachkonto hat zugeordnete Positionen.'], 422);
            }
            return back()->with('error', 'Sachkonto kann nicht gelöscht werden, da ihm Positionen zugeordnet sind.');
        }
        $account->delete();
        if ($this->isApi($request)) {
            return response()->json(null, 204);
        }
        return redirect()->route('hh.accounts.index')->with('success', 'Sachkonto wurde gelöscht.');
    }
}
