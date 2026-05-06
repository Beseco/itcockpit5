<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Organisationseinheiten (OE)</h2>
            <a href="{{ route('abteilungen.help') }}" title="Hilfe & Anleitung" class="inline-flex items-center justify-center w-7 h-7 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-full transition"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg></a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                     class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('info'))
                <div class="mb-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
                    {{ session('info') }}
                </div>
            @endif

            {{-- AD-Zählung Ergebnis --}}
            @if (session()->has('ad_refresh_updated'))
                @php
                    $adUpdated = session('ad_refresh_updated');
                    $adMissing = session('ad_refresh_missing', []);
                    $adFailed  = session('ad_refresh_failed', []);
                    $hasIssues = count($adMissing) > 0 || count($adFailed) > 0;
                @endphp
                <div class="mb-4 rounded-lg border overflow-hidden {{ $hasIssues ? 'border-yellow-300' : 'border-green-300' }}">
                    <div class="px-4 py-3 flex items-center gap-2 {{ $hasIssues ? 'bg-yellow-50 text-yellow-800' : 'bg-green-50 text-green-800' }}">
                        @if($hasIssues)
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @endif
                        <span class="font-medium">AD-Zählung abgeschlossen: {{ $adUpdated }} OE(s) aktualisiert.</span>
                    </div>
                    @if(count($adFailed) > 0)
                        <div class="px-4 py-3 bg-red-50 border-t border-red-200">
                            <p class="text-xs font-semibold text-red-700 mb-1">Ungültiger AD-Pfad – Zählung fehlgeschlagen:</p>
                            <ul class="text-xs text-red-700 space-y-0.5 list-disc list-inside">
                                @foreach($adFailed as $name)
                                    <li>{{ $name }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if(count($adMissing) > 0)
                        <div class="px-4 py-3 bg-yellow-50 border-t border-yellow-200">
                            <p class="text-xs font-semibold text-yellow-700 mb-1">Kein AD-Pfad hinterlegt:</p>
                            <ul class="text-xs text-yellow-700 space-y-0.5 list-disc list-inside">
                                @foreach($adMissing as $name)
                                    <li>{{ $name }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            <div class="flex items-center justify-between mb-4">
                <p class="text-sm text-gray-500">Hierarchische Verwaltung der Organisationseinheiten</p>
                <div class="flex items-center gap-3">
                    @can('abteilungen.edit')
                    <form method="POST" action="{{ route('abteilungen.refresh-ad-counts') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 hover:bg-gray-50 gap-1.5">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            AD-Zählung aktualisieren
                        </button>
                    </form>
                    <a href="{{ route('abteilungen.revision-settings') }}"
                       class="text-sm text-gray-500 hover:text-gray-700">
                        Revisions-Einstellungen
                    </a>
                    @endcan
                    @can('abteilungen.create')
                    <a href="{{ route('abteilungen.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Neue OE
                    </a>
                    @endcan
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if($abteilungen->isEmpty())
                    <div class="px-4 py-8 text-center text-gray-400">
                        Noch keine Organisationseinheiten angelegt.
                    </div>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach($abteilungen as $abteilung)
                            @include('abteilungen._tree_row', ['abteilung' => $abteilung, 'depth' => 0])
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
