<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Organigramm-Planer</h2>
            @can('orgchart.edit')
            <a href="{{ route('orgchart.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Neue Version
            </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        @if(session('success'))
            <div class="rounded-md bg-green-50 border border-green-300 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-md bg-red-50 border border-red-300 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        {{-- KPI-Leiste --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            @php
                $statusCounts = $versions->groupBy('status');
                $aktiv = $versions->firstWhere('status', 'aktiv');
            @endphp
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-3 text-center">
                <div class="text-2xl font-bold text-gray-800">{{ $versions->count() }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Versionen gesamt</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-3 text-center">
                <div class="text-2xl font-bold text-green-600">{{ $statusCounts->get('aktiv', collect())->count() }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Aktiv</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-3 text-center">
                <div class="text-2xl font-bold text-yellow-600">{{ $statusCounts->get('abstimmung', collect())->count() + $statusCounts->get('entwurf', collect())->count() }}</div>
                <div class="text-xs text-gray-500 mt-0.5">In Bearbeitung</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-3 text-center">
                <div class="text-2xl font-bold text-slate-400">{{ $statusCounts->get('archiviert', collect())->count() }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Archiviert</div>
            </div>
        </div>

        {{-- Aktive Version Banner --}}
        @if($aktiv)
        <div class="bg-green-50 border border-green-200 rounded-lg px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3 text-sm text-green-800">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Aktive Version: <strong>{{ $aktiv->name }}</strong></span>
            </div>
            <a href="{{ route('orgchart.show', $aktiv) }}"
               class="text-xs text-green-700 underline hover:text-green-900">Grafisch anzeigen →</a>
        </div>
        @endif

        {{-- Versions-Grid --}}
        @if($versions->isEmpty())
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <p class="text-sm">Noch keine Versionen vorhanden.</p>
                @can('orgchart.edit')
                <a href="{{ route('orgchart.create') }}" class="mt-3 inline-block text-sm text-indigo-600 hover:underline">Erste Version anlegen →</a>
                @endcan
            </div>
        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($versions as $v)
            @php
                $statusColor = match($v->status) {
                    'aktiv'      => 'bg-green-100 text-green-800 border-green-200',
                    'abstimmung' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                    'entwurf'    => 'bg-gray-100 text-gray-700 border-gray-200',
                    'archiviert' => 'bg-slate-100 text-slate-600 border-slate-200',
                    default      => 'bg-gray-100 text-gray-700 border-gray-200',
                };
                $statusLabel = \App\Modules\OrgChart\Models\OrgVersion::STATUS_LABELS[$v->status] ?? $v->status;
            @endphp
            <div class="bg-white rounded-lg shadow-sm border {{ $v->status === 'aktiv' ? 'border-green-300 ring-1 ring-green-200' : 'border-gray-200' }} p-4 flex flex-col gap-3"
                 x-data="{ deleteOpen: false }">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h3 class="font-semibold text-gray-900 text-sm leading-tight">{{ $v->name }}</h3>
                        @if($v->description)
                            <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ $v->description }}</p>
                        @endif
                    </div>
                    <span class="shrink-0 px-2 py-0.5 text-xs rounded border {{ $statusColor }}">{{ $statusLabel }}</span>
                </div>

                <div class="flex items-center gap-4 text-xs text-gray-500 border-t border-gray-100 pt-2">
                    <span title="Gruppen"><svg class="w-3.5 h-3.5 inline mr-0.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>{{ $v->group_count_val }} Gruppen</span>
                    <span title="Personalkapazität (FTE)"><svg class="w-3.5 h-3.5 inline mr-0.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>{{ number_format($v->total_headcount_val, 1, ',', '') }} FTE</span>
                    <span class="ml-auto text-gray-400">{{ $v->updated_at->format('d.m.Y') }}</span>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex gap-1">
                        <a href="{{ route('orgchart.show', $v) }}"
                           class="inline-flex items-center justify-center w-8 h-8 text-indigo-600 hover:bg-indigo-50 rounded transition"
                           title="Grafische Ansicht">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </a>
                        @can('orgchart.edit')
                        <a href="{{ route('orgchart.edit', $v) }}"
                           class="inline-flex items-center justify-center w-8 h-8 text-yellow-600 hover:bg-yellow-50 rounded transition"
                           title="Bearbeiten">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>
                        <form action="{{ route('orgchart.duplicate', $v) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center justify-center w-8 h-8 text-blue-600 hover:bg-blue-50 rounded transition"
                                    title="Duplizieren">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                        </form>
                        @if(in_array($v->status, ['entwurf', 'archiviert']))
                        <button @click="deleteOpen = true"
                                class="inline-flex items-center justify-center w-8 h-8 text-red-500 hover:bg-red-50 rounded transition"
                                title="Löschen">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                        @endif
                        @endcan
                    </div>
                    <span class="text-xs text-gray-400">
                        {{ \App\Modules\OrgChart\Models\OrgVersion::COLOR_SCHEMES[$v->color_scheme] ?? $v->color_scheme }}
                    </span>
                </div>

                @can('orgchart.edit')
                @if(in_array($v->status, ['entwurf', 'archiviert']))
                <div x-show="deleteOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" x-transition style="display:none">
                    <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Version löschen?</h3>
                        <p class="text-sm text-gray-600 mb-4">„<strong>{{ $v->name }}</strong>" und alle enthaltenen Daten werden unwiderruflich gelöscht.</p>
                        <div class="flex justify-end gap-3">
                            <button @click="deleteOpen = false" class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-md">Abbrechen</button>
                            <form action="{{ route('orgchart.destroy', $v) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-4 py-2 text-sm bg-red-600 text-white hover:bg-red-700 rounded-md">Löschen</button>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
                @endcan
            </div>
            @endforeach
        </div>
        @endif

    </div>
</x-app-layout>
