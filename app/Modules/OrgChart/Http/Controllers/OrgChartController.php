<?php

namespace App\Modules\OrgChart\Http\Controllers;

use App\Modules\OrgChart\Models\OrgInterface;
use App\Modules\OrgChart\Models\OrgNode;
use App\Modules\OrgChart\Models\OrgVersion;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrgChartController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index()
    {
        $versions = OrgVersion::withCount('nodes')
            ->orderByRaw("FIELD(status, 'aktiv', 'abstimmung', 'entwurf', 'archiviert')")
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($v) {
                $v->total_headcount_val = (float) $v->nodes()->sum('headcount');
                $v->group_count_val     = $v->nodes()->whereIn('type', ['frame', 'group'])->count();
                return $v;
            });

        $activeVersion = $versions->firstWhere('status', 'aktiv');

        return view('orgchart::index', compact('versions', 'activeVersion'));
    }

    public function create()
    {
        return view('orgchart::create', [
            'statusOptions'      => OrgVersion::STATUS_LABELS,
            'colorSchemeOptions' => OrgVersion::COLOR_SCHEMES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'status'       => ['required', 'in:entwurf,abstimmung,aktiv,archiviert'],
            'color_scheme' => ['required', 'in:klassisch,modern,behoerde,bsi'],
            'notes'        => ['nullable', 'string'],
        ]);

        if ($validated['status'] === 'aktiv') {
            OrgVersion::where('status', 'aktiv')->update(['status' => 'archiviert']);
        }

        $validated['created_by'] = Auth::user()->name;
        $version = OrgVersion::create($validated);

        $this->auditLogger->logModuleAction('OrgChart', 'Version erstellt', [
            'id' => $version->id, 'name' => $version->name,
        ]);

        return redirect()->route('orgchart.edit', $version)
            ->with('success', 'Version „' . $version->name . '" angelegt.');
    }

    public function show(OrgVersion $version)
    {
        $version->load([
            'nodes' => fn($q) => $q->orderBy('sort_order'),
        ]);

        $rootNodes = $version->nodes->whereNull('parent_id')->sortBy('sort_order');

        $interfaces = OrgInterface::with(['fromNode', 'toNode'])
            ->where('version_id', $version->id)
            ->get();

        $interfacesByNode = $interfaces->groupBy('from_node_id');

        $allNodes = $version->nodes;

        return view('orgchart::show', compact(
            'version', 'rootNodes', 'interfaces', 'interfacesByNode', 'allNodes'
        ));
    }

    public function edit(OrgVersion $version)
    {
        $version->load([
            'nodes' => fn($q) => $q->orderBy('sort_order'),
        ]);

        $rootNodes = $version->nodes->whereNull('parent_id')->sortBy('sort_order');

        $interfaces = OrgInterface::with(['fromNode', 'toNode'])
            ->where('version_id', $version->id)
            ->get();

        $allNodes = $version->nodes->whereIn('type', ['frame', 'group', 'staff', 'top']);

        return view('orgchart::edit', compact(
            'version', 'rootNodes', 'interfaces', 'allNodes'
        ));
    }

    public function update(Request $request, OrgVersion $version)
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'status'       => ['required', 'in:entwurf,abstimmung,aktiv,archiviert'],
            'color_scheme' => ['required', 'in:klassisch,modern,behoerde,bsi'],
            'notes'        => ['nullable', 'string'],
        ]);

        if ($validated['status'] === 'aktiv' && $version->status !== 'aktiv') {
            OrgVersion::where('status', 'aktiv')->where('id', '!=', $version->id)
                ->update(['status' => 'archiviert']);
        }

        $version->update($validated);

        $this->auditLogger->logModuleAction('OrgChart', 'Version aktualisiert', [
            'id' => $version->id, 'name' => $version->name,
        ]);

        return redirect()->route('orgchart.edit', $version)
            ->with('success', 'Version gespeichert.');
    }

    public function destroy(OrgVersion $version)
    {
        if (!in_array($version->status, ['entwurf', 'archiviert'])) {
            return redirect()->route('orgchart.index')
                ->with('error', 'Nur Entwürfe und archivierte Versionen können gelöscht werden.');
        }

        $name = $version->name;
        $version->delete();

        $this->auditLogger->logModuleAction('OrgChart', 'Version gelöscht', ['name' => $name]);

        return redirect()->route('orgchart.index')
            ->with('success', 'Version „' . $name . '" wurde gelöscht.');
    }

    public function duplicate(OrgVersion $version)
    {
        DB::transaction(function () use ($version) {
            $newVersion = $version->replicate();
            $newVersion->name       = $version->name . ' (Kopie)';
            $newVersion->status     = 'entwurf';
            $newVersion->created_by = Auth::user()->name;
            $newVersion->save();

            $nodeMap = [];
            $allNodes = OrgNode::where('version_id', $version->id)->orderBy('id')->get();

            foreach ($allNodes as $node) {
                $newNode = $node->replicate();
                $newNode->version_id = $newVersion->id;
                $newNode->parent_id  = null;
                $newNode->save();
                $nodeMap[$node->id] = $newNode->id;
            }

            foreach ($allNodes as $node) {
                if ($node->parent_id && isset($nodeMap[$node->parent_id])) {
                    OrgNode::where('id', $nodeMap[$node->id])
                        ->update(['parent_id' => $nodeMap[$node->parent_id]]);
                }
            }

            $interfaces = OrgInterface::where('version_id', $version->id)->get();
            foreach ($interfaces as $iface) {
                if (isset($nodeMap[$iface->from_node_id]) && isset($nodeMap[$iface->to_node_id])) {
                    OrgInterface::create([
                        'version_id'   => $newVersion->id,
                        'from_node_id' => $nodeMap[$iface->from_node_id],
                        'to_node_id'   => $nodeMap[$iface->to_node_id],
                        'label'        => $iface->label,
                        'description'  => $iface->description,
                    ]);
                }
            }

            $this->auditLogger->logModuleAction('OrgChart', 'Version dupliziert', [
                'original' => $version->name, 'new' => $newVersion->name,
            ]);

            session()->flash('success', 'Version „' . $version->name . '" wurde als Entwurf dupliziert.');
            session()->flash('_redirect_version_id', $newVersion->id);
        });

        $newId = session('_redirect_version_id');
        return $newId
            ? redirect()->route('orgchart.edit', $newId)
            : redirect()->route('orgchart.index');
    }

    public function exportPdf(OrgVersion $version)
    {
        $version->load(['nodes' => fn($q) => $q->orderBy('sort_order')]);
        $rootNodes        = $version->nodes->whereNull('parent_id')->sortBy('sort_order');
        $interfaces       = OrgInterface::with(['fromNode', 'toNode'])->where('version_id', $version->id)->get();
        $interfacesByNode = $interfaces->groupBy('from_node_id');
        $allNodes         = $version->nodes;

        $pdf = Pdf::loadView('orgchart::pdf', compact(
            'version', 'rootNodes', 'interfaces', 'interfacesByNode', 'allNodes'
        ))->setPaper('a3', 'landscape');

        return $pdf->download('organigramm-' . str($version->name)->slug() . '.pdf');
    }
}
