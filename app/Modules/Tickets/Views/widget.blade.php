@php
    $ticketsSettings = \App\Modules\Tickets\Models\TicketsSettings::getSingleton();
    $counts = ['total' => 0, 'open' => 0, 'pending' => 0];
    if ($ticketsSettings->isConfigured()) {
        $counts = (new \App\Modules\Tickets\Services\ZammadService())
            ->getTicketCount(auth()->user()->email);
    }
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
    @endif
</div>
