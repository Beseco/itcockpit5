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

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                 class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(!$configured)
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-md text-sm text-amber-800">
                <strong>Nicht konfiguriert.</strong>
                Die Zammad-Verbindung wurde noch nicht eingerichtet.
                @can('module.tickets.config')
                    <a href="{{ route('tickets.settings') }}" class="underline font-medium">Jetzt konfigurieren</a>
                @endcan
            </div>
        @elseif($tickets->isEmpty())
            <div class="bg-white shadow rounded-lg p-8 text-center text-gray-400 text-sm">
                Keine zugewiesenen Tickets gefunden.
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

    </div>
</x-app-layout>
