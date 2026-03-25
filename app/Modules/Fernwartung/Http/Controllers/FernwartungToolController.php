<?php

namespace App\Modules\Fernwartung\Http\Controllers;

use App\Modules\Fernwartung\Models\FernwartungTool;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class FernwartungToolController extends Controller
{
    public function index()
    {
        $this->authorizeManage();
        $tools = FernwartungTool::orderBy('sort_order')->orderBy('name')->get();

        return view('fernwartung::tools.index', compact('tools'));
    }

    public function store(Request $request)
    {
        $this->authorizeManage();
        $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:fernwartung_tools,name'],
        ]);

        $maxOrder = FernwartungTool::max('sort_order') ?? 0;
        FernwartungTool::create([
            'name'       => trim($request->name),
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('fernwartung.tools.index')
            ->with('success', 'Tool wurde hinzugefügt.');
    }

    public function destroy(FernwartungTool $tool)
    {
        $this->authorizeManage();
        $tool->delete();

        return redirect()->route('fernwartung.tools.index')
            ->with('success', 'Tool wurde entfernt.');
    }

    private function authorizeManage(): void
    {
        if (!Auth::user()->can('fernwartung.tools.manage')) {
            abort(403);
        }
    }
}
