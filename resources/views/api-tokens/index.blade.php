<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">API-Token</h2>
        </div>
    </x-slot>

    <div class="py-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6"
         x-data="{ revokeId: null }">

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Neuer Token angezeigt --}}
        @if(session('new_token'))
            <div x-data="{ copied: false }" class="p-4 bg-amber-50 border border-amber-300 rounded-lg">
                <p class="text-sm font-semibold text-amber-800 mb-2">
                    Token „{{ session('new_token_name') }}" wurde erstellt – nur jetzt sichtbar!
                </p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 block bg-white border border-amber-200 rounded px-3 py-2 text-sm font-mono text-gray-800 break-all select-all">{{ session('new_token') }}</code>
                    <button @click="navigator.clipboard.writeText('{{ session('new_token') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="px-3 py-2 bg-amber-600 text-white text-xs rounded hover:bg-amber-700 whitespace-nowrap">
                        <span x-show="!copied">Kopieren</span>
                        <span x-show="copied">Kopiert ✓</span>
                    </button>
                </div>
                <p class="mt-2 text-xs text-amber-700">Speichere diesen Token sicher — er wird nicht erneut angezeigt.</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Token erstellen --}}
            <div class="bg-white shadow rounded-lg p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Neuen Token erstellen</h3>
                <form method="POST" action="{{ route('api-tokens.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Benutzer</label>
                        <select name="user_id" required
                                class="w-full border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            <option value="">Benutzer wählen…</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Token-Name</label>
                        <input type="text" name="token_name" value="{{ old('token_name') }}"
                               placeholder="z. B. PowerShell-Script"
                               class="w-full border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        @error('token_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit"
                            class="w-full px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                        Token erstellen
                    </button>
                </form>

                <div class="mt-5 pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-500 font-medium mb-2">Verwendung:</p>
                    <code class="block bg-gray-50 rounded p-2 text-xs text-gray-700 break-all">
                        Authorization: Bearer &lt;token&gt;
                    </code>
                    <p class="mt-2 text-xs text-gray-400">
                        Basis-URL: <code class="text-gray-600">{{ config('app.url') }}/api/v1/</code>
                    </p>
                </div>
            </div>

            {{-- Token-Liste --}}
            <div class="lg:col-span-2 bg-white shadow rounded-lg overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-700">Aktive Token ({{ $tokens->count() }})</h3>
                </div>
                @if($tokens->isEmpty())
                    <p class="px-5 py-8 text-center text-gray-400 text-sm">Noch keine Token erstellt.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Benutzer</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Erstellt</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Zuletzt genutzt</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($tokens as $token)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-gray-700">
                                        {{ $token->tokenable?->name ?? '—' }}
                                        <span class="text-gray-400 text-xs block">{{ $token->tokenable?->email }}</span>
                                    </td>
                                    <td class="px-4 py-2 font-medium text-gray-900">{{ $token->name }}</td>
                                    <td class="px-4 py-2 text-gray-500 text-xs whitespace-nowrap">
                                        {{ $token->created_at->format('d.m.Y H:i') }}
                                    </td>
                                    <td class="px-4 py-2 text-gray-500 text-xs whitespace-nowrap">
                                        {{ $token->last_used_at?->format('d.m.Y H:i') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <button @click="revokeId = {{ $token->id }}"
                                                class="text-xs text-red-500 hover:text-red-700 hover:underline">
                                            Widerrufen
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Revoke Confirm Modal --}}
        <div x-show="revokeId !== null" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
                <h3 class="text-base font-semibold text-gray-900 mb-2">Token widerrufen</h3>
                <p class="text-sm text-gray-600 mb-4">Dieser Token wird sofort ungültig. Skripte oder Integrationen, die ihn verwenden, müssen aktualisiert werden.</p>
                <div class="flex justify-end gap-3">
                    <button @click="revokeId = null"
                            class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Abbrechen
                    </button>
                    <form :action="'{{ url('api-tokens') }}/' + revokeId" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 text-sm text-white bg-red-600 rounded-md hover:bg-red-700">
                            Widerrufen
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
