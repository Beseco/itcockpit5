<?php

namespace App\Modules\OrgChart\Http\Controllers;

use App\Modules\OrgChart\Models\OrgInterface;
use App\Modules\OrgChart\Models\OrgVersion;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OrgInterfaceController extends Controller
{
    public function store(Request $request, OrgVersion $version)
    {
        $validated = $request->validate([
            'from_node_id' => ['required', 'integer', 'exists:orgchart_nodes,id'],
            'to_node_id'   => ['required', 'integer', 'exists:orgchart_nodes,id', 'different:from_node_id'],
            'label'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
        ]);

        $validated['version_id'] = $version->id;
        OrgInterface::create($validated);

        return redirect()->route('orgchart.edit', $version)
            ->with('success', 'Schnittstelle angelegt.');
    }

    public function destroy(OrgVersion $version, OrgInterface $iface)
    {
        abort_unless($iface->version_id === $version->id, 404);
        $iface->delete();

        return redirect()->route('orgchart.edit', $version)
            ->with('success', 'Schnittstelle gelöscht.');
    }
}
