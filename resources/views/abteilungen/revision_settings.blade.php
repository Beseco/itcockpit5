<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Abteilungen – Revisions-Einstellungen</h2>
            <a href="{{ route('abteilungen.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Zur Übersicht</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">Neue-Software-Vorschläge</h3>
                <p class="text-xs text-gray-400 mb-5">
                    Wenn Abteilungsleiter im Rahmen der Revision neue (noch nicht erfasste) Software vorschlagen,
                    wird eine E-Mail an die folgende Adresse gesendet.
                </p>

                <form method="POST" action="{{ route('abteilungen.revision-settings.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="new_app_email" value="Empfänger-E-Mail für Software-Vorschläge *" />
                        <x-text-input id="new_app_email" name="new_app_email" type="email"
                                      class="mt-1 block w-full"
                                      :value="old('new_app_email', $settings->new_app_email)"
                                      placeholder="informatiotechnik@kreis-fs.de"
                                      required />
                        <x-input-error :messages="$errors->get('new_app_email')" class="mt-1" />
                    </div>

                    <div class="pt-2 border-t border-gray-100 flex justify-end">
                        <x-primary-button type="submit">Einstellungen speichern</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-600">
                <strong>Hinweis:</strong> Die E-Mail wird gesendet, sobald ein Abteilungsleiter nach dem Durchlauf
                aller Applikationen neue Software vorschlägt. Die IT-Abteilung kann diese Vorschläge dann
                prüfen und ggf. in das IT Cockpit aufnehmen.
            </div>

        </div>
    </div>
</x-app-layout>
