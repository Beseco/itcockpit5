<x-app-layout>
    @push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
    <style>
        .EasyMDEContainer .CodeMirror { min-height: 400px; font-size: 14px; }
        .editor-toolbar { border-radius: 0; }
        .EasyMDEContainer { border-radius: 0.375rem; overflow: hidden; }
    </style>
    @endpush

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('stellen.edit', $stelle) }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-xl font-semibold text-gray-800">
                {{ $avLabel }} bearbeiten
                <span class="text-gray-400 font-normal text-base">– {{ $stelle->bezeichnung }}</span>
            </h2>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg">
            <form action="{{ route('stellen.av.update', [$stelle, $av]) }}" method="POST">
                @csrf @method('PUT')

                <div class="p-6 space-y-6">

                    @if ($errors->any())
                        <div class="p-4 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Betreff --}}
                    <div>
                        <x-input-label for="betreff" value="Betreff *" />
                        <x-text-input id="betreff" name="betreff" type="text" class="mt-1 block w-full"
                            value="{{ old('betreff', $av->betreff) }}" required autofocus />
                        <x-input-error :messages="$errors->get('betreff')" class="mt-1" />
                    </div>

                    {{-- Anteil --}}
                    <div class="w-40">
                        <x-input-label for="anteil" value="Anteil % *" />
                        <x-text-input id="anteil" name="anteil" type="number" min="0" max="100"
                            class="mt-1 block w-full"
                            value="{{ old('anteil', $av->anteil) }}" required />
                        <x-input-error :messages="$errors->get('anteil')" class="mt-1" />
                    </div>

                    {{-- Beschreibung --}}
                    <div>
                        <x-input-label value="Beschreibung" />
                        <p class="text-xs text-gray-500 mb-2">
                            Unterstützt Markdown: <strong>**fett**</strong>, <em>*kursiv*</em>, Aufzählungen (- ), Überschriften (#)
                        </p>
                        <textarea id="mde-beschreibung" name="beschreibung">{{ old('beschreibung', $av->beschreibung) }}</textarea>
                        <x-input-error :messages="$errors->get('beschreibung')" class="mt-1" />
                    </div>

                </div>

                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                    <a href="{{ route('stellen.edit', $stelle) }}"
                       class="text-sm text-gray-600 hover:text-gray-900">
                        Abbrechen
                    </a>
                    <x-primary-button>Speichern</x-primary-button>
                </div>

            </form>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
    <script>
        new EasyMDE({
            element: document.getElementById('mde-beschreibung'),
            spellChecker: false,
            placeholder: 'Beschreibung des Arbeitsvorgangs...',
            toolbar: [
                'bold', 'italic', '|',
                'unordered-list', 'ordered-list', '|',
                'heading-2', 'heading-3', '|',
                'preview', 'side-by-side', 'fullscreen'
            ],
        });
    </script>
    @endpush

</x-app-layout>
