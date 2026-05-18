<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('schulen.matrix') }}" class="text-gray-400 hover:text-gray-600">← Matrix</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Schulen – Einstellungen</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                     class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            {{-- ── Schultypen ─────────────────────────────────────────────────── --}}
            <div x-data="{ showForm: false }" class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Schultypen</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Gruppierung der Schulen in der Matrix-Kopfzeile</p>
                    </div>
                    <button @click="showForm = !showForm"
                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded-md text-xs font-medium text-white hover:bg-indigo-700">
                        + Neuer Schultyp
                    </button>
                </div>

                {{-- Neu-Formular --}}
                <div x-show="showForm" x-transition class="px-6 py-4 border-b border-gray-100 bg-indigo-50">
                    <form action="{{ route('schulen.einstellungen.typen.store') }}" method="POST"
                          class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                        @csrf
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Name *</label>
                            <input type="text" name="name" required placeholder="z.B. Grundschule"
                                   class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Farbe</label>
                            <select name="farbe_klassen"
                                    class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                @foreach ($farbenOptionen as $klasse => $label)
                                    <option value="{{ $klasse }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Sortierung</label>
                            <input type="number" name="sort_order" value="{{ $schulTypen->count() + 1 }}"
                                   class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>
                        <div class="md:col-span-4 flex justify-end gap-2">
                            <button type="button" @click="showForm = false"
                                    class="px-3 py-1.5 border border-gray-300 rounded text-xs font-medium text-gray-700 hover:bg-gray-50">
                                Abbrechen
                            </button>
                            <button type="submit"
                                    class="px-3 py-1.5 bg-indigo-600 rounded text-xs font-medium text-white hover:bg-indigo-700">
                                Anlegen
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Liste --}}
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vorschau</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Farbe</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Schulen</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sortierung</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($schulTypen as $typ)
                            <tr x-data="{ editing: false }" class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-gray-800" x-show="!editing">
                                    {{ $typ->name }}
                                </td>
                                <td class="px-6 py-3" x-show="!editing">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $typ->farbe_klassen }}">
                                        {{ $typ->name }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-xs text-gray-400" x-show="!editing">
                                    {{ \App\Modules\Schulen\Models\SchulTyp::FARBEN[$typ->farbe_klassen] ?? $typ->farbe_klassen }}
                                </td>
                                <td class="px-6 py-3 text-right text-gray-600" x-show="!editing">
                                    {{ $typ->schulen()->count() }}
                                </td>
                                <td class="px-6 py-3 text-right text-gray-600" x-show="!editing">
                                    {{ $typ->sort_order }}
                                </td>
                                <td class="px-6 py-3 text-right" x-show="!editing">
                                    <button @click="editing = true"
                                            class="text-xs text-indigo-600 hover:text-indigo-800 mr-2">Bearbeiten</button>
                                    <form action="{{ route('schulen.einstellungen.typen.destroy', $typ) }}" method="POST"
                                          class="inline"
                                          onsubmit="return confirm('Schultyp \'{{ addslashes($typ->name) }}\' löschen?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Löschen</button>
                                    </form>
                                </td>

                                {{-- Bearbeiten-Zeile --}}
                                <td colspan="6" class="px-6 py-3" x-show="editing" x-transition>
                                    <form action="{{ route('schulen.einstellungen.typen.update', $typ) }}" method="POST"
                                          class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                                        @csrf @method('PUT')
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Name *</label>
                                            <input type="text" name="name" value="{{ $typ->name }}" required
                                                   class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Farbe</label>
                                            <select name="farbe_klassen"
                                                    class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                                @foreach ($farbenOptionen as $klasse => $label)
                                                    <option value="{{ $klasse }}" @selected($typ->farbe_klassen === $klasse)>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Sortierung</label>
                                            <input type="number" name="sort_order" value="{{ $typ->sort_order }}"
                                                   class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                        </div>
                                        <div class="md:col-span-4 flex justify-end gap-2">
                                            <button type="button" @click="editing = false"
                                                    class="px-3 py-1.5 border border-gray-300 rounded text-xs font-medium text-gray-700 hover:bg-gray-50">
                                                Abbrechen
                                            </button>
                                            <button type="submit"
                                                    class="px-3 py-1.5 bg-indigo-600 rounded text-xs font-medium text-white hover:bg-indigo-700">
                                                Speichern
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-400 text-sm">
                                    Noch keine Schultypen vorhanden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ── Dienstleistungs-Kategorien ─────────────────────────────────── --}}
            <div x-data="{ showForm: false }" class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Dienstleistungs-Kategorien</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Gruppierung der Dienstleistungen in Matrix und Liste</p>
                    </div>
                    <button @click="showForm = !showForm"
                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded-md text-xs font-medium text-white hover:bg-indigo-700">
                        + Neue Kategorie
                    </button>
                </div>

                {{-- Neu-Formular --}}
                <div x-show="showForm" x-transition class="px-6 py-4 border-b border-gray-100 bg-indigo-50">
                    <form action="{{ route('schulen.einstellungen.kategorien.store') }}" method="POST"
                          class="flex gap-3 items-end">
                        @csrf
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Name *</label>
                            <input type="text" name="name" required placeholder="z.B. Sicherheit"
                                   class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>
                        <div class="w-28">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Sortierung</label>
                            <input type="number" name="sort_order" value="{{ $kategorien->count() + 1 }}"
                                   class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>
                        <button type="submit"
                                class="px-3 py-2 bg-indigo-600 rounded-md text-xs font-medium text-white hover:bg-indigo-700">
                            Anlegen
                        </button>
                        <button type="button" @click="showForm = false"
                                class="px-3 py-2 border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                            Abbrechen
                        </button>
                    </form>
                </div>

                {{-- Liste --}}
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategorie</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Dienstleistungen</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sortierung</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($kategorien as $kat)
                            <tr x-data="{ editing: false }" class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-gray-800" x-show="!editing">
                                    {{ $kat->name }}
                                </td>
                                <td class="px-6 py-3 text-right text-gray-600" x-show="!editing">
                                    {{ $kat->dienstleistungen()->count() }}
                                </td>
                                <td class="px-6 py-3 text-right text-gray-600" x-show="!editing">
                                    {{ $kat->sort_order }}
                                </td>
                                <td class="px-6 py-3 text-right" x-show="!editing">
                                    <button @click="editing = true"
                                            class="text-xs text-indigo-600 hover:text-indigo-800 mr-2">Bearbeiten</button>
                                    <form action="{{ route('schulen.einstellungen.kategorien.destroy', $kat) }}" method="POST"
                                          class="inline"
                                          onsubmit="return confirm('Kategorie \'{{ addslashes($kat->name) }}\' löschen?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Löschen</button>
                                    </form>
                                </td>

                                {{-- Bearbeiten-Zeile --}}
                                <td colspan="4" class="px-6 py-3" x-show="editing" x-transition>
                                    <form action="{{ route('schulen.einstellungen.kategorien.update', $kat) }}" method="POST"
                                          class="flex gap-3 items-end">
                                        @csrf @method('PUT')
                                        <div class="flex-1">
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Name *</label>
                                            <input type="text" name="name" value="{{ $kat->name }}" required
                                                   class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                        </div>
                                        <div class="w-28">
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Sortierung</label>
                                            <input type="number" name="sort_order" value="{{ $kat->sort_order }}"
                                                   class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                        </div>
                                        <button type="submit"
                                                class="px-3 py-2 bg-indigo-600 rounded-md text-xs font-medium text-white hover:bg-indigo-700">
                                            Speichern
                                        </button>
                                        <button type="button" @click="editing = false"
                                                class="px-3 py-2 border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                                            Abbrechen
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-400 text-sm">
                                    Noch keine Kategorien vorhanden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
