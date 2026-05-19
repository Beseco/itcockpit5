<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="text-xl font-semibold text-gray-800">Sicherheitswarnungen (WID-Portal)</h2>
            <div class="flex items-center gap-2">
                @can('wid.config')
                    <form action="{{ route('wid.fetch-now') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center px-3 py-2 bg-indigo-50 text-indigo-700 text-xs font-medium rounded-md hover:bg-indigo-100 border border-indigo-200">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Jetzt abrufen
                        </button>
                    </form>
                    <a href="{{ route('wid.settings') }}"
                       class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Einstellungen
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(!$settings->isConfigured())
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-5 text-sm text-yellow-800">
                Das WID-Modul ist noch nicht konfiguriert.
                @can('wid.config')
                    <a href="{{ route('wid.settings') }}" class="font-medium underline ml-1">Jetzt einrichten →</a>
                @endcan
            </div>
        @endif

        {{-- Filter --}}
        <div class="bg-white shadow rounded-lg p-4">
            <form method="GET" action="{{ route('wid.index') }}" class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-48">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Suche</label>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Name oder Titel…"
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Klassifizierung</label>
                    <select name="classification"
                            class="rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Alle</option>
                        @foreach(\App\Modules\Wid\Models\WidAdvisory::CLASSIFICATIONS as $cls)
                            <option value="{{ $cls }}" {{ $filterClass === $cls ? 'selected' : '' }}>
                                {{ ucfirst($cls) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                        Filtern
                    </button>
                    @if($search || $filterClass)
                        <a href="{{ route('wid.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200">
                            Zurücksetzen
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Klassifizierungs-Chips --}}
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('wid.index', array_merge(request()->except('classification', 'page'), [])) }}"
               class="px-3 py-1 rounded-full text-xs font-medium {{ !$filterClass ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Alle
            </a>
            @foreach(\App\Modules\Wid\Models\WidAdvisory::CLASSIFICATIONS as $cls)
                @php
                    $colors = ['keine' => 'gray', 'niedrig' => 'blue', 'mittel' => 'yellow', 'hoch' => 'orange', 'kritisch' => 'red'];
                    $c = $colors[$cls] ?? 'gray';
                @endphp
                <a href="{{ route('wid.index', array_merge(request()->except('classification', 'page'), ['classification' => $cls])) }}"
                   class="px-3 py-1 rounded-full text-xs font-medium
                       {{ $filterClass === $cls
                           ? "bg-{$c}-600 text-white"
                           : "bg-{$c}-100 text-{$c}-800 hover:bg-{$c}-200" }}">
                    {{ ucfirst($cls) }}
                </a>
            @endforeach
        </div>

        {{-- Tabelle --}}
        <div class="bg-white shadow rounded-lg overflow-hidden">
            @if($advisories->isEmpty())
                <div class="py-12 text-center text-sm text-gray-400">
                    Keine Sicherheitswarnungen gefunden.
                </div>
            @else
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klassifizierung</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titel</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">CVSS</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-28">Veröffentlicht</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Flags</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($advisories as $advisory)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $advisory->getColorClass() }}">
                                        {{ $advisory->classification }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-800 whitespace-nowrap">
                                    {{ $advisory->name }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 max-w-xs">
                                    {{ $advisory->title ?? '–' }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 text-right tabular-nums">
                                    @if($advisory->temporal_score !== null)
                                        <span class="font-medium {{ $advisory->temporal_score >= 7 ? 'text-red-600' : ($advisory->temporal_score >= 4 ? 'text-yellow-600' : 'text-gray-600') }}">
                                            {{ number_format($advisory->temporal_score, 1) }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">–</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                                    {{ $advisory->published?->format('d.m.Y') ?? '–' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($advisory->no_patch)
                                        <span title="Kein Patch verfügbar" class="text-orange-500">⚠</span>
                                    @endif
                                    @if($advisory->exploit)
                                        <span title="Exploit bekannt" class="text-red-600">💣</span>
                                    @endif
                                    @if(!$advisory->no_patch && !$advisory->exploit)
                                        <span class="text-gray-300 text-xs">–</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if($advisories->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">
                        {{ $advisories->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>
</x-app-layout>
