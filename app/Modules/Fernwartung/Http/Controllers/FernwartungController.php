<?php

namespace App\Modules\Fernwartung\Http\Controllers;

use App\Models\Dienstleister;
use App\Models\User;
use App\Modules\Fernwartung\Models\Fernwartung;
use App\Modules\Fernwartung\Models\FernwartungTool;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class FernwartungController extends Controller
{
    public function index(Request $request)
    {
        $query = Fernwartung::with('beobachter')
            ->orderBy('datum', 'desc')
            ->orderBy('beginn', 'desc');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('externer_name', 'like', "%{$search}%")
                  ->orWhere('firma',        'like', "%{$search}%")
                  ->orWhere('ziel',         'like', "%{$search}%")
                  ->orWhere('tool',         'like', "%{$search}%")
                  ->orWhere('beobachter_name', 'like', "%{$search}%");
            });
        }

        $eintraege = $query->paginate(50)->withQueryString();
        $canDelete = Auth::user()->can('fernwartung.delete');

        return view('fernwartung::index', compact('eintraege', 'search', 'canDelete'));
    }

    public function create()
    {
        $tools        = FernwartungTool::active()->get();
        $users        = User::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $dienstleister = Dienstleister::orderBy('firmenname')->get(['id', 'firmenname']);
        $fernwartung  = null;

        return view('fernwartung::create', compact('tools', 'users', 'dienstleister', 'fernwartung'));
    }

    public function store(Request $request)
    {
        $data = $this->validateAndBuild($request);
        $data['created_by'] = Auth::id();

        Fernwartung::create($data);

        return redirect()->route('fernwartung.index')
            ->with('success', 'Fernwartung wurde erfolgreich eingetragen.');
    }

    public function edit(Fernwartung $fw)
    {
        $this->authorizeEdit($fw);
        $tools         = FernwartungTool::active()->get();
        $users         = User::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $dienstleister = Dienstleister::orderBy('firmenname')->get(['id', 'firmenname']);

        return view('fernwartung::edit', compact('fw', 'tools', 'users', 'dienstleister'));
    }

    public function update(Request $request, Fernwartung $fw)
    {
        $this->authorizeEdit($fw);
        $fw->update($this->validateAndBuild($request));

        return redirect()->route('fernwartung.index')
            ->with('success', 'Fernwartung wurde aktualisiert.');
    }

    public function destroy(Fernwartung $fw)
    {
        $user = Auth::user();
        if (!$fw->kannGeloeschtWerden() && !$user->can('fernwartung.delete')) {
            abort(403, 'Löschen nur innerhalb von 1 Stunde nach Erstellung möglich.');
        }
        $fw->delete();

        return redirect()->route('fernwartung.index')
            ->with('success', 'Eintrag wurde gelöscht.');
    }

    public function setEnde(Fernwartung $fw)
    {
        $this->authorizeEdit($fw);
        $fw->update(['ende' => now()->format('H:i')]);

        return redirect()->route('fernwartung.index')
            ->with('success', 'Ende-Uhrzeit wurde auf ' . now()->format('H:i') . ' gesetzt.');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function validateAndBuild(Request $request): array
    {
        $request->validate([
            'externer_name'      => ['required', 'string', 'max:255'],
            'firma_select'       => ['required', 'string'],
            'firma_custom'       => ['nullable', 'string', 'max:255'],
            'beobachter_user_id' => ['nullable', 'exists:users,id'],
            'ziel'               => ['required', 'string', 'max:255'],
            'tool_select'        => ['required', 'string'],
            'tool_custom'        => ['nullable', 'string', 'max:100'],
            'datum'              => ['required', 'date_format:Y-m-d'],
            'beginn'             => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'ende'               => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'grund'              => ['required', 'string'],
        ]);

        // Firma bestimmen – ggf. als neuen Dienstleister anlegen
        if ($request->firma_select === '__other__') {
            $firmaName = trim($request->firma_custom);
            if ($firmaName) {
                Dienstleister::firstOrCreate(
                    ['firmenname' => $firmaName],
                    ['firmenname' => $firmaName]
                );
            }
        } else {
            $firmaName = $request->firma_select;
        }

        $tool = $request->tool_select === '__other__'
            ? trim($request->tool_custom)
            : $request->tool_select;

        $beobachterUser = $request->beobachter_user_id
            ? User::find($request->beobachter_user_id)
            : null;

        return [
            'externer_name'      => $request->externer_name,
            'firma'              => $firmaName,
            'beobachter_user_id' => $request->beobachter_user_id ?: null,
            'beobachter_name'    => $beobachterUser?->name,
            'ziel'               => $request->ziel,
            'tool'               => $tool,
            'datum'              => $request->datum,
            'beginn'             => $request->beginn,
            'ende'               => $request->ende ?: null,
            'grund'              => $request->grund,
        ];
    }

    private function authorizeEdit(Fernwartung $fw): void
    {
        $user = Auth::user();
        if ($user->can('fernwartung.edit') || $fw->created_by === $user->id) return;
        abort(403);
    }
}
