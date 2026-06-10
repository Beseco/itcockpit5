<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('onboarding.vorlagen.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800">Vorlagen aus Abteilungen generieren</h2>
        </div>
    </x-slot>

    @include('adusers::_subnav')

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 text-sm text-indigo-800">
                Für jede ausgewählte Abteilung (mit AD-Pfad) wird eine Vorlage erstellt, die alle
                <a href="{{ route('onboarding.settings') }}" class="underline">globalen Vorgaben</a>
                (Benutzername-/UPN-Muster, Profil, Heimatverzeichnis, Anmeldeskript) erbt.
                Gruppen, Rufnummern und Adresse kannst du anschließend pro Vorlage ergänzen.
                Abteilungen, für die bereits eine Vorlage existiert, sind ausgegraut.
            </div>

            @php
                // Abteilungen ohne AD-Pfad bzw. ohne verfügbare Auswahl
                $verfuegbar = $abteilungen->reject(fn($a) => in_array($a->id, $belegteAbteilungen, true));
            @endphp

            @if($abteilungen->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-400 text-sm">
                    Keine Abteilungen mit AD-Pfad gefunden. Bitte zuerst in der Abteilungsverwaltung den AD-Pfad hinterlegen.
                </div>
            @else
                <form method="POST" action="{{ route('onboarding.vorlagen.store-generated') }}"
                      x-data="{
                          toggleAll(e) {
                              this.$root.querySelectorAll('input[name=\'abteilung_ids[]\']:not(:disabled)')
                                  .forEach(cb => cb.checked = e.target.checked);
                          }
                      }">
                    @csrf

                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2 bg-gray-50">
                            <input type="checkbox" id="check_all" @change="toggleAll($event)"
                                   class="rounded border-gray-300 text-indigo-600"
                                   @if($verfuegbar->isEmpty()) disabled @endif>
                            <label for="check_all" class="text-xs font-medium text-gray-600">Alle auswählbaren markieren</label>
                            <span class="ml-auto text-xs text-gray-400">{{ $verfuegbar->count() }} verfügbar / {{ $abteilungen->count() }} gesamt</span>
                        </div>

                        <ul class="divide-y divide-gray-100">
                            @foreach($abteilungen as $abt)
                                @php $belegt = in_array($abt->id, $belegteAbteilungen, true); @endphp
                                <li class="px-4 py-3 flex items-start gap-3 {{ $belegt ? 'opacity-50' : '' }}">
                                    <input type="checkbox" name="abteilung_ids[]" value="{{ $abt->id }}"
                                           id="abt_{{ $abt->id }}"
                                           class="mt-1 rounded border-gray-300 text-indigo-600"
                                           @disabled($belegt)>
                                    <label for="abt_{{ $abt->id }}" class="flex-1 cursor-pointer">
                                        <span class="text-sm font-medium text-gray-800">{{ $abt->name }}</span>
                                        @if($belegt)
                                            <span class="ml-2 text-xs text-green-600">✓ Vorlage vorhanden</span>
                                        @endif
                                        <span class="block text-xs text-gray-400 font-mono break-all">{{ $abt->ad_path }}</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-4">
                        <a href="{{ route('onboarding.vorlagen.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Abbrechen</a>
                        <x-primary-button type="submit" @if($verfuegbar->isEmpty()) disabled @endif>
                            Vorlagen erstellen
                        </x-primary-button>
                    </div>
                </form>
            @endif

        </div>
    </div>
</x-app-layout>
