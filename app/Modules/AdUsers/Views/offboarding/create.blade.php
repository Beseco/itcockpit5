<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('adusers.offboarding.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Neuer Offboarding-Vorgang</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Checkliste (Info) --}}
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-5 text-sm">
                <h3 class="font-semibold text-amber-800 mb-2">Maßnahmen bei Ausscheiden</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-amber-700">
                    <div>
                        <p class="font-medium mb-1">Sofort bei Ausscheiden:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            <li>Benutzerkonto deaktivieren</li>
                            <li>Smartcard / Hardware einziehen</li>
                            <li>VPN-Zugang sperren</li>
                            <li>Mobile Endgeräte einziehen</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-medium mb-1">Nach 2 Monaten:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            <li>Postfach bereitstellen / weiterleiten</li>
                            <li>Benutzerkonto und Postfach löschen</li>
                            <li>Serverlaufwerke prüfen und bereinigen</li>
                            <li>Terminalserver-Profil löschen</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Formular --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('adusers.offboarding.store') }}" method="POST">
                    @csrf

                    @if ($aduser)
                        <input type="hidden" name="aduser_id" value="{{ $aduser->id }}">
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <x-input-label for="vorname" value="Vorname *" />
                            <x-text-input id="vorname" name="vorname" type="text" class="mt-1 block w-full"
                                          value="{{ old('vorname', $aduser?->vorname) }}" required />
                            <x-input-error :messages="$errors->get('vorname')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="nachname" value="Nachname *" />
                            <x-text-input id="nachname" name="nachname" type="text" class="mt-1 block w-full"
                                          value="{{ old('nachname', $aduser?->nachname) }}" required />
                            <x-input-error :messages="$errors->get('nachname')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="samaccountname" value="SAM-Account *" />
                            <x-text-input id="samaccountname" name="samaccountname" type="text" class="mt-1 block w-full"
                                          value="{{ old('samaccountname', $aduser?->samaccountname) }}" required />
                            <x-input-error :messages="$errors->get('samaccountname')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="personalnummer" value="Personalnummer" />
                            <x-text-input id="personalnummer" name="personalnummer" type="text" class="mt-1 block w-full"
                                          value="{{ old('personalnummer') }}" />
                            <x-input-error :messages="$errors->get('personalnummer')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="abteilung" value="Abteilung / Sachgebiet" />
                            <x-text-input id="abteilung" name="abteilung" type="text" class="mt-1 block w-full"
                                          value="{{ old('abteilung', $aduser?->abteilung) }}" />
                            <x-input-error :messages="$errors->get('abteilung')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="datum_ausscheiden" value="Beendigung Dienstverhältnis *" />
                            <x-text-input id="datum_ausscheiden" name="datum_ausscheiden" type="date" class="mt-1 block w-full"
                                          value="{{ old('datum_ausscheiden') }}" required />
                            <x-input-error :messages="$errors->get('datum_ausscheiden')" class="mt-1" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label value="E-Mail für Bestätigung" />
                            <p class="mt-1 text-sm text-gray-700 bg-gray-50 border border-gray-200 rounded-md px-3 py-2">
                                {{ $aduser?->email ?? '(kein AD-Benutzer verknüpft – E-Mail nicht verfügbar)' }}
                            </p>
                            @if ($aduser?->email)
                                <input type="hidden" name="email_bestaetigung" value="{{ $aduser->email }}">
                            @endif
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="bemerkungen" value="Bemerkungen" />
                            <textarea id="bemerkungen" name="bemerkungen" rows="3"
                                      class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('bemerkungen') }}</textarea>
                            <x-input-error :messages="$errors->get('bemerkungen')" class="mt-1" />
                        </div>

                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <a href="{{ route('adusers.offboarding.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Abbrechen
                        </a>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-red-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-800">
                            Vorgang anlegen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
