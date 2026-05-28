<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('feedback.admin.dashboard') }}" class="text-gray-400 hover:text-gray-600 text-sm">← Dashboard</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Alle Bewertungen</h2>
            <span class="text-sm text-gray-400">({{ $feedbacks->total() }})</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                     class="bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded-xl text-sm">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Suche --}}
            <form action="{{ route('feedback.admin.index') }}" method="GET" class="flex gap-2">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="dir"  value="{{ $dir }}">
                <input type="text" name="search" value="{{ $search }}" placeholder="Kommentar suchen…"
                       class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm">
                <button type="submit"
                        class="px-4 py-2 bg-gray-800 text-white text-xs font-semibold rounded-lg hover:bg-gray-700 transition">
                    Suchen
                </button>
                @if($search)
                    <a href="{{ route('feedback.admin.index', ['sort' => $sort, 'dir' => $dir]) }}"
                       class="px-3 py-2 text-xs text-gray-500 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Zurücksetzen
                    </a>
                @endif
            </form>

            {{-- Tabelle --}}
            @php
                $smileys = [1 => '😠', 2 => '🙁', 3 => '😐', 4 => '🙂', 5 => '😄'];
                $cols = array_keys($questionLabels);
                $shortLabels = [
                    'q1_overall'         => 'Gesamt',
                    'q2_processing_time' => 'Bearbeit.',
                    'q3_communication'   => 'Komm.',
                    'q4_simplicity'      => 'Unkompli.',
                    'q5_competence'      => 'Kompetenz',
                ];
                $sortLink = fn(string $col) => route('feedback.admin.index', [
                    'sort'   => $col,
                    'dir'    => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc',
                    'search' => $search,
                ]);
                $sortIcon = fn(string $col) => $sort !== $col
                    ? '↕'
                    : ($dir === 'asc' ? '↑' : '↓');
            @endphp

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">
                                    <a href="{{ $sortLink('created_at') }}"
                                       class="flex items-center gap-1 text-xs font-medium text-gray-500 uppercase tracking-wider hover:text-indigo-600">
                                        Datum <span class="text-gray-400">{{ $sortIcon('created_at') }}</span>
                                    </a>
                                </th>
                                @foreach($cols as $col)
                                <th class="px-3 py-3 text-center">
                                    <a href="{{ $sortLink($col) }}"
                                       class="flex items-center justify-center gap-1 text-xs font-medium text-gray-500 uppercase tracking-wider hover:text-indigo-600 whitespace-nowrap">
                                        {{ $shortLabels[$col] ?? $col }}
                                        <span class="text-gray-400">{{ $sortIcon($col) }}</span>
                                    </a>
                                </th>
                                @endforeach
                                <th class="px-4 py-3 text-left">
                                    <a href="{{ $sortLink('created_at') }}"
                                       class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ø
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kommentar</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($feedbacks as $fb)
                                @php $avg = $fb->averageScore(); @endphp
                                <tr class="hover:bg-gray-50 group">
                                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap text-xs">
                                        {{ $fb->created_at->format('d.m.Y') }}<br>
                                        <span class="text-gray-400">{{ $fb->created_at->format('H:i') }}</span>
                                    </td>
                                    @foreach($cols as $col)
                                    <td class="px-3 py-3 text-center">
                                        <span class="text-xl" title="{{ $fb->$col }}/5">{{ $smileys[$fb->$col] ?? '?' }}</span>
                                    </td>
                                    @endforeach
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="font-semibold {{ $avg >= 4 ? 'text-green-600' : ($avg >= 3 ? 'text-yellow-500' : 'text-red-500') }}">
                                            {{ number_format($avg, 1) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 max-w-xs">
                                        @if($fb->comment)
                                            <span class="line-clamp-2 text-xs">{{ $fb->comment }}</span>
                                        @else
                                            <span class="text-gray-300 text-xs italic">–</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <form action="{{ route('feedback.admin.destroy', $fb) }}" method="POST"
                                              onsubmit="return confirm('Bewertung vom {{ $fb->created_at->format('d.m.Y') }} wirklich löschen?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="opacity-0 group-hover:opacity-100 transition-opacity inline-flex items-center gap-1 px-2 py-1 text-xs text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                                Löschen
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($cols) + 4 }}" class="px-6 py-10 text-center text-gray-400">
                                        Keine Bewertungen gefunden.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($feedbacks->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $feedbacks->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
