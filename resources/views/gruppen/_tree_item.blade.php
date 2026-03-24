<div class="flex items-center justify-between px-4 py-3 hover:bg-gray-50"
     style="padding-left: {{ 1 + $depth * 1.5 }}rem">
    <div class="flex items-center gap-3 min-w-0">
        {{-- Indent indicator --}}
        @if($depth > 0)
            <span class="text-gray-300 select-none">{{ str_repeat('└─ ', 1) }}</span>
        @endif

        <div>
            <span class="font-medium text-gray-900">{{ $gruppe->name }}</span>
            <div class="flex items-center gap-2 mt-1 flex-wrap">
                {{-- Roles --}}
                @foreach($gruppe->roles as $role)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">
                        {{ $role->name }}
                    </span>
                @endforeach
                {{-- Vorgesetzter --}}
                @if($gruppe->vorgesetzter)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700">
                        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        {{ $gruppe->vorgesetzter->name }}
                    </span>
                @endif
                {{-- Member count --}}
                @if($gruppe->users->count() > 0)
                    <span class="text-xs text-gray-500">{{ $gruppe->users->count() }} Mitglied(er)</span>
                @endif
                {{-- Children count --}}
                @if($gruppe->children->count() > 0)
                    <span class="text-xs text-gray-400">{{ $gruppe->children->count() }} Untergruppe(n)</span>
                @endif
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2 flex-shrink-0">
        @can('base.gruppen.create')
            <a href="{{ route('gruppen.create', ['parent_id' => $gruppe->id]) }}"
               title="Untergruppe anlegen"
               class="text-xs text-green-600 hover:text-green-800 px-2 py-1 rounded border border-green-200 hover:border-green-400">
                + Untergruppe
            </a>
        @endcan
        @can('base.gruppen.edit')
            <a href="{{ route('gruppen.edit', $gruppe) }}"
               class="text-xs text-indigo-600 hover:text-indigo-800 px-2 py-1 rounded border border-indigo-200 hover:border-indigo-400">
                Bearbeiten
            </a>
        @endcan
        @can('base.gruppen.delete')
            <button @click="deleteId = {{ $gruppe->id }}; deleteName = '{{ addslashes($gruppe->name) }}'"
                    class="text-xs text-red-600 hover:text-red-800 px-2 py-1 rounded border border-red-200 hover:border-red-400">
                Löschen
            </button>
        @endcan
    </div>
</div>

{{-- Recursive children --}}
@foreach($gruppe->children as $child)
    @include('gruppen._tree_item', ['gruppe' => $child->load(['children.children', 'roles', 'users']), 'depth' => $depth + 1])
@endforeach
