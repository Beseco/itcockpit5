{{--
    Node-Karte für die grafische Ansicht.
    Variablen: $node (OrgNode), $allNodes (Collection), $interfacesByNode (Collection), $scheme (array)
--}}
@php
    $children  = $allNodes->where('parent_id', $node->id)->where('type', 'group')->sortBy('sort_order');
    $tasks     = $allNodes->where('parent_id', $node->id)->where('type', 'task')->sortBy('sort_order');
    $nodeIfaces = $interfacesByNode->get($node->id, collect());
    $barColor  = $node->color ?? $scheme['group_bar'];
    $isEmpty   = ($node->headcount === null || $node->headcount == 0) && $node->type !== 'task';
@endphp

<div class="rounded-lg border {{ $scheme['card_border'] }} overflow-hidden shadow-sm min-w-[160px] flex-shrink-0"
     style="{{ $node->color ? 'border-left: 3px solid ' . $node->color . ';' : '' }}">

    {{-- Header --}}
    <div class="px-3 py-2 flex items-center justify-between gap-2"
         style="background-color: {{ $barColor }}20; border-bottom: 2px solid {{ $barColor }};">
        <span class="text-xs font-semibold text-gray-800 leading-tight">{{ $node->name }}</span>
        <div class="flex items-center gap-1 shrink-0">
            @if($isEmpty)
                <span class="inline-flex items-center px-1 py-0.5 rounded text-xs bg-gray-100 text-gray-400 border border-gray-200"
                      title="Keine Kapazität hinterlegt">—</span>
            @elseif($node->headcount !== null)
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-white border"
                      style="color: {{ $barColor }}; border-color: {{ $barColor }}40;"
                      title="Personalkapazität">
                    {{ number_format($node->headcount, 1, ',', '') }}
                </span>
            @endif
        </div>
    </div>

    {{-- Aufgaben --}}
    @if($tasks->isNotEmpty())
    <div class="{{ $scheme['task_bg'] }} px-3 py-2 space-y-0.5">
        @foreach($tasks as $task)
        <div class="flex items-start gap-1.5 text-xs text-gray-700">
            <span class="mt-0.5 w-1.5 h-1.5 rounded-full flex-shrink-0"
                  style="background-color: {{ $task->color ?? $barColor }};"></span>
            <span class="leading-tight">{{ $task->name }}</span>
            @if($task->headcount)
                <span class="ml-auto text-gray-400 font-mono text-[10px]">{{ number_format($task->headcount, 1, ',', '') }}</span>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- Untergruppen --}}
    @if($children->isNotEmpty())
    <div class="px-2 py-2 space-y-2 {{ $scheme['subgroup_bg'] }}">
        @foreach($children as $child)
            @include('orgchart::_node_card', ['node' => $child])
        @endforeach
    </div>
    @endif

    {{-- Schnittstellen-Tags --}}
    @if($nodeIfaces->isNotEmpty())
    <div class="px-3 py-2 border-t border-gray-100 flex flex-wrap gap-1">
        @foreach($nodeIfaces as $iface)
        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] bg-blue-50 text-blue-700 border border-blue-200"
              title="{{ $iface->description ?? '' }}">
            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            {{ $iface->toNode->name }}: {{ $iface->label }}
        </span>
        @endforeach
    </div>
    @endif
</div>
