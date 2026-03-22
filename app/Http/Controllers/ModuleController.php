<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Services\ModuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function __construct(private ModuleService $moduleService)
    {
        $this->middleware('can:base.modules.manage');
    }

    /**
     * Display a listing of all modules (Superadmin only).
     *
     * @return View
     */
    public function index(): View
    {
        $modules = Module::orderBy('name')->get();

        return view('modules.index', compact('modules'));
    }

    /**
     * Show the form for editing the specified module.
     *
     * @param Module $module
     * @return View
     */
    public function edit(Module $module): View
    {
        return view('modules.edit', compact('module'));
    }

    /**
     * Update the specified module's metadata.
     *
     * @param Request $request
     * @param Module $module
     * @return RedirectResponse
     */
    public function update(Request $request, Module $module): RedirectResponse
    {
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $module->update($validated);

        return redirect()->route('modules.index')
            ->with('success', 'Modul erfolgreich aktualisiert.');
    }

    /**
     * Activate the specified module.
     *
     * @param Module $module
     * @return RedirectResponse
     */
    public function activate(Module $module): RedirectResponse
    {
        try {
            $this->moduleService->activateModule($module);

            return redirect()->route('modules.index')
                ->with('success', 'Modul erfolgreich aktiviert.');
        } catch (\Exception $e) {
            return redirect()->route('modules.index')
                ->with('error', 'Fehler beim Aktivieren des Moduls: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate the specified module.
     *
     * @param Module $module
     * @return RedirectResponse
     */
    public function deactivate(Module $module): RedirectResponse
    {
        try {
            $this->moduleService->deactivateModule($module);

            return redirect()->route('modules.index')
                ->with('success', 'Modul erfolgreich deaktiviert.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('modules.index')
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->route('modules.index')
                ->with('error', 'Fehler beim Deaktivieren des Moduls: ' . $e->getMessage());
        }
    }
}
