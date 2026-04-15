@php
    $ticketsSettings = \App\Modules\Tickets\Models\TicketsSettings::getSingleton();
    $showTickets = $ticketsSettings->isConfigured();
    $myTickets = collect();
    $zammadUrl = '';
    if ($showTickets) {
        $zammadUrl = rtrim($ticketsSettings->url, '/');
        $myTickets = (new \App\Modules\Tickets\Services\ZammadService())
            ->getTicketsForUser(auth()->user()->email);
    }
@endphp

@if($showTickets)
<div class="bg-white shadow rounded-xl overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
            </svg>
            <h3 class="text-sm font-semibold text-gray-800">Meine Tickets</h3>
            @if($myTickets->isNotEmpty())
                <span class="text-xs text-gray-400">({{ $myTickets->count() }})</span>
            @endif
        </div>
        <a href="{{ route('tickets.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Alle ansehen →</a>
    </div>

    @if($myTickets->isEmpty())
        <div class="px-6 py-8 text-sm text-gray-400 text-center">Keine zugewiesenen Tickets.</div>
    @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nr.</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Titel</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Priorität</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Letzte Änderung</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($myTickets->take(10) as $ticket)
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
                    <td class="px-4 py-2.5 font-mono text-xs whitespace-nowrap">
                        <a href="{{ $zammadUrl }}/#ticket/zoom/{{ $ticket['id'] }}"
                           target="_blank" rel="noopener"
                           class="text-indigo-600 hover:text-indigo-800 hover:underline">
                            #{{ $ticket['number'] }}
                        </a>
                    </td>
                    <td class="px-4 py-2.5 text-gray-800 max-w-xs truncate" title="{{ $ticket['title'] }}">
                        {{ \Illuminate\Support\Str::limit($ticket['title'], 40) }}
                    </td>
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                            {{ $ticket['state'] }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-xs text-gray-600 whitespace-nowrap">{{ $ticket['priority'] }}</td>
                    <td class="px-4 py-2.5 text-xs text-gray-500 whitespace-nowrap">
                        {{ $ticket['updated_at'] ? \Carbon\Carbon::parse($ticket['updated_at'])->format('d.m.Y H:i') : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($myTickets->count() > 10)
            <div class="px-4 py-2 border-t border-gray-100 text-xs text-gray-400 text-center">
                {{ $myTickets->count() - 10 }} weitere Tickets &middot;
                <a href="{{ route('tickets.index') }}" class="text-indigo-600 hover:underline">Alle ansehen</a>
            </div>
        @endif
    @endif
</div>
@endif
