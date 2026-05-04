<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    public function index()
    {
        $this->authorize('base.users.edit');

        $tokens = PersonalAccessToken::with('tokenable')
            ->orderByDesc('created_at')
            ->get();

        $users = User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']);

        return view('api-tokens.index', compact('tokens', 'users'));
    }

    public function store(Request $request)
    {
        $this->authorize('base.users.edit');

        $validated = $request->validate([
            'user_id'    => ['required', 'integer', 'exists:users,id'],
            'token_name' => ['required', 'string', 'max:100'],
        ]);

        $user      = User::findOrFail($validated['user_id']);
        $newToken  = $user->createToken($validated['token_name']);

        return back()->with('new_token', $newToken->plainTextToken)
                     ->with('new_token_name', $validated['token_name']);
    }

    public function destroy(PersonalAccessToken $token)
    {
        $this->authorize('base.users.edit');

        $token->delete();

        return back()->with('success', 'API-Token widerrufen.');
    }
}
