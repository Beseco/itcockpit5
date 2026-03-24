<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Stellenplan</h2>
            @can('base.stellen.edit')
                <a href="{{ route('stellen.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    <svg width="16" height="16" class="mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Neue Stelle
                </a>
            @endcan
        </div>
    </x-slot>

    @php
        $alleStellen = $gruppen->flatMap->stellen;
        $totalStellen = $alleStellen->count();
        $totalFrei = $alleStellen->sum(fn($s) => $s->isFrei() ? 100 : max(0, 100 - ($s->belegung ?? 100)));
        $freiCount = $alleStellen->filter->isFrei()->count();
    @endphp

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4"
         x-data="{ deleteId: null, deleteName: '' }">

        {{-- Zusammenfassung --}}
        <div style="display:flex;align-items:center;gap:12px;font-size:0.875rem;">
            <span style="color:#6b7280;">{{ $totalStellen }} Stellen</span>
            <span style="color:#d1d5db;">·</span>
            <span style="color:#d97706;font-weight:500;">{{ $freiCount }} unbesetzt</span>
            <span style="color:#d1d5db;">·</span>
            <span style="color:#dc2626;font-weight:500;">{{ number_format($totalFrei, 0) }} % freie Kapazität</span>
        </div>

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                 class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-md text-sm">
                {{ session('error') }}
            </div>
        @endif

        @foreach($gruppen as $gruppe)
            @if($gruppe->stellen->isNotEmpty())
            @php
                $gBelegung = $gruppe->stellen->sum(fn($s) => $s->isFrei() ? 0 : ($s->belegung ?? 0));
                $gFrei     = $gruppe->stellen->sum(fn($s) => $s->isFrei() ? 100 : max(0, 100 - ($s->belegung ?? 100)));
                $gCount    = $gruppe->stellen->count();
                $gFreiCount = $gruppe->stellen->filter->isFrei()->count();
            @endphp
            <div class="bg-white shadow rounded-lg overflow-hidden">
                {{-- Gruppenheader --}}
                <div class="px-5 py-3 bg-indigo-50 border-b border-indigo-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-indigo-800">{{ $gruppe->name }}</h3>
                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                        <span>{{ $gCount }} Stellen</span>
                        @if($gFreiCount > 0)
                            <span class="text-gray-300">·</span>
                            <span class="text-amber-600 font-medium">{{ $gFreiCount }} unbesetzt</span>
                        @endif
                        @if($gFrei > 0)
                            <span class="text-gray-300">·</span>
                            <span class="font-medium text-red-500">{{ number_format($gFrei, 0) }} % frei</span>
                        @endif
                    </div>
                </div>

                <table class="w-full text-sm border-collapse">
                    <colgroup>
                        <col style="width:80px">
                        <col style="width:220px">
                        <col style="width:160px">
                        @if($canSeeSensitive)<col style="width:100px">@endif
                        <col style="width:80px">
                        <col style="width:80px">
                        @can('base.stellen.edit')<col style="width:60px">@endcan
                    </colgroup>
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Nr.</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Bezeichnung</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Stelleninhaber</th>
                            @if($canSeeSensitive)
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Bes.-Gr.</th>
                            @endif
                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wide">Belegt</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-red-400 uppercase tracking-wide">Frei</th>
                            @can('base.stellen.edit')<th class="px-3 py-2 w-10"></th>@endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($gruppe->stellen as $stelle)
                        @php
                            $belegt = $stelle->isFrei() ? 0 : ($stelle->belegung ?? 0);
                            $frei   = $stelle->isFrei() ? 100 : max(0, 100 - ($stelle->belegung ?? 100));
                        @endphp
                        <tr class="{{ $stelle->isFrei() ? 'bg-amber-50' : 'hover:bg-gray-50' }}">
                            <td class="px-3 py-2 font-mono text-xs {{ $stelle->isFrei() ? 'text-gray-400' : 'text-gray-600' }}">
                                {{ $stelle->stellennummer }}
                            </td>
                            <td class="px-3 py-2 {{ $stelle->isFrei() ? 'text-gray-400' : 'text-gray-900 font-medium' }}"
                                title="{{ $stelle->stellenbeschreibung?->bezeichnung ?? '' }}">
                                {{ \Illuminate\Support\Str::limit($stelle->stellenbeschreibung?->bezeichnung ?? '—', 30) }}
                            </td>
                            <td class="px-3 py-2">
                                @if($stelle->isFrei())
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-700">FREI</span>
                                @else
                                    <span class="text-gray-800 text-xs">{{ $stelle->stelleninhaber->name }}</span>
                                @endif
                            </td>
                            @if($canSeeSensitive)
                            <td class="px-3 py-2 text-xs text-gray-600">{{ $stelle->bes_gruppe ?? '—' }}</td>
                            @endif
                            <td class="px-3 py-2 text-center">
                                @if($belegt > 0)
                                    <span class="text-xs font-medium text-gray-700">{{ number_format($belegt, 0) }} %</span>
                                @else
                                    <span class="text-xs text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center">
                                @if($frei > 0)
                                    <span class="text-xs font-semibold {{ $frei >= 50 ? 'text-red-600' : 'text-amber-600' }}">
                                        {{ number_format($frei, 0) }} %
                                    </span>
                                @else
                                    <span class="text-xs text-gray-300">—</span>
                                @endif
                            </td>
                            @can('base.stellen.edit')
                            <td class="px-3 py-2 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('stellen.edit', $stelle) }}"
                                       class="p-1 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded" title="Bearbeiten">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <button @click="deleteId = {{ $stelle->id }}; deleteName = '{{ addslashes(\Illuminate\Support\Str::limit($stelle->stellenbeschreibung?->bezeichnung ?? $stelle->stellennummer, 30)) }}'"
                                            title="Löschen"
                                            class="p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            @endcan
                        </tr>
                        @endforeach
                    </tbody>
                    {{-- Summenzeile pro Gruppe --}}
                    <tfoot>
                        <tr class="bg-gray-50 border-t-2 border-gray-200">
                            <td colspan="{{ 3 + ($canSeeSensitive ? 1 : 0) }}"
                                class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                Summe
                            </td>
                            <td class="px-3 py-2 text-center text-xs font-bold text-gray-700">
                                {{ number_format($gBelegung, 0) }} %
                            </td>
                            <td class="px-3 py-2 text-center text-xs font-bold {{ $gFrei > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                {{ number_format($gFrei, 0) }} %
                            </td>
                            @can('base.stellen.edit')<td></td>@endcan
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        @endforeach

        {{-- Stellen ohne Gruppe --}}
        @php
            $ohneGruppe = \App\Models\Stelle::whereNull('gruppe_id')
                ->with(['stellenbeschreibung','stelleninhaber'])
                ->orderBy('stellennummer')->get();
            $ogFrei = $ohneGruppe->sum(fn($s) => $s->isFrei() ? 100 : max(0, 100 - ($s->belegung ?? 100)));
        @endphp
        @if($ohneGruppe->isNotEmpty())
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-3 bg-gray-100 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-600">Ohne Gruppe</h3>
                @if($ogFrei > 0)
                    <span class="text-xs font-medium text-red-500">{{ number_format($ogFrei, 0) }} % frei</span>
                @endif
            </div>
            <table class="w-full text-sm border-collapse">
                <colgroup>
                    <col style="width:80px">
                    <col style="width:220px">
                    <col style="width:160px">
                    @if($canSeeSensitive)<col style="width:100px">@endif
                    <col style="width:80px">
                    <col style="width:80px">
                    @can('base.stellen.edit')<col style="width:60px">@endcan
                </colgroup>
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nr.</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Bezeichnung</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stelleninhaber</th>
                        @if($canSeeSensitive)
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Bes.-Gr.</th>
                        @endif
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Belegt</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-red-400 uppercase">Frei</th>
                        @can('base.stellen.edit')<th class="px-3 py-2 w-10"></th>@endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($ohneGruppe as $stelle)
                    @php
                        $belegt = $stelle->isFrei() ? 0 : ($stelle->belegung ?? 0);
                        $frei   = $stelle->isFrei() ? 100 : max(0, 100 - ($stelle->belegung ?? 100));
                    @endphp
                    <tr class="{{ $stelle->isFrei() ? 'bg-amber-50' : 'hover:bg-gray-50' }}">
                        <td class="px-3 py-2 font-mono text-xs text-gray-600">{{ $stelle->stellennummer }}</td>
                        <td class="px-3 py-2 text-gray-900 font-medium" title="{{ $stelle->stellenbeschreibung?->bezeichnung ?? '' }}">
                            {{ \Illuminate\Support\Str::limit($stelle->stellenbeschreibung?->bezeichnung ?? '—', 30) }}
                        </td>
                        <td class="px-3 py-2">
                            @if($stelle->isFrei())
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-700">FREI</span>
                            @else
                                <span class="text-gray-800 text-xs">{{ $stelle->stelleninhaber->name }}</span>
                            @endif
                        </td>
                        @if($canSeeSensitive)
                        <td class="px-3 py-2 text-xs text-gray-600">{{ $stelle->bes_gruppe ?? '—' }}</td>
                        @endif
                        <td class="px-3 py-2 text-center">
                            @if($belegt > 0)
                                <span class="text-xs font-medium text-gray-700">{{ number_format($belegt, 0) }} %</span>
                            @else
                                <span class="text-xs text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            @if($frei > 0)
                                <span class="text-xs font-semibold {{ $frei >= 50 ? 'text-red-600' : 'text-amber-600' }}">{{ number_format($frei, 0) }} %</span>
                            @else
                                <span class="text-xs text-gray-300">—</span>
                            @endif
                        </td>
                        @can('base.stellen.edit')
                        <td class="px-3 py-2 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('stellen.edit', $stelle) }}"
                                   class="p-1 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <button @click="deleteId = {{ $stelle->id }}; deleteName = '{{ addslashes(\Illuminate\Support\Str::limit($stelle->stellenbeschreibung?->bezeichnung ?? $stelle->stellennummer, 30)) }}'"
                                        class="p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                        @endcan
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 border-t-2 border-gray-200">
                        <td colspan="{{ 3 + ($canSeeSensitive ? 1 : 0) }}"
                            class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Summe</td>
                        <td class="px-3 py-2 text-center text-xs font-bold text-gray-700">
                            {{ number_format($ohneGruppe->sum(fn($s) => $s->isFrei() ? 0 : ($s->belegung ?? 0)), 0) }} %
                        </td>
                        <td class="px-3 py-2 text-center text-xs font-bold {{ $ogFrei > 0 ? 'text-red-600' : 'text-gray-400' }}">
                            {{ number_format($ogFrei, 0) }} %
                        </td>
                        @can('base.stellen.edit')<td></td>@endcan
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif

        {{-- Delete Modal --}}
        <div x-show="deleteId !== null" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Stelle löschen</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Soll <strong x-text="deleteName"></strong> wirklich gelöscht werden?
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="deleteId = null; deleteName = ''"
                            class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Abbrechen
                    </button>
                    <form :action="'{{ url('stellen') }}/' + deleteId" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-4 py-2 text-sm text-white bg-red-600 rounded-md hover:bg-red-700">
                            Löschen
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
