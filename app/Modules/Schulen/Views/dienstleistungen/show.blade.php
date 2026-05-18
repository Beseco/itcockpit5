<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('schulen.dienste.index') }}" class="text-gray-400 hover:text-gray-600">← Dienstleistungen</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $dienstleistung->name }}</h2>
            @if (!$dienstleistung->is_active)
                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-500">Inaktiv</span>
            @endif
            @can('schulen.edit')
                <a href="{{ route('schulen.dienste.edit', $dienstleistung) }}"
                   class="ml-auto inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    Bearbeiten
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Details</h3>
                </div>
                <div class="p-6 space-y-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-500">Kategorie:</span>
                        <span class="ml-2 text-gray-900">{{ $dienstleistung->kategorie?->name ?? '—' }}</span>
                    </div>
                    @if ($dienstleistung->beschreibung)
                        <div>
                            <span class="font-medium text-gray-500">Beschreibung:</span>
                            <p class="mt-1 text-gray-900 whitespace-pre-line">{{ $dienstleistung->beschreibung }}</p>
                        </div>
                    @endif
                    @if ($dienstleistung->dokumentation_url)
                        <div>
                            <span class="font-medium text-gray-500">Dokumentation:</span>
                            <a href="{{ $dienstleistung->dokumentation_url }}" target="_blank" rel="noopener"
                               class="ml-2 text-indigo-600 hover:underline break-all">
                                📖 Confluence öffnen →
                            </a>
                        </div>
                    @endif
                    <div>
                        <span class="font-medium text-gray-500">Stundenbedarf:</span>
                        @if ($dienstleistung->stunden_wert !== null)
                            <span class="ml-2 text-gray-900">
                                {{ number_format($dienstleistung->stunden_wert, 1, ',', '.') }}
                                {{ $dienstleistung->stunden_modus === 'wochenstunden' ? 'h/Woche' : 'h/Jahr' }}
                                @if ($dienstleistung->stunden_modus === 'wochenstunden')
                                    → {{ number_format($dienstleistung->jahresstunden(), 1, ',', '.') }} h/Jahr
                                @endif
                            </span>
                        @else
                            <span class="ml-2 text-gray-400">—</span>
                        @endif
                    </div>
                    <div>
                        <span class="font-medium text-gray-500">VZE-Bedarf (1 Schule):</span>
                        @if ($dienstleistung->vzeProSchule() !== null)
                            <span class="ml-2 font-semibold text-indigo-700">{{ number_format($dienstleistung->vzeProSchule(), 3, ',', '.') }} VZE</span>
                        @else
                            <span class="ml-2 text-gray-400">—</span>
                        @endif
                    </div>
                    <div>
                        <span class="font-medium text-gray-500">Aktiv an Schulen:</span>
                        <span class="ml-2 text-gray-900">{{ $schulenAktiv }} Schulen</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
