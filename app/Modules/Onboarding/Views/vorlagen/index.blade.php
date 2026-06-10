<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <a href="{{ route('onboarding.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">Onboarding</a>
                <span class="text-gray-300">/</span>
                <h2 class="font-semibold text-xl text-gray-800">Vorlagen</h2>
            </div>
            @can('module.onboarding.edit')
                <div class="flex items-center gap-2">
                    <a href="{{ route('onboarding.vorlagen.generate') }}"
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                        Aus Abteilungen generieren
                    </a>
                    <a href="{{ route('onboarding.vorlagen.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        + Neue Vorlage
                    </a>
                </div>
            @endcan
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

            @if($vorlagen->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-400 text-sm">
                    Noch keine Vorlagen angelegt.
                    <a href="{{ route('onboarding.vorlagen.create') }}" class="text-indigo-600 hover:underline ml-1">Erste Vorlage erstellen</a>
                </div>
            @else
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vorlage</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Abteilung</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">sAMAccountName-Muster</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gruppen</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($vorlagen as $vorlage)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $vorlage->name }}
                                        @if($vorlage->beschreibung)
                                            <p class="text-xs text-gray-400 mt-0.5 font-normal">{{ Str::limit($vorlage->beschreibung, 60) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $vorlage->abteilung?->name ?? '–' }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">
                                        {{ $vorlage->samaccountname_pattern }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 text-xs">
                                        {{ $vorlage->gruppen->count() }} Gruppe(n)
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($vorlage->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktiv</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inaktiv</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('onboarding.create', ['vorlage_id' => $vorlage->id]) }}"
                                               class="text-xs text-indigo-600 hover:text-indigo-800">Verwenden</a>
                                            <a href="{{ route('onboarding.vorlagen.edit', $vorlage) }}"
                                               class="text-xs text-gray-500 hover:text-gray-700">Bearbeiten</a>
                                            <form method="POST" action="{{ route('onboarding.vorlagen.clone', $vorlage) }}">
                                                @csrf
                                                <button type="submit" class="text-xs text-gray-500 hover:text-gray-700">Klonen</button>
                                            </form>
                                            <form method="POST" action="{{ route('onboarding.vorlagen.destroy', $vorlage) }}"
                                                  onsubmit="return confirm('Vorlage „{{ $vorlage->name }}" wirklich löschen?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-xs text-red-500 hover:text-red-700">Löschen</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
