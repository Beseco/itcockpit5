<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mitarbeiter-Offboarding</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                     class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filter --}}
            <div class="bg-white shadow-sm sm:rounded-lg mb-4 p-4">
                <form action="{{ route('adusers.offboarding.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-44">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Suche</label>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Name, SAM, Abteilung…"
                               class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                        <select name="filter_status"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle</option>
                            @foreach (\App\Modules\AdUsers\Models\OffboardingRecord::STATUS_LABELS as $val => $label)
                                <option value="{{ $val }}" @selected($filterStatus === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit"
                                class="inline-flex items-center px-3 py-2 bg-indigo-600 rounded-md text-xs font-semibold text-white hover:bg-indigo-700">
                            Filtern
                        </button>
                        @if ($search || $filterStatus)
                            <a href="{{ route('adusers.offboarding.index') }}"
                               class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                Zurücksetzen
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Vorgänge
                            @if ($records->total() > $records->count())
                                <span class="text-sm font-normal text-gray-400">({{ $records->total() }} gesamt)</span>
                            @endif
                        </h3>
                        <a href="{{ route('adusers.offboarding.create') }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white hover:bg-gray-700">
                            + Neuer Vorgang
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mitarbeiter</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Abteilung</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ausscheiden am</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Anleger</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bestätigung</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Dokumente</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($records as $record)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $record->voller_name }}</div>
                                            <div class="text-xs text-gray-400">{{ $record->samaccountname }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $record->abteilung ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ $record->datum_ausscheiden->format('d.m.Y') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full
                                                {{ \App\Modules\AdUsers\Models\OffboardingRecord::STATUS_COLORS[$record->status] }}">
                                                {{ \App\Modules\AdUsers\Models\OffboardingRecord::STATUS_LABELS[$record->status] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $record->anleger_name }}</td>
                                        {{-- Digitale Bestätigung --}}
                                        <td class="px-4 py-3 text-sm">
                                            @if ($record->bestaetigung_erhalten_at)
                                                <span class="text-green-600 text-xs">✓ {{ $record->bestaetigung_erhalten_at->format('d.m.Y') }}</span>
                                            @elseif ($record->bestaetigung_angefragt_at)
                                                <span class="text-blue-500 text-xs">Mail gesendet {{ $record->bestaetigung_angefragt_at->format('d.m.Y') }}</span>
                                            @else
                                                <span class="text-gray-400 text-xs">—</span>
                                            @endif
                                        </td>
                                        {{-- PDF-Icons --}}
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-center gap-2">
                                                @if ($record->personalmeldung_pdf)
                                                    <a href="{{ route('adusers.offboarding.download', [$record, 'personalmeldung']) }}"
                                                       target="_blank"
                                                       title="Personalmeldung herunterladen"
                                                       class="text-indigo-500 hover:text-indigo-700">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                        </svg>
                                                    </a>
                                                @else
                                                    <svg class="w-5 h-5 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Keine Personalmeldung">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                @endif
                                                @if ($record->bestaetigung_pdf)
                                                    <a href="{{ route('adusers.offboarding.download', [$record, 'bestaetigung']) }}"
                                                       target="_blank"
                                                       title="Bestätigung herunterladen"
                                                       class="text-green-500 hover:text-green-700">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                    </a>
                                                @else
                                                    <svg class="w-5 h-5 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Keine Bestätigung">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex justify-end gap-1 items-center">
                                                <a href="{{ route('adusers.offboarding.show', $record) }}"
                                                   class="inline-flex items-center justify-center w-8 h-8 text-indigo-600 hover:bg-indigo-50 rounded transition-colors"
                                                   title="Detail">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-8 text-center text-gray-400">Keine Vorgänge vorhanden.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-between flex-wrap gap-2">
                        <x-per-page-select :per-page="$perPage" />
                        @if ($records->hasPages())
                            <div>{{ $records->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
