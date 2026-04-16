<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">SSL-Zertifikat importieren</h2>
            <a href="{{ route('sslcerts.index') }}"
               class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Zurück
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="bg-white shadow rounded-lg overflow-hidden"
             x-data="{ mode: '{{ old('upload_type', 'p12') }}', loading: false, showPin: false }">

            {{-- Tab-Auswahl --}}
            <div class="flex border-b border-gray-100">
                <button type="button" @click="mode = 'p12'"
                        :class="mode === 'p12'
                            ? 'border-b-2 border-indigo-500 text-indigo-600 bg-white'
                            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                        class="flex-1 px-6 py-3 text-sm font-medium text-center transition-colors">
                    P12 / PFX
                </button>
                <button type="button" @click="mode = 'pem'"
                        :class="mode === 'pem'
                            ? 'border-b-2 border-indigo-500 text-indigo-600 bg-white'
                            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                        class="flex-1 px-6 py-3 text-sm font-medium text-center transition-colors">
                    PEM / Key-Datei
                </button>
            </div>

            {{-- Beschreibung --}}
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-100 text-xs text-gray-500">
                <span x-show="mode === 'p12'">P12-Zertifikat mit Transport-PIN hochladen. PIN und P12-Datei werden nicht gespeichert.</span>
                <span x-show="mode === 'pem'" x-cloak>PEM-Zertifikat und optionalen Private Key hochladen. Key wird verschlüsselt gespeichert.</span>
            </div>

            <form action="{{ route('sslcerts.store') }}" method="POST" enctype="multipart/form-data"
                  class="p-6 space-y-5" @submit="loading = true">
                @csrf
                <input type="hidden" name="upload_type" :value="mode">

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Bezeichnung <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name"
                           value="{{ old('name') }}"
                           placeholder="z.B. Webserver example.com"
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                           required>
                    @error('name')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Beschreibung --}}
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Beschreibung</label>
                    <textarea name="description" id="description" rows="3"
                              placeholder="Wofür wird dieses Zertifikat verwendet?"
                              class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('description') }}</textarea>
                </div>

                {{-- Verantwortlicher --}}
                <div>
                    <label for="responsible_user_id" class="block text-sm font-medium text-gray-700 mb-1">Verantwortlicher</label>
                    <select name="responsible_user_id" id="responsible_user_id"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">— kein Verantwortlicher —</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('responsible_user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Server --}}
                @if($servers->isNotEmpty())
                @php
                    $oldServers = collect($servers)->whereIn('id', old('servers', []))->values();
                @endphp
                <div x-data="{
                        open: false,
                        search: '',
                        all: {{ $servers->map(fn($s) => ['id'=>$s->id,'name'=>$s->name,'host'=>$s->dns_hostname??''])->values()->toJson() }},
                        selected: {{ $oldServers->map(fn($s) => ['id'=>$s->id,'name'=>$s->name,'host'=>$s->dns_hostname??''])->values()->toJson() }},
                        get filtered() {
                            const q = this.search.toLowerCase();
                            return this.all.filter(s =>
                                !this.selected.find(x => x.id === s.id) &&
                                (s.name.toLowerCase().includes(q) || s.host.toLowerCase().includes(q))
                            );
                        },
                        add(s) { this.selected.push(s); this.search = ''; },
                        remove(id) { this.selected = this.selected.filter(s => s.id !== id); }
                    }">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Server</label>

                    {{-- Hidden inputs --}}
                    <template x-for="s in selected" :key="s.id">
                        <input type="hidden" name="servers[]" :value="s.id">
                    </template>

                    {{-- Gewählte Server als Tags --}}
                    <div class="flex flex-wrap gap-2 mb-2 min-h-[2rem]">
                        <template x-for="s in selected" :key="s.id">
                            <span class="inline-flex items-center gap-1 pl-2.5 pr-1 py-1 bg-indigo-50 text-indigo-700 text-xs rounded-md border border-indigo-200">
                                <svg class="w-3 h-3 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                                </svg>
                                <span x-text="s.name"></span>
                                <button type="button" @click="remove(s.id)"
                                        class="ml-0.5 text-indigo-400 hover:text-indigo-700">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </span>
                        </template>
                        <span x-show="selected.length === 0" class="text-xs text-gray-400 self-center">Noch kein Server verknüpft</span>
                    </div>

                    <button type="button" @click="open = true"
                            class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-600 text-xs font-medium rounded-md hover:bg-gray-200 border border-gray-300">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Server hinzufügen
                    </button>

                    {{-- Modal --}}
                    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
                         @keydown.escape.window="open = false">
                        <div class="absolute inset-0 bg-black bg-opacity-30" @click="open = false"></div>
                        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md flex flex-col" style="max-height:70vh">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-800">Server hinzufügen</h3>
                                <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="px-4 pt-3 pb-2">
                                <input type="text" x-model="search" placeholder="Server suchen…"
                                       x-ref="searchInput" @focus="$nextTick(() => $refs.searchInput.select())"
                                       x-init="$watch('open', v => v && $nextTick(() => $refs.searchInput.focus()))"
                                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div class="overflow-y-auto flex-1 divide-y divide-gray-100 px-2 pb-2">
                                <template x-for="s in filtered" :key="s.id">
                                    <button type="button" @click="add(s)"
                                            class="w-full flex items-center gap-2 px-3 py-2 rounded-md hover:bg-indigo-50 text-left group">
                                        <svg class="w-4 h-4 text-gray-400 group-hover:text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                                        </svg>
                                        <span class="text-sm text-gray-800" x-text="s.name"></span>
                                        <span class="text-xs text-gray-400 font-mono" x-text="s.host"></span>
                                    </button>
                                </template>
                                <div x-show="filtered.length === 0" class="px-3 py-4 text-xs text-gray-400 text-center">
                                    Keine Server gefunden
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Dokumentations-Link --}}
                <div>
                    <label for="doc_url" class="block text-sm font-medium text-gray-700 mb-1">Dokumentations-Link</label>
                    <input type="url" name="doc_url" id="doc_url"
                           value="{{ old('doc_url') }}"
                           placeholder="https://wiki.example.com/ssl/..."
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('doc_url')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <hr class="border-gray-100">

                {{-- P12-Felder --}}
                <div x-show="mode === 'p12'" x-cloak class="space-y-5">

                    <div>
                        <label for="p12_file" class="block text-sm font-medium text-gray-700 mb-1">
                            P12-Zertifikat (.p12 / .pfx) <span class="text-red-500">*</span>
                        </label>
                        <input type="file" name="p12_file" id="p12_file"
                               accept=".p12,.pfx"
                               :required="mode === 'p12'"
                               class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        @error('p12_file')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="p12_pin" class="block text-sm font-medium text-gray-700 mb-1">
                            Transport-PIN
                        </label>
                        <div class="relative">
                            <input :type="showPin ? 'text' : 'password'" name="p12_pin" id="p12_pin"
                                   placeholder="Transport-PIN eingeben (leer lassen wenn kein PIN)"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500 pr-10">
                            <button type="button" @click="showPin = !showPin"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                <svg x-show="!showPin" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPin" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            <svg class="w-3.5 h-3.5 inline-block mr-0.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Der Transport-PIN wird nur zum Entschlüsseln verwendet und nicht gespeichert.
                        </p>
                        @error('p12_pin')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                {{-- PEM-Felder --}}
                <div x-show="mode === 'pem'" x-cloak class="space-y-5">

                    <div>
                        <label for="pem_cert" class="block text-sm font-medium text-gray-700 mb-1">
                            PEM-Zertifikat (.pem / .crt / .cer) <span class="text-red-500">*</span>
                        </label>
                        <input type="file" name="pem_cert" id="pem_cert"
                               accept=".pem,.crt,.cer"
                               :required="mode === 'pem'"
                               class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        @error('pem_cert')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="pem_key" class="block text-sm font-medium text-gray-700 mb-1">
                            Private Key (.pem / .key)
                            <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <input type="file" name="pem_key" id="pem_key"
                               accept=".pem,.key"
                               class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                        <p class="text-xs text-gray-400 mt-1">
                            <svg class="w-3.5 h-3.5 inline-block mr-0.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Der Private Key wird verschlüsselt gespeichert.
                        </p>
                        @error('pem_key')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                @if($errors->any() && !$errors->has('name') && !$errors->has('p12_file') && !$errors->has('p12_pin') && !$errors->has('pem_cert') && !$errors->has('pem_key'))
                    <div class="p-3 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="flex items-center justify-end pt-2">
                    <button type="submit" :disabled="loading"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50">
                        <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="loading ? 'Wird importiert...' : 'Importieren'"></span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
