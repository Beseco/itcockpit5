<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('feedback.admin.dashboard') }}" class="text-gray-400 hover:text-gray-600 text-sm">← Dashboard</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kommentare</h2>
            <span class="ml-1 text-sm text-gray-400">({{ $comments->total() }})</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Suche --}}
            <form action="{{ route('feedback.admin.comments') }}" method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ $search }}" placeholder="Kommentar suchen…"
                       class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm">
                <button type="submit"
                        class="px-4 py-2 bg-gray-800 text-white text-xs font-semibold rounded-lg hover:bg-gray-700 transition">
                    Suchen
                </button>
                @if($search)
                    <a href="{{ route('feedback.admin.comments') }}"
                       class="px-3 py-2 text-xs text-gray-500 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Zurücksetzen
                    </a>
                @endif
            </form>

            @forelse($comments as $feedback)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <p class="text-sm text-gray-800 leading-relaxed flex-1">{{ $feedback->comment }}</p>
                        <div class="flex-shrink-0 text-right">
                            <span class="text-xs text-gray-400">{{ $feedback->created_at->format('d.m.Y H:i') }}</span>
                            <div class="mt-1 flex justify-end gap-0.5">
                                @php $avg = $feedback->averageScore(); @endphp
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-3 h-3 {{ $i <= round($avg) ? 'text-yellow-400' : 'text-gray-200' }}"
                                         fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                @endfor
                                <span class="text-xs text-gray-400 ml-1">{{ number_format($avg, 1) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center text-gray-400">
                    <p class="text-3xl mb-2">💬</p>
                    <p>{{ $search ? 'Keine Kommentare gefunden.' : 'Noch keine Kommentare vorhanden.' }}</p>
                </div>
            @endforelse

            @if($comments->hasPages())
                <div class="pt-2">{{ $comments->links() }}</div>
            @endif

        </div>
    </div>
</x-app-layout>
