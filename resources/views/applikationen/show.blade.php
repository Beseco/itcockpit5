<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $app->name }}</h2>
            <div class="flex items-center gap-2">
                @can('applikationen.edit')
                <a href="{{ route('applikationen.edit', $app) }}"
                   class="inline-flex items-center px-3 py-2 bg-indigo-50 text-indigo-700 text-xs font-medium rounded-md hover:bg-indigo-100 border border-indigo-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Bearbeiten
                </a>
                @endcan
                <a href="{{ route('applikationen.index') }}"
                   class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Zurück
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        @php
            $revisionFaellig = $app->revision_date && $app->revision_date->isPast();
            $sbDot = ['A' => 'bg-green-500', 'B' => 'bg-yellow-400', 'C' => 'bg-red-500'];
            $sbTxt = ['A' => 'text-green-700', 'B' => 'text-yellow-700', 'C' => 'text-red-700'];
            $sbLabel = ['A' => 'Normal', 'B' => 'Hoch', 'C' => 'Sehr hoch'];
        @endphp

        {{-- Revision-Banner --}}
        @if($revisionFaellig)
        <div class="rounded-md bg-red-50 border border-red-300 px-4 py-3 flex items-center gap-3 text-sm text-red-800">
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>
                <span class="font-semibold">Revision fällig</span> seit {{ $app->revision_date->format('d.m.Y') }}
                ({{ abs($app->revision_date->diffInDays(now())) }} Tage überfällig)
            </span>
            @can('applikationen.edit')
            <a href="{{ route('applikationen.edit', $app) }}" class="ml-auto shrink-0 text-xs underline text-red-700 hover:text-red-900">
                Zur Bearbeitung →
            </a>
            @endcan
        </div>
        @elseif($app->revision_date)
        <div class="rounded-md bg-gray-50 border border-gray-200 px-4 py-3 flex items-center gap-3 text-sm text-gray-600">
            <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Nächste Revision: <span class="font-medium text-gray-800">{{ $app->revision_date->format('d.m.Y') }}</span>
            <span class="text-gray-400">(in {{ $app->revision_date->diffInDays(now()) }} Tagen)</span>
        </div>
        @endif

        {{-- Stammdaten --}}
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Stammdaten</h3>
                @if($app->baustein)
                    <span class="px-2 py-0.5 text-xs font-semibold rounded bg-indigo-100 text-indigo-800">{{ $app->baustein }}</span>
                @endif
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">

                @if($app->einsatzzweck)
                <div class="sm:col-span-2">
                    <div class="text-xs text-gray-500 mb-1">Beschreibung / Einsatzzweck</div>
                    <div class="text-sm text-gray-800 whitespace-pre-wrap">{{ $app->einsatzzweck }}</div>
                </div>
                @endif

                @if($app->hersteller)
                <div>
                    <div class="text-xs text-gray-500 mb-1">Hersteller / Lieferant</div>
                    <div class="text-sm text-gray-800">{{ $app->hersteller }}</div>
                </div>
                @endif

                @if($app->ansprechpartner)
                <div>
                    <div class="text-xs text-gray-500 mb-1">Ansprechpartner (Lieferant)</div>
                    <div class="text-sm text-gray-800">{{ $app->ansprechpartner }}</div>
                </div>
                @endif

                @if($app->doc_url)
                <div class="sm:col-span-2">
                    <div class="text-xs text-gray-500 mb-1">Dokumentation</div>
                    <a href="{{ $app->doc_url }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center text-sm text-indigo-600 hover:underline break-all">
                        <svg class="w-3.5 h-3.5 mr-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        {{ $app->doc_url }}
                    </a>
                </div>
                @else
                <div class="sm:col-span-2">
                    <div class="rounded bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-700">
                        Keine Dokumentations-URL hinterlegt. Bitte schnellstmöglich eine Dokumentation anlegen.
                    </div>
                </div>
                @endif

            </div>
        </div>

        {{-- Zuständigkeiten --}}
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Zuständigkeiten</h3>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">

                <div>
                    <div class="text-xs text-gray-500 mb-1">Sachgebiet / Abteilung</div>
                    @if($app->abteilung)
                        <div class="text-sm text-gray-800">{{ $app->abteilung->anzeigename }}</div>
                    @elseif($app->sg)
                        <div class="text-sm text-gray-500 italic">{{ $app->sg }} <span class="text-xs text-red-400">(nicht zugeordnet)</span></div>
                    @else
                        <div class="text-sm text-gray-400">—</div>
                    @endif
                </div>

                <div>
                    <div class="text-xs text-gray-500 mb-1">Verfahrensverantwortlicher</div>
                    @if($app->verantwortlichAdUser)
                        <div class="text-sm text-gray-800">{{ $app->verantwortlichAdUser->anzeigenameOrName }}</div>
                    @elseif($app->verantwortlich_sg)
                        <div class="text-sm text-gray-500 italic">{{ $app->verantwortlich_sg }} <span class="text-xs text-red-400">(nicht zugeordnet)</span></div>
                    @else
                        <div class="text-sm text-gray-400">—</div>
                    @endif
                </div>

                <div>
                    <div class="text-xs text-gray-500 mb-1">IT-Administrator</div>
                    @if($app->adminUser)
                        <div class="text-sm text-gray-800">{{ $app->adminUser->name }}</div>
                    @else
                        <div class="text-sm text-gray-400">—</div>
                    @endif
                </div>

            </div>
        </div>

        {{-- Schutzbedarf --}}
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Schutzbedarf (BSI-Grundschutz)</h3>
            </div>
            <div class="p-5 grid grid-cols-3 gap-4">
                @foreach(['confidentiality' => 'Vertraulichkeit', 'integrity' => 'Integrität', 'availability' => 'Verfügbarkeit'] as $field => $label)
                @php $val = $app->$field; @endphp
                <div class="text-center">
                    <div class="text-xs text-gray-500 mb-2">{{ $label }}</div>
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full text-lg font-bold
                        {{ $val === 'A' ? 'bg-green-100 text-green-700' : ($val === 'B' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                        {{ $val }}
                    </span>
                    <div class="text-xs {{ $sbTxt[$val] ?? 'text-gray-500' }} mt-1">{{ $sbLabel[$val] ?? '—' }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Verknüpfte Server --}}
        @if($app->servers->isNotEmpty())
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Verknüpfte Server ({{ $app->servers->count() }})</h3>
            </div>
            <div class="p-5">
                <div class="flex flex-wrap gap-2">
                    @foreach($app->servers as $server)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 text-gray-700 text-xs border border-gray-200">
                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                        </svg>
                        {{ $server->name }}
                        @if($server->dns_hostname)
                            <span class="text-gray-400 font-mono">{{ $server->dns_hostname }}</span>
                        @endif
                    </span>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Meta --}}
        <div class="text-xs text-gray-400 text-right">
            Zuletzt geändert von {{ $app->updated_by ?? '—' }} &middot; {{ $app->updated_at->format('d.m.Y H:i') }}
        </div>

    </div>
</x-app-layout>
