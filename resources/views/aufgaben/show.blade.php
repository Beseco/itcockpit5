<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                @if($aufgabe->parent)
                    <p class="text-sm text-gray-500 mb-1">
                        <a href="{{ route('aufgaben.show', $aufgabe->parent) }}" class="hover:underline">{{ $aufgabe->parent->name }}</a>
                        &rsaquo;
                    </p>
                @endif
                <h2 class="text-xl font-semibold text-gray-800">{{ $aufgabe->name }}</h2>
            </div>
            <div class="flex items-center gap-3">
                @can('base.aufgaben.edit')
                    <a href="{{ route('aufgaben.edit', $aufgabe) }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                        Bearbeiten
                    </a>
                @endcan
                <a href="{{ route('aufgaben.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                    Zurück
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Beschreibung --}}
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Beschreibung</h3>
            @if($aufgabe->beschreibung)
                <div id="md-content" class="prose prose-sm max-w-none text-gray-800"></div>
            @else
                <p class="text-gray-400 text-sm italic">Keine Beschreibung vorhanden.</p>
            @endif
        </div>

        {{-- Zuweisungen --}}
        @if($aufgabe->zuweisungen->isNotEmpty())
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Zuweisungen</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Gruppe</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Admin</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stellvertreter</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($aufgabe->zuweisungen as $z)
                        <tr>
                            <td class="px-4 py-2 text-gray-700">{{ $z->gruppe?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ $z->admin?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $z->stellvertreter?->name ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Unteraufgaben --}}
        @if($aufgabe->children->isNotEmpty())
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Unteraufgaben</h3>
            <ul class="divide-y divide-gray-100">
                @foreach($aufgabe->children as $child)
                <li class="py-2 flex items-center justify-between">
                    <a href="{{ route('aufgaben.show', $child) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                        {{ $child->name }}
                    </a>
                    @can('base.aufgaben.edit')
                        <a href="{{ route('aufgaben.edit', $child) }}" class="text-xs text-gray-400 hover:text-gray-600">Bearbeiten</a>
                    @endcan
                </li>
                @endforeach
            </ul>
        </div>
        @endif

    </div>
</x-app-layout>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('md-content');
        if (el) {
            el.innerHTML = marked.parse(@json($aufgabe->beschreibung ?? ''));
        }
    });
</script>
@endpush
