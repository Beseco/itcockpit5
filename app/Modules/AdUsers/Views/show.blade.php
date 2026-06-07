<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <a href="{{ route('adusers.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">AD-Benutzer</a>
                <span class="text-gray-300">/</span>
                <h2 class="font-semibold text-xl text-gray-800">{{ $user->anzeigename_or_name }}</h2>
            </div>
            <div class="flex items-center gap-3">
                @php
                    $activeOffboarding = \App\Modules\AdUsers\Models\OffboardingRecord::where('samaccountname', $user->samaccountname)
                        ->whereNotIn('status', ['abgeschlossen'])
                        ->first();
                @endphp
                @if ($activeOffboarding)
                    <a href="{{ route('adusers.offboarding.show', $activeOffboarding) }}"
                       class="inline-flex items-center px-3 py-1.5 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700">
                        ⚠ Offboarding läuft
                    </a>
                @else
                    <a href="{{ route('adusers.offboarding.create', ['aduser' => $user->id]) }}"
                       class="inline-flex items-center px-3 py-1.5 bg-red-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-800">
                        Offboarding einleiten
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    @include('adusers::_subnav')

    <div class="py-6"
         x-data="{
            activeTab: window.location.hash.replace('#','') || 'uebersicht',
            compareLoading: false,
            compareResult: null,
            compareSearch: '',
            compareSearchResults: [],
            compareSearchLoading: false,
            ouLoading: false,
            ouResult: null,

            switchTab(tab) {
                this.activeTab = tab;
                window.location.hash = tab;
            },

            async searchUsers(q) {
                if (q.length < 2) { this.compareSearchResults = []; return; }
                this.compareSearchLoading = true;
                const r = await fetch('{{ route('adusers.index') }}?search=' + encodeURIComponent(q) + '&per_page=10', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                // Parse HTML ist unhandlich – stattdessen eigener AJAX-Endpunkt via inline search
                // Wir nutzen den bereits geladenen users-Index nicht, daher eigene Route
                const res = await fetch('/adusers-search?q=' + encodeURIComponent(q));
                if (res.ok) this.compareSearchResults = await res.json();
                this.compareSearchLoading = false;
            },

            async compareWith(targetId) {
                this.compareLoading = true;
                this.compareResult = null;
                this.ouResult = null;
                const r = await fetch('{{ route('adusers.compare-user', [$user, '__TARGET__']) }}'.replace('__TARGET__', targetId));
                if (r.ok) this.compareResult = await r.json();
                this.compareLoading = false;
                this.compareSearch = '';
                this.compareSearchResults = [];
            },

            async compareOu() {
                this.ouLoading = true;
                this.compareResult = null;
                this.ouResult = null;
                const r = await fetch('{{ route('adusers.compare-ou', $user) }}');
                if (r.ok) this.ouResult = await r.json();
                this.ouLoading = false;
            }
         }">

        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            {{-- Tab-Leiste --}}
            <div class="flex gap-1 mb-6 border-b border-gray-200">
                <button @click="switchTab('uebersicht')"
                        :class="activeTab === 'uebersicht' ? 'border-indigo-600 text-indigo-700 bg-white' : 'border-transparent text-gray-500 hover:text-gray-800'"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition whitespace-nowrap">
                    Übersicht
                </button>
                <button @click="switchTab('gruppen')"
                        :class="activeTab === 'gruppen' ? 'border-indigo-600 text-indigo-700 bg-white' : 'border-transparent text-gray-500 hover:text-gray-800'"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition whitespace-nowrap">
                    Gruppen
                    @if(count($groups) > 0)
                        <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">{{ count($groups) }}</span>
                    @endif
                </button>
                <button @click="switchTab('cockpit')"
                        :class="activeTab === 'cockpit' ? 'border-indigo-600 text-indigo-700 bg-white' : 'border-transparent text-gray-500 hover:text-gray-800'"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition whitespace-nowrap">
                    IT Cockpit
                    @if($onboardingRecords->isNotEmpty())
                        <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">{{ $onboardingRecords->count() }}</span>
                    @endif
                </button>
                <button @click="switchTab('vergleich')"
                        :class="activeTab === 'vergleich' ? 'border-indigo-600 text-indigo-700 bg-white' : 'border-transparent text-gray-500 hover:text-gray-800'"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition whitespace-nowrap">
                    Vergleichen
                </button>
            </div>

            {{-- ══════════════ TAB: ÜBERSICHT ══════════════ --}}
            <div x-show="activeTab === 'uebersicht'" class="space-y-4">

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <div class="flex items-start justify-between mb-4">
                        <h3 class="text-base font-semibold text-gray-800">Benutzerdaten</h3>
                        @php $badge = $user->status_badge; @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-sm font-medium {{ $badge['class'] }}">
                            {{ $badge['label'] }}
                        </span>
                    </div>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 text-sm">
                        @foreach([
                            'Vorname'      => $user->vorname,
                            'Nachname'     => $user->nachname,
                            'Anzeigename'  => $user->anzeigename,
                            'Konto (SAM)'  => $user->samaccountname,
                            'E-Mail / UPN' => $user->email,
                            'Organisation' => $user->organisation,
                            'Abteilung'    => $user->abteilung,
                            'Telefon'      => $user->telefon,
                        ] as $label => $value)
                            @if($value)
                            <div>
                                <dt class="text-gray-500">{{ $label }}</dt>
                                <dd class="mt-0.5 font-medium text-gray-800">{{ $value }}</dd>
                            </div>
                            @endif
                        @endforeach

                        {{-- Zusätzliche Attribute aus raw_data --}}
                        @php
                            $raw = $user->raw_data ?? [];
                            $extraFields = [
                                'Position'    => $raw['title'][0] ?? null,
                                'Beschreibung'=> $raw['description'][0] ?? null,
                                'Büro'        => $raw['physicaldeliveryofficename'][0] ?? null,
                                'Mobilnummer' => $raw['mobile'][0] ?? null,
                            ];
                        @endphp
                        @foreach($extraFields as $label => $value)
                            @if($value)
                            <div>
                                <dt class="text-gray-500">{{ $label }}</dt>
                                <dd class="mt-0.5 font-medium text-gray-800">{{ $value }}</dd>
                            </div>
                            @endif
                        @endforeach

                        @if(!empty($raw['manager'][0]))
                        <div class="sm:col-span-2">
                            <dt class="text-gray-500">Manager</dt>
                            <dd class="mt-0.5 font-mono text-xs text-gray-600 break-all">{{ $raw['manager'][0] }}</dd>
                        </div>
                        @endif

                        <div>
                            <dt class="text-gray-500">Letzte Sync</dt>
                            <dd class="mt-0.5 font-medium text-gray-800">{{ $user->letzter_import_at?->format('d.m.Y H:i') ?? '–' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">AD aktiv</dt>
                            <dd class="mt-0.5 font-medium text-gray-800">{{ $user->ad_aktiv ? 'Ja' : 'Nein' }}</dd>
                        </div>
                    </dl>

                    @if($user->distinguished_name)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <dt class="text-xs text-gray-400">Distinguished Name (AD-Pfad)</dt>
                        <dd class="mt-0.5 text-xs font-mono text-gray-600 break-all">{{ $user->distinguished_name }}</dd>
                    </div>
                    @endif
                </div>

                {{-- Alle raw AD-Attribute (klappbar) --}}
                @if($user->raw_data)
                <details class="bg-white shadow-sm rounded-lg">
                    <summary class="px-6 py-4 text-sm font-semibold text-gray-700 cursor-pointer select-none">
                        Alle AD-Attribute ({{ count(array_filter(array_keys($user->raw_data), fn($k) => $k !== 'count' && !is_numeric($k))) }} Felder)
                    </summary>
                    <div class="px-6 pb-6 overflow-x-auto">
                        <table class="min-w-full text-xs divide-y divide-gray-100">
                            <thead>
                                <tr>
                                    <th class="py-2 pr-4 text-left font-medium text-gray-500 w-1/3">Attribut</th>
                                    <th class="py-2 text-left font-medium text-gray-500">Wert</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($user->raw_data as $key => $val)
                                @if($key !== 'count' && !is_numeric($key) && $key !== 'memberof')
                                <tr>
                                    <td class="py-1.5 pr-4 font-mono text-gray-500 align-top">{{ $key }}</td>
                                    <td class="py-1.5 text-gray-700 break-all align-top">
                                        @if(is_array($val))
                                            {{ implode(', ', array_filter($val, fn($v) => $v !== 'count' && !is_numeric($v) && !is_array($v))) }}
                                        @else
                                            {{ $val }}
                                        @endif
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
                @endif
            </div>

            {{-- ══════════════ TAB: GRUPPEN ══════════════ --}}
            <div x-show="activeTab === 'gruppen'" class="space-y-4">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">
                        Gruppenmitgliedschaften
                        <span class="ml-2 text-sm font-normal text-gray-400">{{ count($groups) }} Gruppen</span>
                    </h3>

                    @if(empty($groups))
                        <p class="text-sm text-gray-400">
                            @if(!empty($user->raw_data['memberof']))
                                Keine Gruppen gefunden.
                            @else
                                Keine Gruppendaten vorhanden – bitte Synchronisation ausführen.
                                Die Gruppen werden ab der nächsten Synchronisation angezeigt.
                            @endif
                        </p>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($groups as $group)
                            <div class="flex items-start gap-2 p-3 rounded-md border border-gray-100 hover:bg-gray-50">
                                <svg class="w-4 h-4 mt-0.5 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-800 truncate" title="{{ $group['name'] }}">{{ $group['name'] }}</div>
                                    <div class="text-xs text-gray-400 break-all">{{ $group['dn'] }}</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- ══════════════ TAB: IT COCKPIT ══════════════ --}}
            <div x-show="activeTab === 'cockpit'" class="space-y-4">

                @if($onboardingRecords->isEmpty())
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <p class="text-sm text-gray-400">Dieser Benutzer wurde nicht über IT Cockpit angelegt.</p>
                    </div>
                @else
                    @foreach($onboardingRecords as $record)
                    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                        {{-- Record-Header --}}
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $record->status_badge['class'] }}">
                                    {{ $record->status_badge['label'] }}
                                </span>
                                <span class="text-sm text-gray-700 font-medium">
                                    Onboarding vom {{ $record->created_at->format('d.m.Y \u\m H:i \U\h\r') }}
                                </span>
                            </div>
                            <span class="text-xs text-gray-400">
                                von {{ $record->createdBy?->name ?? 'Unbekannt' }}
                            </span>
                        </div>

                        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                            {{-- Vorlage --}}
                            @if($record->vorlage)
                            <div>
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Verwendete Vorlage</h4>
                                <div class="p-3 rounded-md border border-indigo-100 bg-indigo-50">
                                    <div class="font-medium text-indigo-800 text-sm">{{ $record->vorlage->name }}</div>
                                    @if($record->vorlage->beschreibung)
                                        <div class="text-xs text-indigo-500 mt-1">{{ $record->vorlage->beschreibung }}</div>
                                    @endif
                                    @if($record->vorlage->abteilung)
                                        <div class="text-xs text-indigo-400 mt-1">{{ $record->vorlage->abteilung->name }}</div>
                                    @endif
                                    <a href="{{ route('onboarding.vorlagen.edit', $record->vorlage) }}"
                                       class="text-xs text-indigo-600 hover:underline mt-2 inline-block">
                                        Vorlage ansehen →
                                    </a>
                                </div>
                            </div>
                            @endif

                            {{-- Snapshot --}}
                            <div>
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Angaben beim Anlegen</h4>
                                @php $snap = $record->ad_attributes_snapshot ?? []; @endphp
                                <dl class="space-y-1.5 text-sm">
                                    @foreach([
                                        'Benutzername' => $record->samaccountname,
                                        'UPN'          => $record->upn,
                                        'Rufnummer'    => $record->rufnummer,
                                        'Mobilnummer'  => $snap['mobile'] ?? null,
                                        'Büro'         => $snap['buero'] ?? null,
                                    ] as $label => $val)
                                        @if($val)
                                        <div class="flex gap-2">
                                            <dt class="text-gray-400 w-28 shrink-0">{{ $label }}</dt>
                                            <dd class="text-gray-700 font-medium font-mono text-xs">{{ $val }}</dd>
                                        </div>
                                        @endif
                                    @endforeach
                                </dl>
                            </div>

                            {{-- Mails --}}
                            <div>
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">E-Mail-Versand</h4>
                                <dl class="space-y-1.5 text-sm">
                                    <div class="flex gap-2 items-center">
                                        <dt class="text-gray-400 w-28 shrink-0">Begrüßungsmail</dt>
                                        <dd>
                                            @if($record->welcome_mail_sent_at)
                                                <span class="text-green-600 text-xs">✓ {{ $record->welcome_mail_sent_at->format('d.m.Y H:i') }}</span>
                                            @else
                                                <span class="text-gray-400 text-xs">nicht gesendet</span>
                                            @endif
                                        </dd>
                                    </div>
                                    <div class="flex gap-2 items-center">
                                        <dt class="text-gray-400 w-28 shrink-0">Vorgesetzten-Mail</dt>
                                        <dd>
                                            @if($record->supervisor_mail_sent_at)
                                                <span class="text-green-600 text-xs">✓ {{ $record->supervisor_mail_sent_at->format('d.m.Y H:i') }}</span>
                                            @else
                                                <span class="text-gray-400 text-xs">nicht gesendet</span>
                                            @endif
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            {{-- Fehler / Warnung --}}
                            @if($record->error_message)
                            <div class="sm:col-span-2">
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Hinweis / Fehler</h4>
                                <div class="p-3 rounded-md bg-amber-50 border border-amber-200 text-xs text-amber-800 break-all">
                                    {{ $record->error_message }}
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="px-6 pb-4">
                            <a href="{{ route('onboarding.records.show', $record) }}"
                               class="text-xs text-indigo-600 hover:underline">Vollständiges Onboarding-Protokoll ansehen →</a>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>

            {{-- ══════════════ TAB: VERGLEICHEN ══════════════ --}}
            <div x-show="activeTab === 'vergleich'" class="space-y-4">

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Benutzer vergleichen</h3>

                    <div class="flex flex-wrap gap-3 items-start">
                        {{-- Suche nach einzelnem Benutzer --}}
                        <div class="flex-1 min-w-[220px] relative" x-data>
                            <input type="text"
                                   x-model="compareSearch"
                                   @input.debounce.300ms="
                                       if (compareSearch.length >= 2) {
                                           compareSearchLoading = true;
                                           fetch('/adusers/search-json?q=' + encodeURIComponent(compareSearch))
                                               .then(r => r.json())
                                               .then(d => { compareSearchResults = d; compareSearchLoading = false; })
                                               .catch(() => { compareSearchLoading = false; });
                                       } else { compareSearchResults = []; }
                                   "
                                   placeholder="Benutzer suchen (min. 2 Zeichen) …"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-8">
                            <div x-show="compareSearchLoading" class="absolute right-2 top-2">
                                <svg class="animate-spin w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </div>
                            <div x-show="compareSearchResults.length > 0 && compareSearch.length >= 2"
                                 class="absolute top-full left-0 right-0 z-20 mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-y-auto">
                                <template x-for="u in compareSearchResults" :key="u.id">
                                    <button @click="compareWith(u.id)"
                                            class="w-full text-left px-4 py-2.5 hover:bg-indigo-50 text-sm border-b border-gray-50 last:border-0">
                                        <div class="font-medium text-gray-800" x-text="u.name"></div>
                                        <div class="text-xs text-gray-400 font-mono" x-text="u.sam + ' · ' + u.abteilung"></div>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- OU-Vergleich --}}
                        @if($user->distinguished_name)
                        <button @click="compareOu()"
                                :disabled="ouLoading"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-md text-sm font-medium hover:bg-indigo-100 disabled:opacity-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <span x-text="ouLoading ? 'Wird geladen …' : 'Mit OU vergleichen'"></span>
                        </button>
                        @endif
                    </div>
                </div>

                {{-- Einzelvergleich Ergebnis --}}
                <div x-show="compareLoading" class="text-center py-8 text-sm text-gray-400">
                    <svg class="animate-spin w-6 h-6 mx-auto mb-2 text-indigo-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Vergleich wird geladen …
                </div>

                <div x-show="compareResult !== null" class="space-y-4">
                    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-gray-700">
                                Vergleich: <span class="text-indigo-700">{{ $user->anzeigename_or_name }}</span>
                                <span class="text-gray-400 mx-2">vs.</span>
                                <span class="text-indigo-700" x-text="compareResult?.target?.name"></span>
                            </h4>
                            <button @click="compareResult = null" class="text-gray-400 hover:text-gray-600 text-xs">✕ schließen</button>
                        </div>

                        {{-- Attributvergleich --}}
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm divide-y divide-gray-100">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 w-32">Attribut</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-medium text-indigo-600">{{ $user->anzeigename_or_name }}</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-medium text-indigo-600" x-text="compareResult?.target?.name"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <template x-for="attr in compareResult?.attributes ?? []" :key="attr.label">
                                        <tr :class="attr.diff ? 'bg-amber-50' : ''">
                                            <td class="px-4 py-2 text-gray-500 text-xs font-medium" x-text="attr.label"></td>
                                            <td class="px-4 py-2 text-gray-800 text-xs" :class="attr.diff ? 'font-semibold text-amber-800' : ''" x-text="attr.user1 || '–'"></td>
                                            <td class="px-4 py-2 text-gray-800 text-xs" :class="attr.diff ? 'font-semibold text-amber-800' : ''" x-text="attr.user2 || '–'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        {{-- Gruppenvergleich --}}
                        <div class="p-6 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                    Nur <span class="text-indigo-700">{{ $user->anzeigename_or_name }}</span>
                                    (<span x-text="(compareResult?.groups?.only_user1 ?? []).length"></span>)
                                </h5>
                                <template x-for="dn in compareResult?.groups?.only_user1 ?? []" :key="dn">
                                    <div class="text-xs text-red-700 bg-red-50 rounded px-2 py-1 mb-1 break-all" x-text="dn.match(/^CN=([^,]+)/i)?.[1] ?? dn"></div>
                                </template>
                                <p x-show="(compareResult?.groups?.only_user1 ?? []).length === 0" class="text-xs text-gray-400 italic">Keine exklusiven Gruppen</p>
                            </div>
                            <div>
                                <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                    Gemeinsame Gruppen
                                    (<span x-text="(compareResult?.groups?.common ?? []).length"></span>)
                                </h5>
                                <template x-for="dn in compareResult?.groups?.common ?? []" :key="dn">
                                    <div class="text-xs text-gray-600 bg-gray-50 rounded px-2 py-1 mb-1 break-all" x-text="dn.match(/^CN=([^,]+)/i)?.[1] ?? dn"></div>
                                </template>
                            </div>
                            <div>
                                <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                    Nur <span class="text-indigo-700" x-text="compareResult?.target?.name"></span>
                                    (<span x-text="(compareResult?.groups?.only_user2 ?? []).length"></span>)
                                </h5>
                                <template x-for="dn in compareResult?.groups?.only_user2 ?? []" :key="dn">
                                    <div class="text-xs text-blue-700 bg-blue-50 rounded px-2 py-1 mb-1 break-all" x-text="dn.match(/^CN=([^,]+)/i)?.[1] ?? dn"></div>
                                </template>
                                <p x-show="(compareResult?.groups?.only_user2 ?? []).length === 0" class="text-xs text-gray-400 italic">Keine exklusiven Gruppen</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- OU-Vergleich Ergebnis --}}
                <div x-show="ouLoading" class="text-center py-8 text-sm text-gray-400">
                    <svg class="animate-spin w-6 h-6 mx-auto mb-2 text-indigo-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    OU-Vergleich wird geladen …
                </div>

                <div x-show="ouResult !== null" class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700">OU-Gruppenvergleich</h4>
                            <p class="text-xs text-gray-400 mt-0.5 break-all" x-text="ouResult?.ou"></p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-500" x-text="ouResult?.users_count + ' andere Benutzer in der OU'"></span>
                            <button @click="ouResult = null" class="text-gray-400 hover:text-gray-600 text-xs">✕</button>
                        </div>
                    </div>

                    <div x-show="ouResult?.users_count === 0" class="p-6 text-sm text-gray-400">
                        Keine anderen Benutzer in derselben OU gefunden.
                    </div>

                    <div x-show="(ouResult?.groups_analysis ?? []).length > 0" class="overflow-x-auto">
                        <table class="min-w-full text-sm divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500">Gruppe</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 w-32">Dieser Benutzer</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 w-44">Verbreitung in OU</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <template x-for="g in ouResult?.groups_analysis ?? []" :key="g.dn">
                                    <tr :class="g.notable ? (g.i_have && g.pct <= 20 ? 'bg-amber-50' : (!g.i_have && g.pct >= 80 ? 'bg-red-50' : '')) : ''">
                                        <td class="px-4 py-2">
                                            <div class="font-medium text-gray-800 text-xs" x-text="g.name"></div>
                                            <div class="text-xs text-gray-400 break-all" x-text="g.dn"></div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <span x-show="g.i_have" class="inline-flex items-center gap-1 text-xs text-green-700 font-medium">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                Ja
                                            </span>
                                            <span x-show="!g.i_have" class="inline-flex items-center gap-1 text-xs text-gray-400">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                                Nein
                                            </span>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="flex items-center gap-2">
                                                <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full"
                                                         :class="g.pct >= 80 ? 'bg-indigo-500' : g.pct >= 50 ? 'bg-indigo-300' : 'bg-gray-300'"
                                                         :style="'width:' + g.pct + '%'"></div>
                                                </div>
                                                <span class="text-xs text-gray-500 w-16 shrink-0"
                                                      x-text="g.count + '/' + g.total + ' (' + g.pct + '%)'"></span>
                                            </div>
                                            <div x-show="g.notable && !g.i_have && g.pct >= 80"
                                                 class="text-xs text-red-600 mt-1 font-medium">
                                                ⚠ Gruppe fehlt (fast alle haben sie)
                                            </div>
                                            <div x-show="g.notable && g.i_have && g.pct <= 20"
                                                 class="text-xs text-amber-600 mt-1 font-medium">
                                                ⚠ Exklusive Gruppe (kaum jemand hat sie)
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
