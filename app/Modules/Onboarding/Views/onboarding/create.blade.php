<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('onboarding.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800">Benutzer anlegen</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8"
             x-data="{
                vorlageId: '{{ old('vorlage_id', $vorlage?->id ?? '') }}',
                vorname: '{{ old('vorname', '') }}',
                nachname: '{{ old('nachname', '') }}',
                samaccountname: '{{ old('samaccountname', '') }}',
                upn: '{{ old('upn', '') }}',
                rufnummer: '{{ old('rufnummer', '') }}',
                fax: '{{ old('fax', '') }}',
                alternatives: [],
                previewing: false,
                async loadPreview() {
                    if (!this.vorlageId || !this.vorname || !this.nachname) return;
                    this.previewing = true;
                    try {
                        const r = await fetch('{{ route('onboarding.preview') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify({ vorlage_id: this.vorlageId, vorname: this.vorname, nachname: this.nachname }),
                        });
                        const j = await r.json();
                        this.samaccountname = j.samaccountname;
                        this.upn = j.upn;
                        this.rufnummer = j.rufnummer || '';
                        this.fax = j.fax || '';
                        this.alternatives = j.alternatives || [];
                    } catch(e) {}
                    finally { this.previewing = false; }
                }
             }">

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-md text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('onboarding.store') }}" class="space-y-6">
                @csrf

                {{-- Vorlage wählen --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">1. Vorlage wählen</h3>
                    <select name="vorlage_id" x-model="vorlageId" @change="loadPreview()"
                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" required>
                        <option value="">– Vorlage auswählen –</option>
                        @foreach($vorlagen as $v)
                            <option value="{{ $v->id }}" @selected($vorlage?->id == $v->id)>
                                {{ $v->name }}{{ $v->abteilung ? ' – ' . $v->abteilung->name : '' }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('vorlage_id')" class="mt-1" />
                </div>

                {{-- Personendaten --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">2. Personendaten</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="vorname" value="Vorname *" />
                            <x-text-input id="vorname" name="vorname" type="text" class="mt-1 block w-full"
                                          x-model="vorname" @blur="loadPreview()" required />
                            <x-input-error :messages="$errors->get('vorname')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="nachname" value="Nachname *" />
                            <x-text-input id="nachname" name="nachname" type="text" class="mt-1 block w-full"
                                          x-model="nachname" @blur="loadPreview()" required />
                            <x-input-error :messages="$errors->get('nachname')" class="mt-1" />
                        </div>
                    </div>
                </div>

                {{-- Generierte Kontodaten --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700">3. Kontodaten (automatisch befüllt)</h3>
                        <button type="button" @click="loadPreview()" :disabled="previewing || !vorlageId || !vorname || !nachname"
                                class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                            <span x-text="previewing ? 'Lade …' : '⟳ Neu ermitteln'"></span>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="samaccountname" value="Benutzername (sAMAccountName) *" />
                                <x-text-input id="samaccountname" name="samaccountname" type="text" class="mt-1 block w-full font-mono"
                                              x-model="samaccountname" required />
                                <div x-show="alternatives.length > 0" x-cloak class="mt-1 text-xs text-amber-600">
                                    ⚠ Name vergeben. Alternativen:
                                    <template x-for="alt in alternatives" :key="alt">
                                        <button type="button" @click="samaccountname = alt"
                                                class="ml-1 underline hover:text-amber-800" x-text="alt"></button>
                                    </template>
                                </div>
                                <x-input-error :messages="$errors->get('samaccountname')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="upn" value="UPN / E-Mail *" />
                                <x-text-input id="upn" name="upn" type="email" class="mt-1 block w-full font-mono"
                                              x-model="upn" required />
                                <x-input-error :messages="$errors->get('upn')" class="mt-1" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="rufnummer" value="Rufnummer" />
                                <x-text-input id="rufnummer" name="rufnummer" type="text" class="mt-1 block w-full"
                                              x-model="rufnummer" placeholder="Wird automatisch ermittelt" />
                                <x-input-error :messages="$errors->get('rufnummer')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="fax" value="Fax" />
                                <x-text-input id="fax" name="fax" type="text" class="mt-1 block w-full"
                                              x-model="fax" placeholder="Wird automatisch ermittelt" />
                                <x-input-error :messages="$errors->get('fax')" class="mt-1" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="vorgesetzter_dn" value="Vorgesetzter (AD-DN, optional – überschreibt Vorlage)" />
                            <x-text-input id="vorgesetzter_dn" name="vorgesetzter_dn" type="text" class="mt-1 block w-full font-mono text-xs"
                                          value="{{ old('vorgesetzter_dn') }}"
                                          placeholder="CN=Max Muster,OU=A1,OU=Benutzer,OU=LRA-FS,DC=lra,DC=lan" />
                            <x-input-error :messages="$errors->get('vorgesetzter_dn')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
                    <strong>Hinweis:</strong> Das temporäre Passwort wird nach dem Anlegen <strong>einmalig angezeigt</strong>
                    und ist in der Datenbank nicht gespeichert. Bitte notieren oder weitergeben, bevor die Seite verlassen wird.
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('onboarding.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Abbrechen</a>
                    <x-primary-button type="submit">Benutzer jetzt anlegen</x-primary-button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
