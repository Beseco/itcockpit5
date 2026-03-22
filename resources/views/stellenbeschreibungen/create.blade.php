<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('stellenbeschreibungen.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-xl font-semibold text-gray-800">Neue Stellenbeschreibung</h2>
        </div>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg">
            <form action="{{ route('stellenbeschreibungen.store') }}" method="POST">
                @csrf
                <div class="p-6 space-y-4">
                    @if ($errors->any())
                        <div class="p-4 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">
                            <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <div>
                        <x-input-label for="bezeichnung" value="Bezeichnung *" />
                        <x-text-input id="bezeichnung" name="bezeichnung" type="text" class="mt-1 block w-full"
                            value="{{ old('bezeichnung') }}" required autofocus
                            placeholder="z.B. EDV Systembetreuer" />
                        <p class="mt-1 text-xs text-gray-500">Muss eindeutig sein. Arbeitsvorgänge können nach dem Anlegen hinzugefügt werden.</p>
                        <x-input-error :messages="$errors->get('bezeichnung')" class="mt-1" />
                    </div>
                </div>
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                    <a href="{{ route('stellenbeschreibungen.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Abbrechen</a>
                    <x-primary-button>Anlegen & Arbeitsvorgänge bearbeiten</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
