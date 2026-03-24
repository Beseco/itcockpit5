<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kalender</h2>
    </x-slot>

    <div class="py-6" x-data="calendarApp()" x-init="init()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- ICS-Abonnement --}}
            <div class="flex items-center justify-between gap-3 px-4 py-2 bg-white border border-gray-200 rounded-lg shadow-sm text-sm">
                <div class="flex items-center gap-2 text-gray-600 min-w-0">
                    <svg class="w-4 h-4 flex-shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="font-medium text-gray-700">Kalender abonnieren (ICS):</span>
                    <code id="ics-url" class="truncate text-indigo-600 font-mono text-xs">{{ url(route('calendar.ics', auth()->user()->ics_token, false)) }}</code>
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
                    right:  'dayGridMonth,timeGridWeek,listWeek',
                },
                buttonText: {
                    today: 'Heute', month: 'Monat', week: 'Woche', list: 'Liste',
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
                    ? event.start.toISOString().slice(0,16)
                    : (event.allDay ? event.startStr : event.start.toISOString().slice(0,16)),
                end_at:    event.end
                    ? (event.allDay ? '' : event.end.toISOString().slice(0,16))
                    : '',
                ganztag:   event.allDay,
                typ:       p.typ ?? 'termin',
                farbe:     p.farbe ?? '',
                erinnerung: p.erinnerung ?? '',
                attendeeEmails: (p.attendees ?? []).map(a => a.email),
                attendees:  p.attendees ?? [],
                intervall:  p.intervall ?? '',
            };
        },

        closeModal() {
            this.modal.open = false;
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
                attendees:          this.modal.attendeeEmails,
                _token:             document.querySelector('meta[name="csrf-token"]').content,
            };

            try {
                const url    = this.modal.id
                    ? `/calendar/events/${this.modal.id}`
                    : `/calendar/events`;
                const method = this.modal.id ? 'PUT' : 'POST';

                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': payload._token },
                    body: JSON.stringify(payload),
                });

                if (!res.ok) throw new Error('Fehler beim Speichern');
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

            try {
                await fetch(`/calendar/events/${this.modal.id}`, {
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
