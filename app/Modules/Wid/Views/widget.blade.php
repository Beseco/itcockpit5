@php
    $widSettings = \App\Modules\Wid\Models\WidSettings::getInstance();
    $widAdvisories = collect();
    if ($widSettings->isConfigured()) {
        $widAdvisories = \App\Modules\Wid\Models\WidAdvisory::query()
            ->aboveMinClassification($widSettings->min_classification)
            ->orderByRaw("FIELD(classification, 'kritisch','hoch','mittel','niedrig','keine')")
            ->orderByDesc('published')
            ->limit(5)
            ->get();
    }
@endphp

<div class="bg-white rounded-lg shadow p-5">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-700">Sicherheitswarnungen (WID)</h3>
        @if($widSettings->isConfigured())
            <a href="{{ route('wid.index') }}" class="text-xs text-indigo-600 hover:underline">Alle anzeigen →</a>
        @endif
    </div>

    @if(!$widSettings->isConfigured())
        <div class="text-xs text-gray-400 text-center py-3">WID nicht konfiguriert</div>
    @elseif($widAdvisories->isEmpty())
        <div class="text-xs text-gray-400 text-center py-3">Keine Warnungen vorhanden</div>
    @else
        <div class="space-y-2">
            @foreach($widAdvisories as $advisory)
                @php $color = $advisory->getColorClass(); @endphp
                <div class="flex items-start gap-2">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $color }} shrink-0 mt-0.5">
                        {{ $advisory->classification }}
                    </span>
                    <div class="min-w-0">
                        <div class="text-xs font-medium text-gray-800 truncate">{{ $advisory->name }}</div>
                        @if($advisory->title)
                            <div class="text-xs text-gray-500 truncate">{{ $advisory->title }}</div>
                        @endif
                    </div>
                    <div class="text-right shrink-0 ml-auto">
                        @if($advisory->status === 'UPDATE')
                            <span class="block text-xs text-amber-600 font-medium">UPDATE</span>
                        @endif
                        <span class="text-xs text-gray-400">{{ $advisory->published?->format('d.m.') }}</span>
                    </div>
                </div>
            @endforeach
        </div>
        @php
            $lastFetch = \App\Modules\Wid\Models\WidAdvisory::max('fetched_at');
        @endphp
        @if($lastFetch)
            <div class="text-right text-xs text-gray-400 mt-2">
                Stand: {{ \Carbon\Carbon::parse($lastFetch)->format('d.m.Y H:i') }}
            </div>
        @endif
    @endif
</div>
