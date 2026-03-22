<?php

namespace App\Http\Controllers;

use App\Models\AccountCode;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class AccountCodeController extends Controller
{
    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    public function index()
    {
        $accountCodes = AccountCode::orderBy('code')->get();
        return view('account-codes.index', compact('accountCodes'));
    }

    public function create()
    {
        return view('account-codes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => ['required', 'string', 'max:50', 'unique:it_account_codes,code'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $ac = AccountCode::create($validated);
        $this->auditLogger->log('AccountCode', 'Sachkonto erstellt', ['code' => $ac->code]);

        return redirect()->route('account-codes.index')->with('success', 'Sachkonto erfolgreich angelegt.');
    }

    public function show(AccountCode $accountCode)
    {
        return redirect()->route('account-codes.edit', $accountCode);
    }

    public function edit(AccountCode $accountCode)
    {
        return view('account-codes.edit', compact('accountCode'));
    }

    public function update(Request $request, AccountCode $accountCode)
    {
        $validated = $request->validate([
            'code'        => ['required', 'string', 'max:50', 'unique:it_account_codes,code,' . $accountCode->id],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $accountCode->update($validated);
        $this->auditLogger->log('AccountCode', 'Sachkonto aktualisiert', ['code' => $accountCode->code]);

        return redirect()->route('account-codes.index')->with('success', 'Sachkonto erfolgreich aktualisiert.');
    }

    public function destroy(AccountCode $accountCode)
    {
        $code = $accountCode->code;
        $accountCode->delete();
        $this->auditLogger->log('AccountCode', 'Sachkonto gelöscht', ['code' => $code]);

        return redirect()->route('account-codes.index')->with('success', 'Sachkonto erfolgreich gelöscht.');
    }
}
