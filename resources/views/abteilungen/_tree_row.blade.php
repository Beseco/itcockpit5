{{-- Rekursive Baumzeile --}}
<div class="px-4 py-3 flex items-center justify-between hover:bg-gray-50"
     style="padding-left: {{ 1 + $depth * 1.5 }}rem">
    <div class="flex items-center gap-3">
        @if($depth > 0)
            <span class="text-gray-300 select-none">{{ str_repeat('└', 1) }}</span>
        @endif
        <div>
            <span class="font-medium text-gray-900">{{ $abteilung->name }}</span>
            @if($abteilung->kurzzeichen)
                <span class="ml-2 px-1.5 py-0.5 text-xs font-mono bg-gray-100 text-gray-600 rounded">
                    {{ $abteilung->kurzzeichen }}
                </span>
            @endif
        </div>
    </div>
    <div class="flex items-center gap-3 text-sm">
        @can('abteilungen.create')
        <a href="{{ route('abteilungen.create', ['parent_id' => $abteilung->id]) }}"
           class="text-gray-400 hover:text-indigo-600" title="Untergeordnete Abteilung anlegen">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </a>
        @endcan
        @can('abteilungen.edit')
        <a href="{{ route('abteilungen.edit', $abteilung) }}"
           class="text-indigo-600 hover:text-indigo-900">Bearbeiten</a>
        @endcan
    </div>
</div>

@foreach($abteilung->children as $child)
    @include('abteilungen._tree_row', ['abteilung' => $child, 'depth' => $depth + 1])
@endforeach
