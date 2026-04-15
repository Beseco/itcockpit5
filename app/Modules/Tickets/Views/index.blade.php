<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Tickets</h2>
            @can('module.tickets.config')
                <a href="{{ route('tickets.settings') }}"
                   class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Einstellungen
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4"
         x-data="{ showStats: false }">

        @if(!$configured)
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-md text-sm text-amber-800">
                <strong>Nicht konfiguriert.</strong>
                Die Zammad-Verbindung wurde noch nicht eingerichtet.
                @can('module.tickets.config')
                    <a href="{{ route('tickets.settings') }}" class="underline font-medium">Jetzt konfigurieren</a>
                @endcan
            </div>
        @else

        {{-- FILTER-BAR --}}
        <form method="GET" action="{{ route('tickets.index') }}"
              class="bg-white shadow rounded-lg p-4">
            <div class="flex flex-wrap items-end gap-3">
                {{-- Mitarbeiter --}}
                <div class="flex-1 min-w-[180px]">
                    <label for="user" class="block text-xs font-medium text-gray-500 mb-1">Mitarbeiter</label>
                    <select name="user" id="user"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="me" {{ ($filters['user'] ?? 'me') === 'me' ? 'selected' : '' }}>Meine Tickets</option>
                        <option value="all" {{ ($filters['user'] ?? '') === 'all' ? 'selected' : '' }}>Alle Mitarbeiter</option>
                        <option value="unassigned" {{ ($filters['user'] ?? '') === 'unassigned' ? 'selected' : '' }}>Nicht zugewiesen</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ ($filters['user'] ?? '') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div class="min-w-[140px]">
                    <label for="status" class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                    <select name="status" id="status"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="" {{ empty($filters['status'] ?? '') ? 'selected' : '' }}>Alle</option>
                        <option value="new" {{ ($filters['status'] ?? '') === 'new' ? 'selected' : '' }}>Neu</option>
                        <option value="open" {{ ($filters['status'] ?? '') === 'open' ? 'selected' : '' }}>Offen</option>
                        <option value="pending reminder" {{ ($filters['status'] ?? '') === 'pending reminder' ? 'selected' : '' }}>Warten auf Erinnerung</option>
                        <option value="pending close" {{ ($filters['status'] ?? '') === 'pending close' ? 'selected' : '' }}>Warten auf Schließen</option>
                        <option value="closed" {{ ($filters['status'] ?? '') === 'closed' ? 'selected' : '' }}>Geschlossen</option>
                    </select>
                </div>

                {{-- Suche --}}
                <div class="flex-1 min-w-[200px]">
                    <label for="search" class="block text-xs font-medium text-gray-500 mb-1">Suche</label>
                    <input type="text" name="search" id="search"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="Titel, Nummer, ..."
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                {{-- Geschlossene anzeigen --}}
                <div class="flex items-center gap-2 pb-1">
                    <input type="checkbox" name="closed" value="1" id="closed"
                           {{ ($filters['closed'] ?? false) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <label for="closed" class="text-sm text-gray-600">Geschlossene</label>
                </div>

                {{-- Buttons --}}
                <div class="flex items-center gap-2 pb-0.5">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Filtern
                    </button>
                    <a href="{{ route('tickets.index') }}"
                       class="inline-flex items-center px-3 py-2 text-sm text-gray-600 hover:text-gray-800"
                       title="Filter zurücksetzen">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                </div>
            </div>
        </form>

        {{-- ZUSAMMENFASSUNG + STATISTIK-TOGGLE --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3 text-sm">
                <span class="text-gray-500">{{ $tickets->count() }} Tickets</span>
                @if($allTickets->isNotEmpty())
                    <span class="text-gray-300">|</span>
                    <span class="text-gray-400">{{ $allTickets->count() }} gesamt offen (alle Mitarbeiter)</span>
                @endif
            </div>
            <button @click="showStats = !showStats"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-md hover:bg-gray-50 shadow-sm">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span x-text="showStats ? 'Statistik ausblenden' : 'Statistik & Berichte'"></span>
            </button>
        </div>

        {{-- STATISTIK-BEREICH --}}
        <div x-show="showStats" x-cloak x-transition class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                {{-- Pro Mitarbeiter --}}
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-4 py-3 bg-indigo-50 border-b border-indigo-100">
                        <h3 class="text-xs font-semibold text-indigo-800 uppercase tracking-wide">Pro Mitarbeiter</h3>
                    </div>
                    @if($statsByOwner->isEmpty())
                        <div class="p-4 text-xs text-gray-400 text-center">Keine Daten</div>
                    @else
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-1.5 text-left text-gray-500 font-medium">Name</th>
                                    <th class="px-3 py-1.5 text-center text-gray-500 font-medium">Offen</th>
                                    <th class="px-3 py-1.5 text-center text-gray-500 font-medium">Wartend</th>
                                    <th class="px-3 py-1.5 text-center text-gray-500 font-medium">Gesamt</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($statsByOwner as $stat)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-1.5 text-gray-700">{{ $stat['owner'] }}</td>
                                    <td class="px-3 py-1.5 text-center">
                                        <span class="inline-flex items-center justify-center w-6 h-5 rounded-full text-xs font-medium {{ $stat['open'] > 0 ? 'bg-green-100 text-green-700' : 'text-gray-400' }}">
                                            {{ $stat['open'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-1.5 text-center">
                                        <span class="inline-flex items-center justify-center w-6 h-5 rounded-full text-xs font-medium {{ $stat['pending'] > 0 ? 'bg-amber-100 text-amber-700' : 'text-gray-400' }}">
                                            {{ $stat['pending'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-1.5 text-center font-semibold text-gray-700">{{ $stat['total'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                {{-- Pro Gruppe --}}
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-4 py-3 bg-indigo-50 border-b border-indigo-100">
                        <h3 class="text-xs font-semibold text-indigo-800 uppercase tracking-wide">Pro Gruppe</h3>
                    </div>
                    @if($statsByGroup->isEmpty())
                        <div class="p-4 text-xs text-gray-400 text-center">Keine Daten</div>
                    @else
                        <div class="divide-y divide-gray-50">
                            @foreach($statsByGroup as $stat)
                            <div class="px-4 py-2 flex items-center justify-between">
                                <span class="text-xs text-gray-700">{{ $stat['group'] }}</span>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-indigo-400 rounded-full"
                                             style="width: {{ $allTickets->count() > 0 ? round($stat['total'] / $allTickets->count() * 100) : 0 }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600 w-6 text-right">{{ $stat['total'] }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Pro Priorität --}}
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-4 py-3 bg-indigo-50 border-b border-indigo-100">
                        <h3 class="text-xs font-semibold text-indigo-800 uppercase tracking-wide">Pro Priorität</h3>
                    </div>
                    @if($statsByPriority->isEmpty())
                        <div class="p-4 text-xs text-gray-400 text-center">Keine Daten</div>
                    @else
                        <div class="divide-y divide-gray-50">
                            @foreach($statsByPriority as $stat)
                            @php
                                $prio = strtolower($stat['priority']);
                                $prioColor = match(true) {
                                    str_contains($prio, '1') || str_contains($prio, 'low')  => 'bg-blue-400',
                                    str_contains($prio, '3') || str_contains($prio, 'high') => 'bg-red-400',
                                    default => 'bg-amber-400',
                                };
                            @endphp
                            <div class="px-4 py-2 flex items-center justify-between">
                                <span class="text-xs text-gray-700">{{ $stat['priority'] }}</span>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full {{ $prioColor }} rounded-full"
                                             style="width: {{ $allTickets->count() > 0 ? round($stat['total'] / $allTickets->count() * 100) : 0 }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600 w-6 text-right">{{ $stat['total'] }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>
        </div>

        {{-- TICKET-TABELLE --}}
        @if($tickets->isEmpty())
            <div class="bg-white shadow rounded-lg p-8 text-center text-gray-400 text-sm">
                Keine Tickets gefunden.
            </div>
        @else
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Nr.</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Titel</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Priorität</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Gruppe</th>
                            @if(($filters['user'] ?? 'me') !== 'me')
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Besitzer</th>
                            @endif
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Erstellt am</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Wartet bis</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Letzte Änderung</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($tickets as $ticket)
                        @php
                            $state = strtolower($ticket['state'] ?? '');
                            $badgeClass = match(true) {
                                str_contains($state, 'closed'), str_contains($state, 'geschlossen'), str_contains($state, 'merged')
                                    => 'bg-gray-100 text-gray-600',
                                str_contains($state, 'pending'), str_contains($state, 'wartend')
                                    => 'bg-amber-100 text-amber-700',
                                str_contains($state, 'new'), str_contains($state, 'neu')
                                    => 'bg-blue-100 text-blue-700',
                                default => 'bg-green-100 text-green-700',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-mono text-xs text-gray-600 whitespace-nowrap">
                                <a href="{{ $zammadUrl }}/#ticket/zoom/{{ $ticket['id'] }}"
                                   target="_blank" rel="noopener"
                                   class="text-indigo-600 hover:text-indigo-800 hover:underline"
                                   title="In Zammad öffnen">
                                    #{{ $ticket['number'] }}
                                </a>
                            </td>
                            <td class="px-3 py-2 text-gray-800 max-w-xs truncate" title="{{ $ticket['title'] }}">
                                {{ \Illuminate\Support\Str::limit($ticket['title'], 50) }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                    {{ $ticket['state'] }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-600 whitespace-nowrap">{{ $ticket['priority'] }}</td>
                            <td class="px-3 py-2 text-xs text-gray-600 whitespace-nowrap">{{ $ticket['group'] }}</td>
                            @if(($filters['user'] ?? 'me') !== 'me')
                            <td class="px-3 py-2 text-xs text-gray-600 whitespace-nowrap">{{ $ticket['owner'] }}</td>
                            @endif
                            <td class="px-3 py-2 text-xs text-gray-500 whitespace-nowrap">
                                {{ $ticket['created_at'] ? \Carbon\Carbon::parse($ticket['created_at'])->format('d.m.Y H:i') : '—' }}
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500 whitespace-nowrap">
                                {{ $ticket['pending_time'] ? \Carbon\Carbon::parse($ticket['pending_time'])->format('d.m.Y H:i') : '—' }}
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500 whitespace-nowrap">
                                {{ $ticket['updated_at'] ? \Carbon\Carbon::parse($ticket['updated_at'])->format('d.m.Y H:i') : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="text-xs text-gray-400">
                {{ $tickets->count() }} Tickets &middot; Daten werden alle 3 Minuten aktualisiert
            </div>
        @endif

        @endif {{-- configured --}}
    </div>
</x-app-layout>
