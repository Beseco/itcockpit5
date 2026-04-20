<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Abteilungen / Sachgebiete</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                     class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="flex items-center justify-between mb-4">
                <p class="text-sm text-gray-500">Hierarchische Verwaltung von Abteilungen und Sachgebieten</p>
                <div class="flex items-center gap-3">
                    @can('abteilungen.edit')
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
                        Neue Abteilung
                    </a>
                    @endcan
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if($abteilungen->isEmpty())
                    <div class="px-4 py-8 text-center text-gray-400">
                        Noch keine Abteilungen angelegt.
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
