<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <a href="{{ route('onboarding.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">Onboarding</a>
                <span class="text-gray-300">/</span>
                <h2 class="font-semibold text-xl text-gray-800">Vorlagen</h2>
            </div>
            <a href="{{ route('abteilungen.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                Organisationseinheiten verwalten
            </a>
        </div>
    </x-slot>

    @include('adusers::_subnav')

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-3 text-xs text-indigo-800">
                Zu jeder <a href="{{ route('abteilungen.index') }}" class="underline">Organisationseinheit</a> gehört automatisch genau eine Vorlage.
                Neue OEs erzeugen automatisch eine Vorlage, gelöschte OEs entfernen sie wieder.
                Gemeinsame Felder (Muster, Profil, Laufwerke, Anmeldeskript) kommen aus den globalen
                <a href="{{ route('onboarding.settings') }}" class="underline">Einstellungen</a>; hier ergänzt du pro OE Gruppen, Adresse und Vorgesetzten.
            </div>

            @if($abteilungen->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-400 text-sm">
                    Noch keine Organisationseinheiten vorhanden.
                    <a href="{{ route('abteilungen.index') }}" class="text-indigo-600 hover:underline ml-1">Erste OE anlegen</a>
                </div>
            @else
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="divide-y divide-gray-100">
                        @foreach($abteilungen as $abteilung)
                            @include('onboarding::vorlagen._tree_row', ['abteilung' => $abteilung, 'depth' => 0])
                        @endforeach
                    </div>
                </div>
            @endif

            @if($standalone->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <p class="px-4 py-2 text-xs font-medium text-gray-500 bg-gray-50 border-b border-gray-100">Vorlagen ohne OE</p>
                    <div class="divide-y divide-gray-100">
                        @foreach($standalone as $vorlage)
                            <div class="px-4 py-3 flex items-center justify-between hover:bg-gray-50">
                                <span class="text-sm font-medium text-gray-900">{{ $vorlage->name }}</span>
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('onboarding.create', ['vorlage_id' => $vorlage->id]) }}"
                                       class="text-xs text-indigo-600 hover:text-indigo-800">Verwenden</a>
                                    @can('module.onboarding.edit')
                                        <a href="{{ route('onboarding.vorlagen.edit', $vorlage) }}"
                                           class="text-xs text-gray-500 hover:text-gray-700">Bearbeiten</a>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
