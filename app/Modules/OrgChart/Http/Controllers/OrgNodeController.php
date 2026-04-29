<?php

namespace App\Modules\OrgChart\Http\Controllers;

use App\Modules\OrgChart\Models\OrgNode;
use App\Modules\OrgChart\Models\OrgVersion;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OrgNodeController extends Controller
{
    public function store(Request $request, OrgVersion $version)
    {
        $validated = $request->validate([
            'parent_id'   => ['nullable', 'integer', 'exists:orgchart_nodes,id'],
            'type'        => ['required', 'in:top,staff,frame,group,task'],
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'color'       => ['nullable', 'string', 'max:7'],
            'headcount'   => ['nullable', 'numeric', 'min:0', 'max:99.99'],
        ]);

        $validated['version_id'] = $version->id;
        $validated['sort_order'] = OrgNode::where('version_id', $version->id)
            ->where('parent_id', $validated['parent_id'] ?? null)
            ->max('sort_order') + 1;

        OrgNode::create($validated);

        return redirect()->route('orgchart.edit', $version)
            ->with('success', 'Knoten „' . $validated['name'] . '" hinzugefügt.');
    }

    public function update(Request $request, OrgVersion $version, OrgNode $node)
    {
        abort_unless($node->version_id === $version->id, 404);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'type'        => ['required', 'in:top,staff,frame,group,task'],
            'description' => ['nullable', 'string'],
            'color'       => ['nullable', 'string', 'max:7'],
            'headcount'   => ['nullable', 'numeric', 'min:0', 'max:99.99'],
        ]);

        $node->update($validated);

        return redirect()->route('orgchart.edit', $version)
            ->with('success', 'Knoten aktualisiert.');
    }

    public function destroy(OrgVersion $version, OrgNode $node)
    {
        abort_unless($node->version_id === $version->id, 404);

        $name = $node->name;
        $node->delete();

        return redirect()->route('orgchart.edit', $version)
            ->with('success', '„' . $name . '" und alle Unterknoten wurden gelöscht.');
    }

    public function moveUp(OrgVersion $version, OrgNode $node)
    {
        abort_unless($node->version_id === $version->id, 404);

        $sibling = OrgNode::where('version_id', $version->id)
            ->where('parent_id', $node->parent_id)
            ->where('sort_order', '<', $node->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        if ($sibling) {
            [$node->sort_order, $sibling->sort_order] = [$sibling->sort_order, $node->sort_order];
            $node->save();
            $sibling->save();
        }

        return redirect()->route('orgchart.edit', $version);
    }

    public function moveDown(OrgVersion $version, OrgNode $node)
    {
        abort_unless($node->version_id === $version->id, 404);

        $sibling = OrgNode::where('version_id', $version->id)
            ->where('parent_id', $node->parent_id)
            ->where('sort_order', '>', $node->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($sibling) {
            [$node->sort_order, $sibling->sort_order] = [$sibling->sort_order, $node->sort_order];
            $node->save();
            $sibling->save();
        }

        return redirect()->route('orgchart.edit', $version);
    }
}
