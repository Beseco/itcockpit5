<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class CostCenterController extends Controller
{
    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    public function index()
    {
        $costCenters = CostCenter::orderBy('number')->get();
        return view('cost-centers.index', compact('costCenters'));
    }

    public function create()
    {
        return view('cost-centers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'number'      => ['required', 'string', 'max:50', 'unique:it_cost_centers,number'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $cc = CostCenter::create($validated);
        $this->auditLogger->log('CostCenter', 'Kostenstelle erstellt', ['number' => $cc->number]);

        return redirect()->route('cost-centers.index')->with('success', 'Kostenstelle erfolgreich angelegt.');
    }

    public function show(CostCenter $costCenter)
    {
        return redirect()->route('cost-centers.edit', $costCenter);
    }

    public function edit(CostCenter $costCenter)
    {
        return view('cost-centers.edit', compact('costCenter'));
    }

    public function update(Request $request, CostCenter $costCenter)
    {
        $validated = $request->validate([
            'number'      => ['required', 'string', 'max:50', 'unique:it_cost_centers,number,' . $costCenter->id],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $costCenter->update($validated);
        $this->auditLogger->log('CostCenter', 'Kostenstelle aktualisiert', ['number' => $costCenter->number]);

        return redirect()->route('cost-centers.index')->with('success', 'Kostenstelle erfolgreich aktualisiert.');
    }

    public function destroy(CostCenter $costCenter)
    {
        $number = $costCenter->number;
        $costCenter->delete();
        $this->auditLogger->log('CostCenter', 'Kostenstelle gelöscht', ['number' => $number]);

        return redirect()->route('cost-centers.index')->with('success', 'Kostenstelle erfolgreich gelöscht.');
    }
}
