@php
    $paddingLeft = 16 + $depth * 24;
    $isParent = $aufgabe->children->count() > 0;
@endphp

@if($aufgabe->zuweisungen->isEmpty())
    {{-- Row without assignment --}}
    <tr class="{{ $isParent ? 'bg-gray-50 font-medium' : '' }} hover:bg-blue-50">
        <td class="px-4 py-2" style="padding-left: {{ $paddingLeft }}px">
            <div class="flex items-center gap-2">
                @if($isParent)
                    <span class="text-gray-400 text-xs">▼</span>
                @else
                    <span class="text-gray-200 text-xs">—</span>
                @endif
                <span class="{{ $isParent ? 'font-semibold text-gray-800' : 'text-gray-700' }}">
                    {{ $aufgabe->name }}
                </span>
            </div>
        </td>
        <td class="px-4 py-2 text-gray-400">—</td>
        <td class="px-4 py-2 text-gray-400">—</td>
        <td class="px-4 py-2 text-gray-400">—</td>
        <td class="px-4 py-2 text-right whitespace-nowrap">
            @can('base.aufgaben.create')
                <a href="{{ route('aufgaben.create', ['parent_id' => $aufgabe->id]) }}"
                   class="text-green-600 hover:text-green-800 text-xs font-medium mr-2">+ Unteraufgabe</a>
            @endcan
            <a href="{{ route('aufgaben.show', $aufgabe) }}"
               class="text-gray-500 hover:text-gray-700 text-xs font-medium mr-2">Ansicht</a>
            @can('base.aufgaben.edit')
                <a href="{{ route('aufgaben.edit', $aufgabe) }}"
                   class="text-indigo-600 hover:text-indigo-800 text-xs font-medium mr-2">Bearbeiten</a>
            @endcan
            @can('base.aufgaben.delete')
                <button @click="deleteId = {{ $aufgabe->id }}; deleteName = '{{ addslashes($aufgabe->name) }}'"
                        class="text-red-600 hover:text-red-800 text-xs font-medium">Löschen</button>
            @endcan
        </td>
    </tr>
@else
    {{-- One row per assignment --}}
    @foreach($aufgabe->zuweisungen as $i => $zuweisung)
        <tr class="{{ $isParent ? 'bg-gray-50' : '' }} hover:bg-blue-50">
            <td class="px-4 py-2" style="padding-left: {{ $paddingLeft }}px">
                @if($i === 0)
                    <div class="flex items-center gap-2">
                        @if($isParent)
                            <span class="text-gray-400 text-xs">▼</span>
                        @else
                            <span class="text-gray-200 text-xs">—</span>
                        @endif
                        <span class="{{ $isParent ? 'font-semibold text-gray-800' : 'text-gray-700' }}">
                            {{ $aufgabe->name }}
                        </span>
                    </div>
                @endif
            </td>
            <td class="px-4 py-2 text-gray-700">{{ $zuweisung->gruppe?->name ?? '—' }}</td>
            <td class="px-4 py-2 text-gray-700">{{ $zuweisung->admin?->name ?? '—' }}</td>
            <td class="px-4 py-2 text-gray-500">{{ $zuweisung->stellvertreter?->name ?? '—' }}</td>
            <td class="px-4 py-2 text-right whitespace-nowrap">
                @if($i === 0)
                    @can('base.aufgaben.create')
                        <a href="{{ route('aufgaben.create', ['parent_id' => $aufgabe->id]) }}"
                           class="text-green-600 hover:text-green-800 text-xs font-medium mr-2">+ Unteraufgabe</a>
                    @endcan
                    <a href="{{ route('aufgaben.show', $aufgabe) }}"
                       class="text-gray-500 hover:text-gray-700 text-xs font-medium mr-2">Ansicht</a>
                    @can('base.aufgaben.edit')
                        <a href="{{ route('aufgaben.edit', $aufgabe) }}"
                           class="text-indigo-600 hover:text-indigo-800 text-xs font-medium mr-2">Bearbeiten</a>
                    @endcan
                    @can('base.aufgaben.delete')
                        <button @click="deleteId = {{ $aufgabe->id }}; deleteName = '{{ addslashes($aufgabe->name) }}'"
                                class="text-red-600 hover:text-red-800 text-xs font-medium">Löschen</button>
                    @endcan
                @endif
            </td>
        </tr>
    @endforeach
@endif

{{-- Recursive children --}}
@foreach($aufgabe->children as $child)
    @include('aufgaben._table_row', ['aufgabe' => $child, 'depth' => $depth + 1])
@endforeach
