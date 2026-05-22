<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('schulen.matrix') }}" class="text-gray-400 hover:text-gray-600">← Matrix</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dienstleistungen</h2>
            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('schulen.export', ['dienstleistungen', 'pdf']) }}"
                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    PDF
                </a>
                <a href="{{ route('schulen.export', ['dienstleistungen', 'docx']) }}"
                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Word
                </a>
                @can('schulen.edit')
                <a href="{{ route('schulen.dienste.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    + Dienstleistung anlegen
                </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                     class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Kategorien-Verwaltung --}}
            @can('schulen.edit')
            <div x-data="{ showKatForm: false }" class="bg-white shadow-sm sm:rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-gray-600">Kategorien verwalten</span>
                    <button @click="showKatForm = !showKatForm"
                            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                        + Neue Kategorie
                    </button>
                </div>
                <div x-show="showKatForm" x-transition class="mt-3">
                    <form action="{{ route('schulen.kategorien.store') }}" method="POST" class="flex gap-3 items-end">
                        @csrf
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Name *</label>
                            <input type="text" name="name" required
                                   class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>
                        <div class="w-24">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Sortierung</label>
                            <input type="number" name="sort_order" value="0"
                                   class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>
                        <button type="submit"
                                class="px-3 py-2 bg-indigo-600 rounded-md text-xs font-medium text-white hover:bg-indigo-700">
                            Anlegen
                        </button>
                    </form>
                </div>
            </div>
            @endcan

            {{-- Pro Kategorie --}}
            @foreach ($kategorien as $kat)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-3 border-b border-gray-100 bg-indigo-50 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-indigo-700">{{ $kat->name }}</h3>
                        @can('schulen.edit')
                            <div x-data="{ editing: false }" class="flex gap-2 items-center">
                                <button @click="editing = !editing"
                                        class="text-xs text-gray-500 hover:text-gray-700">Umbenennen</button>
                                <div x-show="editing" x-transition>
                                    <form action="{{ route('schulen.kategorien.update', $kat) }}" method="POST" class="flex gap-2 items-center">
                                        @csrf @method('PUT')
                                        <input type="text" name="name" value="{{ $kat->name }}"
                                               class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs px-2 py-1">
                                        <button type="submit" class="text-xs text-indigo-600 hover:underline">OK</button>
                                        <button type="button" @click="editing = false" class="text-xs text-gray-400 hover:text-gray-600">×</button>
                                    </form>
                                </div>
                                @if ($kat->dienstleistungen->isEmpty())
                                    <form action="{{ route('schulen.kategorien.destroy', $kat) }}" method="POST"
                                          onsubmit="return confirm('Kategorie {{ addslashes($kat->name) }} löschen?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Löschen</button>
                                    </form>
                                @endif
                            </div>
                        @endcan
                    </div>
                    @if ($kat->dienstleistungen->isNotEmpty())
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($kat->dienstleistungen as $dienst)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-2 font-medium text-gray-800">
                                            <a href="{{ route('schulen.dienste.show', $dienst) }}" class="hover:text-indigo-600">
                                                {{ $dienst->name }}
                                            </a>
                                            @if (!$dienst->is_active)
                                                <span class="ml-1 text-xs text-gray-400">(inaktiv)</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-2 text-gray-500">
                                            @if ($dienst->beschreibung)
                                                <span class="text-xs text-gray-400 truncate max-w-xs inline-block" title="{{ $dienst->beschreibung }}">
                                                    {{ Str::limit($dienst->beschreibung, 60) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-2 text-right text-gray-600 text-xs">
                                            @if ($dienst->jahresstunden() !== null)
                                                {{ number_format($dienst->jahresstunden(), 1, ',', '.') }} h/Jahr
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-6 py-2 text-right">
                                            @if ($dienst->dokumentation_url)
                                                <a href="{{ $dienst->dokumentation_url }}" target="_blank" rel="noopener"
                                                   class="text-xs text-indigo-600 hover:underline mr-2" title="Dokumentation öffnen">
                                                    📖 Doku
                                                </a>
                                            @endif
                                            @can('schulen.edit')
                                                <a href="{{ route('schulen.dienste.edit', $dienst) }}"
                                                   class="text-xs text-gray-500 hover:text-gray-700 mr-2">Bearbeiten</a>
                                                <form action="{{ route('schulen.dienste.destroy', $dienst) }}" method="POST" class="inline"
                                                      onsubmit="return confirm('Dienstleistung {{ addslashes($dienst->name) }} löschen?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-xs text-red-500 hover:text-red-700">Löschen</button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="px-6 py-3 text-sm text-gray-400">Keine Dienstleistungen in dieser Kategorie.</div>
                    @endif
                </div>
            @endforeach

            {{-- Ohne Kategorie --}}
            @if ($ohneKategorie->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-3 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-500">Ohne Kategorie</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($ohneKategorie as $dienst)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-2 font-medium text-gray-800">
                                        <a href="{{ route('schulen.dienste.show', $dienst) }}" class="hover:text-indigo-600">
                                            {{ $dienst->name }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-2 text-right text-gray-600 text-xs">
                                        {{ $dienst->jahresstunden() !== null ? number_format($dienst->jahresstunden(), 1, ',', '.') . ' h/Jahr' : '—' }}
                                    </td>
                                    <td class="px-6 py-2 text-right">
                                        @can('schulen.edit')
                                            <a href="{{ route('schulen.dienste.edit', $dienst) }}"
                                               class="text-xs text-gray-500 hover:text-gray-700 mr-2">Bearbeiten</a>
                                            <form action="{{ route('schulen.dienste.destroy', $dienst) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Löschen?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-xs text-red-500 hover:text-red-700">Löschen</button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($kategorien->isEmpty() && $ohneKategorie->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                    Noch keine Dienstleistungen angelegt.
                    @can('schulen.edit')
                        <a href="{{ route('schulen.dienste.create') }}" class="text-indigo-600 hover:underline ml-1">Jetzt anlegen →</a>
                    @endcan
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
