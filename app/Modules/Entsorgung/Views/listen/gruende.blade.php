<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Entsorgung – Entsorgungsgründe verwalten</h2>
            <a href="{{ route('entsorgung.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700">← Zurück zur Liste</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                     class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Neuen Entsorgungsgrund hinzufügen</h3>
                <form method="POST" action="{{ route('entsorgung.listen.gruende.store') }}" class="flex gap-2">
                    @csrf
                    <x-text-input name="name" type="text" class="flex-1"
                                  placeholder="z. B. Technisch überholt" value="{{ old('name') }}" />
                    <x-primary-button type="submit">Hinzufügen</x-primary-button>
                </form>
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entsorgungsgrund</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($gruende as $grund)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-900">{{ $grund->name }}</td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('entsorgung.listen.gruende.destroy', $grund) }}"
                                      onsubmit="return confirm('Entsorgungsgrund entfernen?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center justify-center w-7 h-7 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="px-4 py-6 text-center text-gray-400">Keine Entsorgungsgründe vorhanden.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
