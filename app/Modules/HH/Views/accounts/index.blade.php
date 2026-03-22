<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sachkonten</h2>
            @if($isLeiter)
                <button onclick="document.getElementById('modal-create-account').classList.remove('hidden')"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-xs font-semibold uppercase rounded hover:bg-gray-700 transition">
                    + Neues Sachkonto
                </button>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nummer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bezeichnung</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Typ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                @if($isLeiter)
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($accounts as $account)
                                <tr>
                                    <td class="px-6 py-3 whitespace-nowrap font-mono">{{ $account->number }}</td>
                                    <td class="px-6 py-3">{{ $account->name }}</td>
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-xs {{ $account->type === 'investiv' ? 'bg-blue-100 text-blue-800' : 'bg-indigo-100 text-indigo-800' }}">
                                            {{ $account->type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-xs {{ $account->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $account->is_active ? 'Aktiv' : 'Inaktiv' }}
                                        </span>
                                    </td>
                                    @if($isLeiter)
                                        <td class="px-6 py-3 whitespace-nowrap space-x-2">
                                            <button type="button"
                                                    onclick="openEditAccount({{ $account->id }}, '{{ addslashes($account->number) }}', '{{ addslashes($account->name) }}', '{{ $account->type }}', {{ $account->is_active ? 'true' : 'false' }})"
                                                    class="text-yellow-600 hover:text-yellow-800">Bearbeiten</button>
                                            <form method="POST" action="{{ url('hh/accounts/' . $account->id) }}" class="inline"
                                                  onsubmit="return confirm('Sachkonto wirklich löschen?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800">Löschen</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isLeiter ? 5 : 4 }}" class="px-6 py-6 text-center text-gray-500">
                                        Keine Sachkonten vorhanden.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- Create Modal --}}
    <div id="modal-create-account" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Neues Sachkonto</h3>
            <form method="POST" action="{{ route('hh.accounts.store') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nummer</label>
                        <input type="text" name="number" required
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bezeichnung</label>
                        <input type="text" name="name" required
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Typ</label>
                        <select name="type" required
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                            <option value="investiv">investiv</option>
                            <option value="konsumtiv">konsumtiv</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="account-create-active" value="1" checked
                               class="rounded border-gray-300">
                        <label for="account-create-active" class="text-sm text-gray-700">Aktiv</label>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button"
                            onclick="document.getElementById('modal-create-account').classList.add('hidden')"
                            class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded hover:bg-gray-50">
                        Abbrechen
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm bg-gray-800 text-white rounded hover:bg-gray-700">
                        Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div id="modal-edit-account" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Sachkonto bearbeiten</h3>
            <form id="form-edit-account" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nummer</label>
                        <input type="text" name="number" id="account-edit-number" required
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bezeichnung</label>
                        <input type="text" name="name" id="account-edit-name" required
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Typ</label>
                        <select name="type" id="account-edit-type" required
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                            <option value="investiv">investiv</option>
                            <option value="konsumtiv">konsumtiv</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="account-edit-active" value="1"
                               class="rounded border-gray-300">
                        <label for="account-edit-active" class="text-sm text-gray-700">Aktiv</label>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button"
                            onclick="document.getElementById('modal-edit-account').classList.add('hidden')"
                            class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded hover:bg-gray-50">
                        Abbrechen
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm bg-gray-800 text-white rounded hover:bg-gray-700">
                        Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditAccount(id, number, name, type, isActive) {
            document.getElementById('form-edit-account').action = '{{ url('hh/accounts') }}/' + id;
            document.getElementById('account-edit-number').value = number;
            document.getElementById('account-edit-name').value = name;
            document.getElementById('account-edit-type').value = type;
            document.getElementById('account-edit-active').checked = isActive;
            document.getElementById('modal-edit-account').classList.remove('hidden');
        }
    </script>
</x-app-layout>
