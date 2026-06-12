<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('vertragsmanagement.show', $vertrag) }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800">Vertrag bearbeiten: {{ $vertrag->name }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('vertragsmanagement.update', $vertrag) }}" class="space-y-5" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    @include('vertragsmanagement::_form')

                    <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                        <a href="{{ route('vertragsmanagement.show', $vertrag) }}"
                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                            Abbrechen
                        </a>
                        <x-primary-button type="submit">Speichern</x-primary-button>
                    </div>
                </form>
            </div>

            {{-- Vorhandene Dokumente --}}
            @if($vertrag->dokumente->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Vorhandene Dokumente</h3>
                    <ul class="divide-y divide-gray-100">
                        @foreach($vertrag->dokumente as $dok)
                            <li class="py-2 flex items-center justify-between gap-3">
                                <a href="{{ route('vertragsmanagement.dokumente.download', $dok) }}"
                                   class="text-sm text-indigo-600 hover:underline truncate">📄 {{ $dok->dateiname }}</a>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span class="text-xs text-gray-400">{{ $dok->groesse_lesbar }}</span>
                                    <form method="POST" action="{{ route('vertragsmanagement.dokumente.destroy', $dok) }}"
                                          onsubmit="return confirm('Dokument „{{ $dok->dateiname }}“ löschen?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Löschen</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
