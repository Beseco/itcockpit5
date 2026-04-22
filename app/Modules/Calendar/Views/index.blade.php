<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kalender</h2>
            <a href="{{ route('calendar.help') }}" title="Hilfe & Anleitung"
               class="inline-flex items-center justify-center w-7 h-7 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-full transition">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            </a>
        </div>
    </x-slot>

    <div class="py-6" x-data="calendarApp()" x-init="init()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Aktionsleiste --}}
            <div class="flex items-center justify-between">
                <button @click="openNew('{{ now()->format('Y-m-d') }}', false)"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Neuer Termin
                </button>
            </div>

            {{-- ICS-Abonnement --}}
            <div class="flex items-center justify-between gap-3 px-4 py-2 bg-white border border-gray-200 rounded-lg shadow-sm text-sm">
                <div class="flex items-center gap-2 text-gray-600 min-w-0">
                    <svg class="w-4 h-4 flex-shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="font-medium text-gray-700">Kalender abonnieren (ICS):</span>
                    <code id="ics-url" class="truncate text-indigo-600 font-mono text-xs">{{ url(route('calendar.ics', $icsToken, false)) }}</code>
                </div>
                <button onclick="navigator.clipboard.writeText(document.getElementById('ics-url').textContent);this.textContent='✓ Kopiert!';setTimeout(()=>this.textContent='Kopieren',2000)"
                        class="flex-shrink-0 px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-medium rounded-md transition-colors">
                    Kopieren
                </button>
            </div>

            {{-- Kalender --}}
            <div class="bg-white shadow-sm rounded-lg p-4">
                <div id="calendar"></div>
            </div>

        </div>

        {{-- ─── Event-Modal ────────────────────────────────────────────── --}}
        <div x-show="modal.open" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             @keydown.escape.window="closeModal()">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="closeModal()"></div>

                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-xl">
                    {{-- Header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900"
                            x-text="modal.id ? (modal.canEdit ? 'Termin bearbeiten' : 'Termin') : 'Neuer Termin'"></h3>
                        <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="px-6 py-4 space-y-4">

                        {{-- Read-only Reminder-Anzeige --}}
                        <template x-if="modal.type === 'reminder'">
                            <div class="space-y-3">
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <p class="text-sm font-medium text-gray-700 mb-1">Erinnerungsmail</p>
                                    <p class="text-sm text-gray-600" x-text="modal.intervall"></p>
                                </div>
                                <div x-show="modal.beschreibung" class="prose prose-sm max-w-none p-3 bg-gray-50 rounded-lg text-gray-700 text-sm" x-html="modal.beschreibung"></div>
                            </div>
                        </template>

                        {{-- Editierbarer Termin --}}
                        <template x-if="modal.type !== 'reminder'">
                            <div class="space-y-4">

                                {{-- Titel --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Titel *</label>
                                    <input type="text" x-model="modal.titel" :readonly="!modal.canEdit"
                                           class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="Titel des Termins">
                                </div>

                                {{-- Start / End --}}
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Start *
                                        </label>
                                        <input x-model="modal.start_at" :readonly="!modal.canEdit"
                                               :type="modal.ganztag ? 'date' : 'datetime-local'"
                                               class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Ende</label>
                                        <input x-model="modal.end_at" :readonly="!modal.canEdit"
                                               :type="modal.ganztag ? 'date' : 'datetime-local'"
                                               class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                </div>

                                {{-- Ganztag --}}
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="modal.ganztag" :disabled="!modal.canEdit"
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Ganztägig</span>
                                </label>

                                {{-- Typ + Farbe --}}
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Typ</label>
                                        <select x-model="modal.typ" :disabled="!modal.canEdit"
                                                class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @foreach($eventTypen as $val => $lbl)
                                                <option value="{{ $val }}">{{ $lbl }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Farbe</label>
                                        <div class="flex items-center gap-2">
                                            <input type="color" x-model="modal.farbe" :disabled="!modal.canEdit"
                                                   class="h-9 w-16 rounded border-gray-300 cursor-pointer">
                                            <button type="button" @click="modal.farbe = ''" x-show="modal.canEdit"
                                                    class="text-xs text-gray-500 hover:text-gray-700">Standard</button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Erinnerung --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Erinnerung</label>
                                    <select x-model="modal.erinnerung" :disabled="!modal.canEdit"
                                            class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach($erinnerungOptionen as $min => $lbl)
                                            <option value="{{ $min }}">{{ $lbl }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Wiederholung --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Wiederholung</label>
                                    <div class="flex flex-wrap gap-1.5 mb-3">
                                        @foreach([''=>'Keine','daily'=>'Täglich','weekly'=>'Wöchentlich','monthly'=>'Monatlich','yearly'=>'Jährlich'] as $val => $lbl)
                                        <button type="button"
                                                @click="modal.wiederholung_typ = '{{ $val }}'"
                                                :disabled="!modal.canEdit"
                                                :class="modal.wiederholung_typ === '{{ $val }}'
                                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                                    : 'bg-white text-gray-700 border-gray-300 hover:border-indigo-400'"
                                                class="px-3 py-1.5 text-xs font-medium border rounded-md transition-colors">
                                            {{ $lbl }}
                                        </button>
                                        @endforeach
                                    </div>

                                    {{-- täglich: alle X Tage --}}
                                    <div x-show="modal.wiederholung_typ === 'daily'" x-cloak class="flex items-center gap-2 mb-2">
                                        <span class="text-sm text-gray-600">Alle</span>
                                        <input type="number" min="1" :disabled="!modal.canEdit"
                                               :value="modal.wiederholung_config?.every ?? 1"
                                               @input="modal.wiederholung_config = { every: parseInt($event.target.value) || 1 }"
                                               class="w-16 border-gray-300 rounded-md shadow-sm text-sm">
                                        <span class="text-sm text-gray-600">Tag(e)</span>
                                    </div>

                                    {{-- wöchentlich: Wochentage --}}
                                    <div x-show="modal.wiederholung_typ === 'weekly'" x-cloak class="mb-2">
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach(['Mo','Di','Mi','Do','Fr','Sa','So'] as $wt)
                                            <button type="button" :disabled="!modal.canEdit"
                                                    @click="toggleWDay('{{ $wt }}')"
                                                    :class="(modal.wiederholung_config?.days ?? []).includes('{{ $wt }}')
                                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                                        : 'bg-white text-gray-700 border-gray-300'"
                                                    class="px-2.5 py-1 text-xs font-medium border rounded-md transition-colors">
                                                {{ $wt }}
                                            </button>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- monatlich: alle X Monate --}}
                                    <div x-show="modal.wiederholung_typ === 'monthly'" x-cloak class="flex items-center gap-2 mb-2">
                                        <span class="text-sm text-gray-600">Alle</span>
                                        <input type="number" min="1" :disabled="!modal.canEdit"
                                               :value="modal.wiederholung_config?.every ?? 1"
                                               @input="modal.wiederholung_config = { every: parseInt($event.target.value) || 1 }"
                                               class="w-16 border-gray-300 rounded-md shadow-sm text-sm">
                                        <span class="text-sm text-gray-600">Monat(e)</span>
                                    </div>

                                    {{-- Wiederholen bis --}}
                                    <div x-show="modal.wiederholung_typ" x-cloak class="flex items-center gap-2 mt-2">
                                        <span class="text-sm text-gray-600">Bis</span>
                                        <input type="date" x-model="modal.wiederholung_bis" :disabled="!modal.canEdit"
                                               class="border-gray-300 rounded-md shadow-sm text-sm">
                                        <button type="button" x-show="modal.wiederholung_bis" @click="modal.wiederholung_bis = ''"
                                                class="text-xs text-gray-400 hover:text-gray-600">✕ kein Ende</button>
                                    </div>
                                </div>

                                {{-- Teilnehmer --}}
                                <div x-show="modal.canEdit" x-data="emailTagInput(modal.attendeeEmails, {{ json_encode($allUsers->pluck('email')->filter()->values()) }})">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Teilnehmer einladen</label>
                                    <div class="min-h-[38px] flex flex-wrap gap-1.5 items-center px-3 py-2
                                                border border-gray-300 rounded-md bg-white focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500"
                                         @click="$refs.attendeeInput.focus()">
                                        <template x-for="(email, i) in tags" :key="i">
                                            <span class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 bg-indigo-100 text-indigo-800 text-xs rounded-full">
                                                <span x-text="email"></span>
                                                <button type="button" @click.stop="removeTag(i)"
                                                        class="w-3.5 h-3.5 rounded-full hover:bg-indigo-200 flex items-center justify-center font-bold">×</button>
                                            </span>
                                        </template>
                                        <input x-ref="attendeeInput" type="text" x-model="input"
                                               @focus="open = true" @input="open = true"
                                               @keydown="handleKey($event)" @keydown.escape="open = false"
                                               @change="modal.attendeeEmails = tags"
                                               @blur="modal.attendeeEmails = tags"
                                               x-effect="modal.attendeeEmails = tags"
                                               placeholder="E-Mail eingeben …"
                                               class="flex-1 min-w-[160px] border-none outline-none p-0 text-sm bg-transparent focus:ring-0">
                                    </div>
                                    <div x-show="open && filtered.length > 0" x-cloak class="relative">
                                        <ul class="absolute z-50 w-full bg-white border border-gray-200 rounded-md shadow-lg mt-1 max-h-40 overflow-y-auto text-sm">
                                            <template x-for="s in filtered" :key="s">
                                                <li @mousedown.prevent="addTag(s)"
                                                    class="px-3 py-2 cursor-pointer hover:bg-indigo-50 hover:text-indigo-700" x-text="s"></li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>

                                {{-- Teilnehmer-Liste (read-only) --}}
                                <div x-show="!modal.canEdit && modal.attendees && modal.attendees.length > 0">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Teilnehmer</label>
                                    <div class="flex flex-wrap gap-1.5">
                                        <template x-for="a in modal.attendees" :key="a.email">
                                            <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs rounded-full"
                                                  x-text="a.name || a.email"></span>
                                        </template>
                                    </div>
                                </div>

                                {{-- Beschreibung --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Beschreibung</label>
                                    <textarea x-model="modal.beschreibung" :readonly="!modal.canEdit" rows="3"
                                              class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                              placeholder="Optional: Markdown unterstützt"></textarea>
                                </div>

                            </div>
                        </template>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                        <div>
                            <button x-show="modal.id && modal.canEdit && modal.type !== 'reminder'"
                                    @click="deleteEvent()"
                                    class="text-sm text-red-600 hover:text-red-800 font-medium">
                                Löschen
                            </button>
                        </div>
                        <div class="flex gap-3">
                            <button @click="closeModal()"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                <span x-text="modal.canEdit && modal.type !== 'reminder' ? 'Abbrechen' : 'Schließen'"></span>
                            </button>
                            <button x-show="modal.canEdit && modal.type !== 'reminder'"
                                    @click="saveEvent()"
                                    :disabled="saving"
                                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md disabled:opacity-50">
                                <span x-text="saving ? 'Speichern …' : 'Speichern'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('styles')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css' rel='stylesheet' />
<style>
    [x-cloak] { display: none !important; }
    .fc-toolbar-title { font-size: 1rem !important; font-weight: 600; }
    .fc-button { font-size: 0.8rem !important; }
    .fc-event { cursor: pointer; border-radius: 4px !important; }
    .fc-daygrid-event { white-space: normal !important; }
</style>
@endpush

@push('scripts')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<script>
function calendarApp() {
    return {
        calendar: null,
        saving: false,
        modal: {
            open: false, id: null, type: 'event', canEdit: true,
            titel: '', beschreibung: '', start_at: '', end_at: '',
            ganztag: false, typ: 'termin', farbe: '#4f46e5',
            erinnerung: '', attendeeEmails: [], attendees: [], intervall: '',
                wiederholung_typ: '', wiederholung_config: {}, wiederholung_bis: '',
            wiederholung_typ: '', wiederholung_config: {}, wiederholung_bis: '',
        },

        init() {
            const self = this;
            const calEl = document.getElementById('calendar');
            this.calendar = new FullCalendar.Calendar(calEl, {
                locale: 'de',
                initialView: 'dayGridMonth',
                height: 'auto',
                firstDay: 1,
                headerToolbar: {
                    left:   'prev,next today',
                    center: 'title',
                    right:  'dayGridMonth,timeGridWeek,list60',
                },
                buttonText: {
                    today: 'Heute', month: 'Monat', week: 'Woche',
                },
                views: {
                    list60: {
                        type: 'list',
                        duration: { days: 60 },
                        buttonText: 'Liste',
                    },
                },
                events: {
                    url: '{{ route('calendar.events') }}',
                    method: 'GET',
                    failure() { console.error('Fehler beim Laden der Termine'); },
                },
                eventClick(info) {
                    self.openEvent(info.event);
                },
                dateClick(info) {
                    self.openNew(info.dateStr, info.allDay);
                },
                eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                slotMinTime: '07:00:00',
                slotMaxTime: '18:00:00',
                scrollTime:  '07:00:00',
                noEventsText: 'Keine Termine',
            });
            this.calendar.render();
        },

        openNew(dateStr, allDay) {
            const start = allDay ? dateStr : dateStr + 'T08:00';
            const end   = allDay ? '' : dateStr + 'T09:00';
            this.modal = {
                open: true, id: null, type: 'event', canEdit: true,
                titel: '', beschreibung: '',
                start_at: start, end_at: end,
                ganztag: allDay, typ: 'termin', farbe: '#4f46e5',
                erinnerung: '', attendeeEmails: [], attendees: [], intervall: '',
                wiederholung_typ: '', wiederholung_config: {}, wiederholung_bis: '',
            };
        },

        openEvent(event) {
            const p = event.extendedProps;
            this.modal = {
                open: true,
                id:        p.dbId ?? null,
                type:      p.type,
                canEdit:   p.canEdit,
                titel:     event.title.replace(/^🔔\s*/, ''),
                beschreibung: p.beschreibung ?? '',
                start_at:  p.type === 'reminder'
                    ? localDT(event.start)
                    : (event.allDay ? event.startStr : localDT(event.start)),
                end_at:    event.end
                    ? (event.allDay ? '' : localDT(event.end))
                    : '',
                ganztag:   event.allDay,
                typ:       p.typ ?? 'termin',
                farbe:     p.farbe ?? '',
                erinnerung: p.erinnerung ?? '',
                attendeeEmails: (p.attendees ?? []).map(a => a.email),
                attendees:  p.attendees ?? [],
                intervall:  p.intervall ?? '',
                wiederholung_typ:    p.wiederholung_typ ?? '',
                wiederholung_config: p.wiederholung_config ?? {},
                wiederholung_bis:    p.wiederholung_bis ?? '',
            };
        },

        closeModal() {
            this.modal.open = false;
        },


        toggleWDay(d) {
            const days = (this.modal.wiederholung_config && this.modal.wiederholung_config.days)
                ? [...this.modal.wiederholung_config.days] : [];
            const idx = days.indexOf(d);
            if (idx >= 0) days.splice(idx, 1); else days.push(d);
            this.modal.wiederholung_config = { days };
        },

        async saveEvent() {
            if (!this.modal.titel.trim()) return;
            this.saving = true;

            const payload = {
                titel:              this.modal.titel,
                beschreibung:       this.modal.beschreibung,
                start_at:           this.modal.ganztag
                    ? this.modal.start_at + ' 00:00:00'
                    : this.modal.start_at,
                end_at:             this.modal.end_at
                    ? (this.modal.ganztag ? this.modal.end_at + ' 00:00:00' : this.modal.end_at)
                    : null,
                ganztag:            this.modal.ganztag ? 1 : 0,
                typ:                this.modal.typ,
                farbe:              this.modal.farbe || null,
                erinnerung_minuten: this.modal.erinnerung || null,
                attendees:              this.modal.attendeeEmails,
                wiederholung_typ:       this.modal.wiederholung_typ || null,
                wiederholung_config:    this.modal.wiederholung_config || null,
                wiederholung_bis:       this.modal.wiederholung_bis || null,
                _token:             document.querySelector('meta[name="csrf-token"]').content,
            };

            try {
                const storeUrl  = '{{ route('calendar.events.store') }}';
                const updateUrl = '{{ route('calendar.events.update', ['event' => '__ID__']) }}';
                const url    = this.modal.id ? updateUrl.replace('__ID__', this.modal.id) : storeUrl;
                const method = this.modal.id ? 'PUT' : 'POST';

                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': payload._token },
                    body: JSON.stringify(payload),
                });

                if (!res.ok) {
                    let msg = 'HTTP ' + res.status;
                    try {
                        const body = await res.json();
                        if (body.message) msg = body.message;
                        if (body.errors)  msg = Object.values(body.errors).flat().join('\n');
                    } catch {}
                    throw new Error(msg);
                }
                this.calendar.refetchEvents();
                this.closeModal();
            } catch (e) {
                alert('Fehler: ' + e.message);
            } finally {
                this.saving = false;
            }
        },

        async deleteEvent() {
            if (!confirm('Termin wirklich löschen?')) return;
            const token = document.querySelector('meta[name="csrf-token"]').content;

            const deleteUrl = '{{ route('calendar.events.destroy', ['event' => '__ID__']) }}'
                .replace('__ID__', this.modal.id);
            try {
                await fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': token, 'Content-Type': 'application/json' },
                });
                this.calendar.refetchEvents();
                this.closeModal();
            } catch (e) {
                alert('Fehler beim Löschen');
            }
        },
    };
}

function localDT(d) {
    if (!d) return '';
    const pad = n => String(n).padStart(2,'0');
    return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+'T'+pad(d.getHours())+':'+pad(d.getMinutes());
}

// Wiederverwendung der emailTagInput-Funktion aus reminders (inline für Unabhängigkeit)
function emailTagInput(initial, suggestions) {
    return {
        tags: Array.isArray(initial) ? [...initial] : [],
        input: '',
        open: false,
        get filtered() {
            const q = this.input.toLowerCase();
            return suggestions.filter(s => s && s.toLowerCase().includes(q) && !this.tags.includes(s)).slice(0, 8);
        },
        addTag(email) {
            email = (email || '').trim().toLowerCase();
            if (email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) && !this.tags.includes(email)) {
                this.tags.push(email);
            }
            this.input = '';
            this.open  = false;
        },
        removeTag(i) { this.tags.splice(i, 1); },
        handleKey(e) {
            if (['Enter', 'Tab', ',', ';'].includes(e.key)) {
                e.preventDefault();
                if (this.input.trim()) this.addTag(this.input);
                else if (e.key === 'Tab') this.open = false;
            } else if (e.key === 'Backspace' && !this.input && this.tags.length) {
                this.tags.pop();
            }
        },
    };
}
</script>
@endpush
</x-app-layout>
