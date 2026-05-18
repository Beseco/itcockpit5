<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('schulen.index') }}" class="text-gray-400 hover:text-gray-600">← Schulen</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $schule->name }}</h2>
            <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $schule->typFarbe() }}">
                {{ $schule->typLabel() }}
            </span>
            <div class="ml-auto flex gap-2">
                @can('schulen.edit')
                    <a href="{{ route('schulen.edit', $schule) }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        Bearbeiten
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

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

            {{-- Stammdaten --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Stammdaten</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div><span class="font-medium text-gray-500">Name:</span> <span class="ml-2 text-gray-900">{{ $schule->name }}</span></div>
                    <div><span class="font-medium text-gray-500">Typ:</span>
                        <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full {{ $schule->typFarbe() }}">
                            {{ $schule->typLabel() }}
                        </span>
                    </div>
                    @if ($schule->strasse || $schule->ort)
                        <div class="md:col-span-2">
                            <span class="font-medium text-gray-500">Adresse:</span>
                            <span class="ml-2 text-gray-900">{{ $schule->adresse() }}</span>
                        </div>
                    @endif
                    @if ($schule->telefon)
                        <div><span class="font-medium text-gray-500">Telefon:</span>
                            <a href="tel:{{ $schule->telefon }}" class="ml-2 text-indigo-600 hover:underline">{{ $schule->telefon }}</a>
                        </div>
                    @endif
                    @if ($schule->email)
                        <div><span class="font-medium text-gray-500">E-Mail:</span>
                            <a href="mailto:{{ $schule->email }}" class="ml-2 text-indigo-600 hover:underline">{{ $schule->email }}</a>
                        </div>
                    @endif
                    @if ($schule->website)
                        <div class="md:col-span-2"><span class="font-medium text-gray-500">Website:</span>
                            <a href="{{ $schule->website }}" target="_blank" rel="noopener" class="ml-2 text-indigo-600 hover:underline">{{ $schule->website }}</a>
                        </div>
                    @endif
                    @if ($schule->notizen)
                        <div class="md:col-span-2">
                            <span class="font-medium text-gray-500">Notizen:</span>
                            <p class="mt-1 text-gray-900 whitespace-pre-line">{{ $schule->notizen }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Kontakte --}}
            <div x-data="{ showKontaktForm: false, editId: null }" class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">
                        Kontakte ({{ $schule->kontakte->count() }})
                    </h3>
                    @can('schulen.edit')
                        <button @click="showKontaktForm = !showKontaktForm; editId = null"
                                class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                            + Kontakt hinzufügen
                        </button>
                    @endcan
                </div>

                {{-- Kontakt-Hinzufügen-Formular --}}
                @can('schulen.edit')
                <div x-show="showKontaktForm" x-transition class="px-6 py-4 border-b border-gray-100 bg-indigo-50">
                    <form action="{{ route('schulen.kontakte.store', $schule) }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Rolle *</label>
                            <select name="rolle" required
                                    class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                @foreach (\App\Modules\Schulen\Models\SchulenKontakt::ROLLE_LABELS as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Vorname *</label>
                            <input type="text" name="vorname" required
                                   class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Nachname *</label>
                            <input type="text" name="nachname" required
                                   class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Telefon</label>
                            <input type="text" name="telefon"
                                   class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">E-Mail</label>
                            <input type="email" name="email"
                                   class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Notiz</label>
                            <input type="text" name="notizen" maxlength="500"
                                   class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>
                        <div class="md:col-span-3 flex justify-end gap-2 mt-1">
                            <button type="button" @click="showKontaktForm = false"
                                    class="px-3 py-1.5 border border-gray-300 rounded text-xs font-medium text-gray-700 hover:bg-gray-50">
                                Abbrechen
                            </button>
                            <button type="submit"
                                    class="px-3 py-1.5 bg-indigo-600 rounded text-xs font-medium text-white hover:bg-indigo-700">
                                Hinzufügen
                            </button>
                        </div>
                    </form>
                </div>
                @endcan

                @forelse ($schule->kontakte as $kontakt)
                    <div x-data="{ editing: false }" class="px-6 py-3 border-b border-gray-50 last:border-0">
                        <div x-show="!editing" class="flex items-center gap-4 flex-wrap text-sm">
                            <span class="px-2 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-600">
                                {{ $kontakt->rollelabel() }}
                            </span>
                            <span class="font-medium text-gray-900">{{ $kontakt->vollname() }}</span>
                            @if ($kontakt->telefon)
                                <a href="tel:{{ $kontakt->telefon }}" class="text-gray-500 hover:text-indigo-600">
                                    📞 {{ $kontakt->telefon }}
                                </a>
                            @endif
                            @if ($kontakt->email)
                                <a href="mailto:{{ $kontakt->email }}" class="text-gray-500 hover:text-indigo-600">
                                    ✉️ {{ $kontakt->email }}
                                </a>
                            @endif
                            @if ($kontakt->notizen)
                                <span class="text-gray-400 text-xs italic">{{ $kontakt->notizen }}</span>
                            @endif
                            @can('schulen.edit')
                                <div class="ml-auto flex gap-2">
                                    <button @click="editing = true"
                                            class="text-xs text-indigo-600 hover:text-indigo-800">Bearbeiten</button>
                                    <form action="{{ route('schulen.kontakte.destroy', [$schule, $kontakt]) }}" method="POST"
                                          onsubmit="return confirm('Kontakt wirklich löschen?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Löschen</button>
                                    </form>
                                </div>
                            @endcan
                        </div>

                        @can('schulen.edit')
                        <div x-show="editing" x-transition class="mt-2">
                            <form action="{{ route('schulen.kontakte.update', [$schule, $kontakt]) }}" method="POST"
                                  class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                @csrf @method('PUT')
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Rolle</label>
                                    <select name="rolle"
                                            class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                        @foreach (\App\Modules\Schulen\Models\SchulenKontakt::ROLLE_LABELS as $val => $label)
                                            <option value="{{ $val }}" @selected($kontakt->rolle === $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Vorname</label>
                                    <input type="text" name="vorname" value="{{ $kontakt->vorname }}" required
                                           class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Nachname</label>
                                    <input type="text" name="nachname" value="{{ $kontakt->nachname }}" required
                                           class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Telefon</label>
                                    <input type="text" name="telefon" value="{{ $kontakt->telefon }}"
                                           class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">E-Mail</label>
                                    <input type="email" name="email" value="{{ $kontakt->email }}"
                                           class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Notiz</label>
                                    <input type="text" name="notizen" value="{{ $kontakt->notizen }}" maxlength="500"
                                           class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                </div>
                                <div class="md:col-span-3 flex justify-end gap-2">
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
                        </div>
                        @endcan
                    </div>
                @empty
                    <div class="px-6 py-4 text-sm text-gray-400">Noch keine Kontakte hinterlegt.</div>
                @endforelse
            </div>

            {{-- Dienstleistungen dieser Schule --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Dienstleistungen</h3>
                    <a href="{{ route('schulen.matrix') }}" class="text-xs text-indigo-600 hover:text-indigo-800">
                        In Matrix anzeigen →
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dienstleistung</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategorie</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stunden/Jahr</th>
                                @can('schulen.edit')
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aktion</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($dienstleistungen as $dienst)
                                @php
                                    $pivot = $pivotMap->get($dienst->id)?->pivot ?? null;
                                    $status = $pivot?->status ?? 'nicht_vorhanden';
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-2 font-medium text-gray-800">
                                        <a href="{{ route('schulen.dienste.show', $dienst) }}" class="hover:text-indigo-600">
                                            {{ $dienst->name }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-2 text-gray-500">{{ $dienst->kategorie?->name ?? '—' }}</td>
                                    <td class="px-6 py-2 text-center">
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full
                                            {{ \App\Modules\Schulen\Models\SchuleDienstleistung::STATUS_COLORS[$status] }}">
                                            {{ \App\Modules\Schulen\Models\SchuleDienstleistung::STATUS_ICONS[$status] }}
                                            {{ \App\Modules\Schulen\Models\SchuleDienstleistung::STATUS_LABELS[$status] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-2 text-right text-gray-600">
                                        @php $h = $pivot?->stunden_override ?? $dienst->jahresstunden() @endphp
                                        {{ $h !== null ? number_format($h, 1, ',', '.') . ' h' : '—' }}
                                        @if ($pivot?->stunden_override !== null)
                                            <span class="text-xs text-orange-500">(override)</span>
                                        @endif
                                    </td>
                                    @can('schulen.edit')
                                        <td class="px-6 py-2 text-right">
                                            <a href="{{ route('schulen.matrix') }}"
                                               class="text-xs text-indigo-600 hover:text-indigo-800">In Matrix →</a>
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-400">
                                        Noch keine Dienstleistungen angelegt.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Schule löschen --}}
            @can('schulen.delete')
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-red-600 mb-2">Gefahrenzone</h3>
                    <p class="text-sm text-gray-600 mb-4">Das Löschen der Schule entfernt auch alle Kontakte und Matrix-Einträge.</p>
                    <form action="{{ route('schulen.destroy', $schule) }}" method="POST"
                          onsubmit="return confirm('Schule {{ addslashes($schule->name) }} wirklich löschen?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                            Schule löschen
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
