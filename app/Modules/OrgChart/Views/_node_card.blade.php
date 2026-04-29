{{--
    Themenblock-Karte (type=group) für die grafische Ansicht.
    Variablen: $node, $allNodes, $interfacesByNode, $scheme
--}}
@php
    $tasks      = $allNodes->where('parent_id', $node->id)->where('type', 'task')->sortBy('sort_order');
    $subGroups  = $allNodes->where('parent_id', $node->id)->where('type', 'group')->sortBy('sort_order');
    $nodeIfaces = $interfacesByNode->get($node->id, collect());
    $barColor   = $node->color ?? $scheme['group_bar'];
@endphp

<div class="flex flex-col rounded-lg border {{ $scheme['card_border'] }} overflow-hidden min-w-[160px] flex-1">

    {{-- Themenblock-Header --}}
    <div class="px-3 py-1.5 flex items-center justify-between gap-2"
         style="background-color: {{ $barColor }}; border-bottom: 1px solid {{ $barColor }}dd;">
        <span class="text-xs font-bold text-white leading-tight drop-shadow-sm">{{ $node->name }}</span>
        @if($node->headcount !== null && $node->headcount > 0)
            <span class="shrink-0 text-[10px] font-semibold bg-white/25 text-white px-1.5 py-0.5 rounded"
                  title="Personalkapazität">
                {{ number_format($node->headcount, 1, ',', '') }}
            </span>
        @endif
    </div>

    {{-- Aufgaben-Liste --}}
    <div class="{{ $scheme['task_bg'] }} flex-1 divide-y divide-gray-100">
        @forelse($tasks as $task)
        @php $taskColor = $task->color ?? null; @endphp
        <div class="px-2.5 py-1.5 flex items-start gap-2 {{ $taskColor ? '' : 'hover:bg-gray-50' }}"
             @if($taskColor) style="background-color: {{ $taskColor }}18;" @endif>
            <span class="mt-1 w-1.5 h-1.5 rounded-full flex-shrink-0"
                  style="background-color: {{ $taskColor ?? $barColor }};"></span>
            <div class="flex-1 min-w-0">
                <span class="text-xs text-gray-800 leading-tight block">{{ $task->name }}</span>
                @if($task->description)
                    <span class="text-[10px] text-gray-400 leading-tight block mt-0.5">{{ $task->description }}</span>
                @endif
            </div>
            @if($task->headcount && $task->headcount > 0)
                <span class="text-[10px] text-gray-400 shrink-0 font-mono">{{ number_format($task->headcount, 1, ',', '') }}</span>
            @endif
        </div>
        @empty
        <div class="px-2.5 py-2 text-[10px] text-gray-300 italic">Keine Aufgaben</div>
        @endforelse
    </div>

    {{-- Untergruppen (rekursiv, falls vorhanden) --}}
    @if($subGroups->isNotEmpty())
    <div class="p-2 {{ $scheme['subgroup_bg'] }} border-t border-gray-100 flex flex-wrap gap-2">
        @foreach($subGroups as $sub)
            @include('orgchart::_node_card', ['node' => $sub])
        @endforeach
    </div>
    @endif

    {{-- Schnittstellen-Tags --}}
    @if($nodeIfaces->isNotEmpty())
    <div class="px-2.5 py-1.5 border-t border-blue-100 bg-blue-50/60 flex flex-wrap gap-1">
        @foreach($nodeIfaces as $iface)
        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] bg-white text-blue-700 border border-blue-200 shadow-sm"
              title="{{ $iface->description ?? '' }}">
            <svg class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            {{ $iface->toNode->name }}: {{ $iface->label }}
        </span>
        @endforeach
    </div>
    @endif

</div>
