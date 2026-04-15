@php
    $freiCount = $gruppe->stellen->filter->isFrei()->count();
    $total = $gruppe->stellen->count();
@endphp

<div class="orgchart-node flex flex-col items-center">
    {{-- Gruppen-Box --}}
    <div class="bg-white border-2 border-indigo-200 rounded-lg shadow-sm w-64 overflow-hidden">
        {{-- Header --}}
        <div class="bg-indigo-50 px-3 py-2 border-b border-indigo-100">
            <div class="text-sm font-semibold text-indigo-800 truncate">{{ $gruppe->name }}</div>
            @if($gruppe->vorgesetzter)
                <div class="text-xs text-indigo-500 mt-0.5 truncate">
                    Leitung: {{ $gruppe->vorgesetzter->name }}
                </div>
            @endif
        </div>

        {{-- Stellen --}}
        <div class="divide-y divide-gray-100">
            @forelse($gruppe->stellen->sortBy('stellennummer') as $stelle)
                <div class="px-3 py-1.5 flex items-center justify-between text-xs gap-2
                            {{ $stelle->isFrei() ? 'bg-amber-50' : '' }}">
                    <div class="truncate min-w-0">
                        <span class="font-mono text-gray-400">{{ $stelle->stellennummer }}</span>
                        <span class="ml-1 {{ $stelle->isFrei() ? 'text-gray-400' : 'text-gray-700' }}">
                            {{ \Illuminate\Support\Str::limit($stelle->stellenbeschreibung?->bezeichnung ?? '—', 18) }}
                        </span>
                    </div>
                    <div class="flex-shrink-0">
                        @if($stelle->isFrei())
                            <span class="inline-flex items-center px-1 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-700">FREI</span>
                        @else
                            <span class="text-gray-500 truncate" style="max-width:80px;display:inline-block;">{{ $stelle->stelleninhaber->name }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-3 py-2 text-xs text-gray-400 italic">Keine Stellen</div>
            @endforelse
        </div>

        {{-- Footer --}}
        @if($total > 0)
        <div class="bg-gray-50 px-3 py-1.5 border-t text-xs text-gray-500 flex items-center justify-between">
            <span>{{ $total }} {{ $total === 1 ? 'Stelle' : 'Stellen' }}</span>
            @if($freiCount > 0)
                <span class="text-amber-600 font-medium">{{ $freiCount }} frei</span>
            @endif
        </div>
        @endif
    </div>

    {{-- Kinder --}}
    @if($gruppe->children->isNotEmpty())
        <div class="orgchart-children flex flex-row items-start gap-8">
            @foreach($gruppe->children as $child)
                @include('stellenplan::_orgchart_node', ['gruppe' => $child])
            @endforeach
        </div>
    @endif
</div>
