{{-- Rekursive Baumzeile --}}
<div class="px-4 py-3 flex items-center justify-between hover:bg-gray-50"
     style="padding-left: {{ 1 + $depth * 1.5 }}rem">
    <div class="flex items-center gap-3">
        @if($depth > 0)
            <span class="text-gray-300 select-none">└</span>
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
    <div class="inline-flex items-center gap-1">
        @can('abteilungen.create')
        <a href="{{ route('abteilungen.create', ['parent_id' => $abteilung->id]) }}"
           class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200"
           title="Untergeordnete Abteilung anlegen">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </a>
        @endcan
        @can('abteilungen.edit')
        <a href="{{ route('abteilungen.edit', $abteilung) }}"
           class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200"
           title="Bearbeiten">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
        </a>
        @endcan
        @can('abteilungen.delete')
        <div x-data="{ showDelete: false }">
            <button @click="showDelete = true" type="button"
                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-red-50 text-red-600 hover:bg-red-100 border border-red-200"
                    title="Löschen">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
            <div x-show="showDelete" x-cloak @click.away="showDelete = false"
                 class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
                    <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Abteilung löschen</h3>
                        <p class="text-sm text-gray-500 mb-4">Soll <strong>{{ $abteilung->name }}</strong> wirklich gelöscht werden?</p>
                        <div class="flex justify-end gap-3">
                            <button @click="showDelete = false" type="button"
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Abbrechen</button>
                            <form action="{{ route('abteilungen.destroy', $abteilung) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Löschen</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
    </div>
</div>

@foreach($abteilung->children as $child)
    @include('abteilungen._tree_row', ['abteilung' => $child, 'depth' => $depth + 1])
@endforeach
