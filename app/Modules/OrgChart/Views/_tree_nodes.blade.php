{{-- Rekursive Baumdarstellung im Editor --}}
@foreach($nodes as $node)
@php
    $children = $allNodes->where('parent_id', $node->id)->sortBy('sort_order');
    $indent   = $depth * 20;
    $typeLabels = \App\Modules\OrgChart\Models\OrgNode::TYPE_LABELS;
    $typeBadge = match($node->type) {
        'top'   => 'bg-purple-100 text-purple-700',
        'staff' => 'bg-green-100 text-green-700',
        'frame' => 'bg-amber-100 text-amber-700',
        'group' => 'bg-blue-100 text-blue-700',
        'task'  => 'bg-gray-100 text-gray-600',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp
<div class="border border-gray-200 rounded-lg mb-2 overflow-hidden" style="margin-left: {{ $indent }}px"
     x-data="{ editOpen: false }">
    <div class="flex items-center gap-2 px-3 py-2 bg-white hover:bg-gray-50">
        {{-- Farb-Indikator --}}
        <div class="w-2.5 h-2.5 rounded-full flex-shrink-0"
             style="background-color: {{ $node->color ?? '#9ca3af' }};"></div>

        {{-- Typ-Badge --}}
        <span class="px-1.5 py-0.5 text-[10px] rounded font-medium {{ $typeBadge }} flex-shrink-0">
            {{ $typeLabels[$node->type] ?? $node->type }}
        </span>

        {{-- Name --}}
        <span class="text-sm text-gray-800 font-medium flex-1 truncate">{{ $node->name }}</span>

        {{-- Headcount --}}
        @if($node->headcount !== null)
            <span class="text-xs text-gray-400 flex-shrink-0">{{ number_format($node->headcount, 1, ',', '') }} FTE</span>
        @endif

        {{-- Aktionen --}}
        <div class="flex items-center gap-1 flex-shrink-0">
            {{-- Hoch --}}
            <form action="{{ route('orgchart.nodes.move-up', [$version, $node]) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="p-1 text-gray-400 hover:text-gray-600 rounded" title="Nach oben">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                    </svg>
                </button>
            </form>
            {{-- Runter --}}
            <form action="{{ route('orgchart.nodes.move-down', [$version, $node]) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="p-1 text-gray-400 hover:text-gray-600 rounded" title="Nach unten">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </form>
            {{-- Bearbeiten --}}
            <button @click="editOpen = !editOpen" type="button"
                    class="p-1 text-yellow-500 hover:text-yellow-700 rounded" title="Bearbeiten">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </button>
            {{-- Löschen --}}
            <form action="{{ route('orgchart.nodes.destroy', [$version, $node]) }}" method="POST" class="inline"
                  onsubmit="return confirm('„{{ addslashes($node->name) }}" und alle {{ $children->count() }} Unterknoten löschen?')">
                @csrf @method('DELETE')
                <button type="submit" class="p-1 text-red-400 hover:text-red-600 rounded" title="Löschen">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    {{-- Bearbeiten-Formular (inline) --}}
    <div x-show="editOpen" x-cloak class="border-t border-gray-200 bg-gray-50 p-4">
        <form action="{{ route('orgchart.nodes.update', [$version, $node]) }}" method="POST"
              class="grid grid-cols-2 gap-3">
            @csrf @method('PUT')
            <div class="col-span-2">
                <x-input-label value="Name *" />
                <x-text-input name="name" type="text" class="mt-1 block w-full"
                              value="{{ $node->name }}" required />
            </div>
            <div>
                <x-input-label value="Typ" />
                <select name="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    @foreach(\App\Modules\OrgChart\Models\OrgNode::TYPE_LABELS as $val => $label)
                        <option value="{{ $val }}" @selected($node->type === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Kapazität (FTE)" />
                <x-text-input name="headcount" type="number" step="0.5" min="0" class="mt-1 block w-full"
                              value="{{ $node->headcount }}" placeholder="z. B. 2.5" />
            </div>
            <div>
                <x-input-label value="Farbe" />
                <div class="mt-1 flex gap-2 items-center">
                    <input type="color" name="color" value="{{ $node->color ?? '#6366f1' }}"
                           class="h-9 w-12 rounded border border-gray-300 cursor-pointer p-0.5">
                </div>
            </div>
            <div>
                <x-input-label value="&nbsp;" />
                <div class="mt-1 flex gap-2 justify-end">
                    <button type="button" @click="editOpen = false"
                            class="px-3 py-1.5 text-xs bg-gray-200 hover:bg-gray-300 rounded-md">Abbrechen</button>
                    <button type="submit"
                            class="px-3 py-1.5 text-xs bg-indigo-600 text-white hover:bg-indigo-700 rounded-md font-semibold">Speichern</button>
                </div>
            </div>
            <div class="col-span-2">
                <x-input-label value="Beschreibung" />
                <textarea name="description" rows="2"
                          class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ $node->description }}</textarea>
            </div>
        </form>
    </div>
</div>

{{-- Rekursion: Kinder --}}
@if($children->isNotEmpty())
    @include('orgchart::_tree_nodes', [
        'nodes'    => $children,
        'allNodes' => $allNodes,
        'version'  => $version,
        'depth'    => $depth + 1,
    ])
@endif
@endforeach
