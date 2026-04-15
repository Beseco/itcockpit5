@php
    $ticketsSettings = \App\Modules\Tickets\Models\TicketsSettings::getSingleton();
    $counts = ['total' => 0, 'open' => 0, 'pending' => 0];
    if ($ticketsSettings->isConfigured()) {
        $counts = (new \App\Modules\Tickets\Services\ZammadService())
            ->getTicketCount(auth()->user()->email);
    }
    $ticketScore = \App\Modules\Tickets\Models\TicketScore::forUser(auth()->id());
@endphp

<div class="bg-white rounded-lg shadow p-5">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-700">Meine Tickets</h3>
        <a href="{{ route('tickets.index') }}" class="text-xs text-indigo-600 hover:underline">Übersicht →</a>
    </div>
    @if(!$ticketsSettings->isConfigured())
        <div class="text-xs text-gray-400 text-center py-2">Nicht konfiguriert</div>
    @else
        <div class="grid grid-cols-3 gap-3 text-center">
            <div>
                <div class="text-2xl font-bold text-gray-900">{{ $counts['total'] }}</div>
                <div class="text-xs text-gray-500 mt-0.5">gesamt</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-green-600">{{ $counts['open'] }}</div>
                <div class="text-xs text-gray-500 mt-0.5">offen</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-amber-500">{{ $counts['pending'] }}</div>
                <div class="text-xs text-gray-500 mt-0.5">wartend</div>
            </div>
        </div>

        @if($ticketScore)
        @php
            $scoreVal   = (float) $ticketScore->score;
            $greenMax   = (float) $ticketsSettings->score_green_max;
            $redMin     = (float) $ticketsSettings->score_red_min;
            $scoreStyle = $scoreVal <= $greenMax
                ? ['bg' => 'bg-green-50',  'text' => 'text-green-700',  'border' => 'border-green-200',  'label' => 'Gut']
                : ($scoreVal < $redMin
                    ? ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-700', 'border' => 'border-yellow-200', 'label' => 'Erhöht']
                    : ['bg' => 'bg-red-50',    'text' => 'text-red-700',    'border' => 'border-red-200',    'label' => 'Kritisch']);
        @endphp
        <div class="mt-3 p-3 rounded-md border {{ $scoreStyle['bg'] }} {{ $scoreStyle['border'] }} flex items-center justify-between">
            <div>
                <div class="text-xs text-gray-500">Mein Score</div>
                <div class="text-2xl font-bold {{ $scoreStyle['text'] }}">{{ number_format($scoreVal, 1) }}</div>
                <div class="text-xs {{ $scoreStyle['text'] }}">{{ $scoreStyle['label'] }}</div>
            </div>
            <div class="text-right text-xs text-gray-400">
                <div>{{ $ticketScore->yellow_count }} gelb</div>
                <div>{{ $ticketScore->red_count }} rot</div>
                <div class="mt-1">{{ $ticketScore->calculated_at->format('d.m.Y') }}</div>
            </div>
        </div>
        @endif
    @endif
</div>
