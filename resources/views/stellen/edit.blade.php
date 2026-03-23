<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('stellen.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-xl font-semibold text-gray-800">Stelle bearbeiten: {{ $stelle->stellennummer }}</h2>
        </div>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('success'))
            <div class="p-4 bg-green-50 border border-green-200 rounded-md text-sm text-green-700">{{ session('success') }}</div>
        @endif

        <div class="bg-white shadow rounded-lg">
            <form action="{{ route('stellen.update', $stelle) }}" method="POST">
                @csrf @method('PUT')
                @include('stellen._form')
            </form>
        </div>

        {{-- Stellenbeschreibung / Arbeitsvorgänge (Info-Box) --}}
        @if($stelle->stellenbeschreibung)
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Stellenbeschreibung: {{ $stelle->stellenbeschreibung->bezeichnung }}</h3>
                    @php $gesamt = $stelle->stellenbeschreibung->gesamtanteil(); @endphp
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $stelle->stellenbeschreibung->arbeitsvorgaenge->count() }} Arbeitsvorgang/-vorgänge –
                        <span class="{{ $gesamt === 100 ? 'text-green-600' : 'text-red-600' }} font-medium">{{ $gesamt }}% Gesamt</span>
                    </p>
                </div>
                <a href="{{ route('stellenbeschreibungen.edit', $stelle->stellenbeschreibung) }}"
                   class="inline-flex items-center px-3 py-2 text-sm text-indigo-600 hover:text-indigo-800 border border-indigo-200 hover:border-indigo-400 rounded">
                    Stellenbeschreibung bearbeiten ↗
                </a>
            </div>
        </div>
        @endif

        @can('base.stellen.edit')
        <div class="bg-white shadow rounded-lg p-6 border border-red-100">
            <h3 class="text-sm font-semibold text-red-700 mb-1">Stelle löschen</h3>
            <p class="text-sm text-gray-500 mb-4">Diese Stelle wird unwiderruflich gelöscht.</p>
            <form action="{{ route('stellen.destroy', $stelle) }}" method="POST"
                  onsubmit="return confirm('Stelle {{ addslashes($stelle->stellennummer) }} wirklich löschen?')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 text-sm text-white bg-red-600 rounded-md hover:bg-red-700">
                    Stelle löschen
                </button>
            </form>
        </div>
        @endcan

    </div>
</x-app-layout>
