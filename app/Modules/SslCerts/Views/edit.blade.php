<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Zertifikat bearbeiten</h2>
            <a href="{{ route('sslcerts.show', $cert) }}"
               class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Zurück
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        {{-- Zertifikats-Info (readonly) --}}
        @php
            $color    = $cert->getExpiryColor();
            $colorMap = [
                'red'    => 'bg-red-50 border-red-300 text-red-700',
                'yellow' => 'bg-yellow-50 border-yellow-300 text-yellow-700',
                'green'  => 'bg-green-50 border-green-300 text-green-700',
            ];
            $days = $cert->getDaysRemaining();
        @endphp
        <div class="rounded-lg border p-4 {{ $colorMap[$color] }} text-sm">
            <div class="font-semibold">{{ $cert->subject_cn ?? $cert->name }}</div>
            <div class="text-xs mt-0.5 opacity-80">
                {{ $cert->valid_from?->format('d.m.Y') ?? '?' }} – {{ $cert->valid_to?->format('d.m.Y') ?? '?' }}
                &middot;
                @if($days < 0) Abgelaufen seit {{ abs($days) }} Tagen
                @elseif($days === 0) Läuft heute ab
                @else Noch {{ $days }} Tage gültig
                @endif
            </div>
        </div>

        {{-- Formular --}}
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">Informationen bearbeiten</h3>
            </div>

            <form action="{{ route('sslcerts.update', $cert) }}" method="POST" class="p-6 space-y-5">
                @csrf @method('PUT')

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Bezeichnung <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name"
                           value="{{ old('name', $cert->name) }}"
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
                              class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('description', $cert->description) }}</textarea>
                </div>

                {{-- Verantwortlicher --}}
                <div>
                    <label for="responsible_user_id" class="block text-sm font-medium text-gray-700 mb-1">Verantwortlicher</label>
                    <select name="responsible_user_id" id="responsible_user_id"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">— kein Verantwortlicher —</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}"
                                {{ old('responsible_user_id', $cert->responsible_user_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Server --}}
                @if($servers->isNotEmpty())
                @php
                    $preSelected = collect($servers)->whereIn('id', old('servers', $serverIds))->values();
                @endphp
                <div x-data="{
                        open: false,
                        search: '',
                        all: {{ $servers->map(fn($s) => ['id'=>$s->id,'name'=>$s->name,'host'=>$s->dns_hostname??''])->values()->toJson() }},
                        selected: {{ $preSelected->map(fn($s) => ['id'=>$s->id,'name'=>$s->name,'host'=>$s->dns_hostname??''])->values()->toJson() }},
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
                                       x-ref="searchInput"
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
                           value="{{ old('doc_url', $cert->doc_url) }}"
                           placeholder="https://wiki.example.com/ssl/..."
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('doc_url')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                        Speichern
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
