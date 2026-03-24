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
                @if($aufgabe->beschreibung)
                    <svg title="Hat Beschreibung" width="14" height="14" style="flex-shrink:0;color:#818cf8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                @endif
            </div>
        </td>
        <td class="px-4 py-2 text-gray-400">—</td>
        <td class="px-4 py-2 text-gray-400">—</td>
        <td class="px-4 py-2 text-gray-400">—</td>
        <td class="px-4 py-2 text-right whitespace-nowrap">
            <div class="inline-flex items-center gap-1">
                @can('base.aufgaben.create')
                    <a href="{{ route('aufgaben.create', ['parent_id' => $aufgabe->id]) }}"
                       class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-green-50 text-green-700 hover:bg-green-100 border border-green-200">+ Sub</a>
                @endcan
                <a href="{{ route('aufgaben.show', $aufgabe) }}"
                   class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-600 hover:bg-gray-200 border border-gray-200">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </a>
                @can('base.aufgaben.edit')
                    <a href="{{ route('aufgaben.edit', $aufgabe) }}"
                       class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                @endcan
                @can('base.aufgaben.delete')
                    <button @click="deleteId = {{ $aufgabe->id }}; deleteName = '{{ addslashes($aufgabe->name) }}'"
                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-red-50 text-red-600 hover:bg-red-100 border border-red-200">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                @endcan
            </div>
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
                        @if($aufgabe->beschreibung)
                            <svg title="Hat Beschreibung" width="14" height="14" style="flex-shrink:0;color:#818cf8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        @endif
                    </div>
                @endif
            </td>
            <td class="px-4 py-2 text-gray-700">{{ $zuweisung->gruppe?->name ?? '—' }}</td>
            <td class="px-4 py-2 text-gray-700">{{ $zuweisung->admin?->name ?? '—' }}</td>
            <td class="px-4 py-2 text-gray-500">{{ $zuweisung->stellvertreter?->name ?? '—' }}</td>
            <td class="px-4 py-2 text-right whitespace-nowrap">
                @if($i === 0)
                    <div class="inline-flex items-center gap-1">
                        @can('base.aufgaben.create')
                            <a href="{{ route('aufgaben.create', ['parent_id' => $aufgabe->id]) }}"
                               class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-green-50 text-green-700 hover:bg-green-100 border border-green-200">+ Sub</a>
                        @endcan
                        <a href="{{ route('aufgaben.show', $aufgabe) }}"
                           class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-600 hover:bg-gray-200 border border-gray-200">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </a>
                        @can('base.aufgaben.edit')
                            <a href="{{ route('aufgaben.edit', $aufgabe) }}"
                               class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                        @endcan
                        @can('base.aufgaben.delete')
                            <button @click="deleteId = {{ $aufgabe->id }}; deleteName = '{{ addslashes($aufgabe->name) }}'"
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-red-50 text-red-600 hover:bg-red-100 border border-red-200">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        @endcan
                    </div>
                @endif
            </td>
        </tr>
    @endforeach
@endif

{{-- Recursive children --}}
@foreach($aufgabe->children as $child)
    @include('aufgaben._table_row', ['aufgabe' => $child, 'depth' => $depth + 1])
@endforeach
