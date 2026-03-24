<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Persönlicher Bereich</h2>
    </x-slot>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        marked.setOptions({ breaks: true });
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.md-render[data-md]').forEach(function (el) {
                el.innerHTML = marked.parse(el.dataset.md);
            });
        });
    </script>
    @endpush

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                 class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- PROFIL-HEADER --}}
        <div class="bg-white shadow rounded-xl overflow-hidden">
            <div class="h-24 bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-500"></div>
            <div class="px-6 pb-6">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 -mt-12">
                    <div x-data="{ preview: null }">
                        <form id="avatar-form" action="{{ route('personal.avatar') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <label class="relative cursor-pointer group block w-24 h-24" title="Profilbild ändern">
                                <div class="w-24 h-24 rounded-full ring-4 ring-white shadow-lg overflow-hidden bg-indigo-600 flex items-center justify-center">
                                    <template x-if="preview"><img :src="preview" class="w-full h-full object-cover"></template>
                                    <template x-if="!preview">
                                        @if($user->avatarUrl())
                                            <img src="{{ $user->avatarUrl() }}" class="w-full h-full object-cover" alt="">
                                        @else
                                            <span class="text-white text-3xl font-bold select-none">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                        @endif
                                    </template>
                                </div>
                                <div class="absolute inset-0 rounded-full bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <input type="file" name="avatar" accept="image/*" class="hidden"
                                       @change="
                                           if ($event.target.files[0]) {
                                               preview = URL.createObjectURL($event.target.files[0]);
                                               $nextTick(() => document.getElementById('avatar-form').submit());
                                           }
                                       ">
                            </label>
                        </form>
                    </div>
                    <div class="flex-1 min-w-0 sm:ml-4">
                        <h3 class="text-2xl font-bold text-gray-900 leading-tight">{{ $user->name }}</h3>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $user->email }}</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach($user->getRoleNames() as $role)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">{{ $role }}</span>
                            @endforeach
                        </div>
                    </div>
                    @if($user->last_login_at)
                    <div class="flex-shrink-0 text-right">
                        <p class="text-xs text-gray-400">Letzter Login</p>
                        <p class="text-sm font-medium text-gray-600">{{ $user->last_login_at->format('d.m.Y H:i') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- MEINE GRUPPEN (volle Breite, mit Kollegen) --}}
        <div class="bg-white shadow rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <h3 class="text-sm font-semibold text-gray-800">Meine Gruppen</h3>
            </div>
            @if($user->gruppen->isEmpty())
                <div class="px-6 py-8 text-sm text-gray-400 text-center">Keiner Gruppe zugeordnet.</div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($user->gruppen as $gruppe)
                    <div x-data="{ open: false }" class="px-6 py-4">
                        {{-- Gruppenzeile --}}
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-semibold text-gray-800">{{ $gruppe->name }}</span>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($gruppe->roles as $role)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ $role->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                            @if($gruppe->users->isNotEmpty())
                            <button @click="open = !open" type="button"
                                    class="flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-700 flex-shrink-0">
                                <span x-text="open ? 'Kollegen ausblenden' : '{{ $gruppe->users->count() }} Kollege(n) anzeigen'"></span>
                                <svg :class="open ? 'rotate-180' : ''" class="w-3.5 h-3.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            @endif
                        </div>
                        {{-- Kollegenliste --}}
                        @if($gruppe->users->isNotEmpty())
                        <div x-show="open" x-cloak class="mt-3 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
                            @foreach($gruppe->users->sortBy('name') as $kollege)
                            <div class="flex items-center gap-2 p-2 rounded-lg bg-gray-50 border border-gray-100">
                                <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0 text-xs font-bold text-indigo-600 overflow-hidden">
                                    @if($kollege->avatarUrl())
                                        <img src="{{ $kollege->avatarUrl() }}" class="w-full h-full object-cover" alt="">
                                    @else
                                        {{ strtoupper(substr($kollege->name, 0, 1)) }}
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-gray-800 truncate {{ $kollege->id === $user->id ? 'text-indigo-600' : '' }}">
                                        {{ $kollege->name }}{{ $kollege->id === $user->id ? ' (ich)' : '' }}
                                    </p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ROLLEN & AUFGABEN + STELLE (2 Spalten) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- ROLLEN & AUFGABEN --}}
            <div class="bg-white shadow rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-gray-800">Meine Rollen & Aufgaben</h3>
                    <span class="ml-auto text-xs text-gray-400">{{ $aufgabenZuweisungen->count() }}</span>
                </div>
                @if($aufgabenZuweisungen->isEmpty())
                    <div class="px-6 py-8 text-sm text-gray-400 text-center">Keine Aufgaben zugeordnet.</div>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach($aufgabenZuweisungen as $az)
                        <li x-data="{ open: false }" class="px-5 py-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <a href="{{ route('aufgaben.show', $az->aufgabe) }}"
                                           class="text-sm font-medium text-gray-800 hover:text-indigo-600">
                                            {{ $az->aufgabe?->name ?? '—' }}
                                        </a>
                                        @if($az->admin_user_id === Auth::id())
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">Admin</span>
                                        @endif
                                        @if($az->stellvertreter_user_id === Auth::id())
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Stellvertreter</span>
                                        @endif
                                    </div>
                                    @if($az->gruppe)
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $az->gruppe->name }}</p>
                                    @endif
                                </div>
                                @if($az->aufgabe?->beschreibung)
                                <button @click="open = !open" type="button"
                                        class="flex-shrink-0 p-1 rounded hover:bg-gray-100 text-gray-400 hover:text-indigo-600 transition-colors"
                                        title="Beschreibung">
                                    <svg :class="open ? 'rotate-180' : ''" class="transition-transform" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                            @if($az->aufgabe?->beschreibung)
                            <div x-show="open" x-cloak class="mt-2">
                                <div class="md-render text-xs text-gray-600 leading-relaxed bg-gray-50 rounded p-3 border-l-2 border-indigo-200
                                            [&_strong]:font-semibold [&_em]:italic [&_ul]:list-disc [&_ul]:pl-4 [&_ol]:list-decimal [&_ol]:pl-4
                                            [&_p]:mb-1 [&_li]:mb-0.5 [&_h1]:font-bold [&_h2]:font-semibold [&_h3]:font-semibold
                                            [&_code]:bg-gray-200 [&_code]:px-1 [&_code]:rounded [&_code]:font-mono"
                                     data-md="{{ e($az->aufgabe->beschreibung) }}"></div>
                            </div>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- MEINE STELLE --}}
            <div class="bg-white shadow rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-gray-800">Meine Stelle</h3>
                </div>
                @if(!$stelle)
                    <div class="px-6 py-8 text-sm text-gray-400 text-center">Keine Stelle zugeordnet.</div>
                @else
                    <div class="px-6 py-5 space-y-4">
                        {{-- Titel --}}
                        <div>
                            <p class="text-lg font-bold text-gray-900 leading-snug">
                                {{ $stelle->stellenbeschreibung?->bezeichnung ?? '—' }}
                            </p>
                            @if($stelle->stellennummer)
                                <p class="text-xs font-mono text-gray-400 mt-0.5">{{ $stelle->stellennummer }}</p>
                            @endif
                        </div>

                        {{-- Details --}}
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm border-t border-gray-100 pt-4">
                            @if($stelle->gruppe)
                            <div>
                                <dt class="text-xs text-gray-400 uppercase tracking-wide">Gruppe</dt>
                                <dd class="font-medium text-gray-700 mt-0.5">{{ $stelle->gruppe->name }}</dd>
                            </div>
                            @endif
                            @if($stelle->belegung !== null)
                            <div>
                                <dt class="text-xs text-gray-400 uppercase tracking-wide">Belegung</dt>
                                <dd class="font-medium text-gray-700 mt-0.5">{{ number_format($stelle->belegung, 0) }} %</dd>
                            </div>
                            @endif
                            @if($stelle->haushalt_bewertung)
                            <div>
                                <dt class="text-xs text-gray-400 uppercase tracking-wide">HH-Bewertung</dt>
                                <dd class="mt-0.5"><span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">{{ $stelle->haushalt_bewertung }}</span></dd>
                            </div>
                            @endif
                            @if($stelle->bes_gruppe)
                            <div>
                                <dt class="text-xs text-gray-400 uppercase tracking-wide">Bes.-Gruppe</dt>
                                <dd class="mt-0.5"><span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">{{ $stelle->bes_gruppe }}</span></dd>
                            </div>
                            @endif
                        </dl>

                        {{-- Arbeitsvorgänge --}}
                        @if($stelle->stellenbeschreibung?->arbeitsvorgaenge->isNotEmpty())
                        <div class="border-t border-gray-100 pt-4">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Arbeitsvorgänge</p>
                            <ul class="space-y-2">
                                @foreach($stelle->stellenbeschreibung->arbeitsvorgaenge as $av)
                                <li x-data="{ open: false }">
                                    <div class="flex items-center justify-between gap-3 cursor-pointer group"
                                         @click="open = !open">
                                        {{-- Balken --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-sm text-gray-700 font-medium truncate mr-2">{{ $av->betreff }}</span>
                                                <span class="flex-shrink-0 text-sm font-bold text-indigo-600">{{ $av->anteil }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-100 rounded-full h-1.5">
                                                <div class="bg-indigo-400 h-1.5 rounded-full" style="width: {{ min($av->anteil, 100) }}%"></div>
                                            </div>
                                        </div>
                                        @if($av->beschreibung)
                                        <button type="button" class="flex-shrink-0 p-1 rounded hover:bg-gray-100 text-gray-400 group-hover:text-indigo-500 transition-colors">
                                            <svg :class="open ? 'rotate-180' : ''" class="transition-transform" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        @endif
                                    </div>
                                    @if($av->beschreibung)
                                    <div x-show="open" x-cloak class="mt-2 ml-0">
                                        <div class="md-render text-xs text-gray-600 leading-relaxed bg-gray-50 rounded p-3 border-l-2 border-indigo-200
                                                    [&_strong]:font-semibold [&_em]:italic [&_ul]:list-disc [&_ul]:pl-4 [&_ol]:list-decimal [&_ol]:pl-4
                                                    [&_p]:mb-1 [&_li]:mb-0.5 [&_code]:bg-gray-200 [&_code]:px-1 [&_code]:rounded [&_code]:font-mono"
                                             data-md="{{ e($av->beschreibung) }}"></div>
                                    </div>
                                    @endif
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <a href="{{ route('stellen.show', $stelle) }}"
                           class="block text-center text-xs text-indigo-600 hover:text-indigo-800 font-medium pt-3 border-t border-gray-100">
                            Vollständige Beschreibung ansehen →
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- BESTELLUNGEN + ANKÜNDIGUNGEN --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Bestellungen --}}
            <div class="bg-white shadow rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-gray-800">Meine Bestellungen</h3>
                    </div>
                    @if($bestellungen->isNotEmpty())
                        <a href="{{ route('orders.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Alle ansehen →</a>
                    @endif
                </div>
                @if($bestellungen->isEmpty())
                    <div class="px-6 py-8 text-sm text-gray-400 text-center">Keine Bestellungen vorhanden.</div>
                @else
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Artikel</th>
                                <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 uppercase">Preis</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($bestellungen as $bestellung)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">{{ $bestellung->order_date->format('d.m.Y') }}</td>
                                <td class="px-4 py-2.5 text-gray-800 max-w-xs truncate">{{ $bestellung->subject }}</td>
                                <td class="px-4 py-2.5 text-right text-gray-700 whitespace-nowrap font-medium">{{ number_format($bestellung->price_gross, 2, ',', '.') }} €</td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $bestellung->status == 6 ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ \App\Models\Order::STATUS_LABELS[$bestellung->status] ?? $bestellung->status }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Ankündigungen --}}
            <div class="bg-white shadow rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-gray-800">Aktuelle Ankündigungen</h3>
                    </div>
                    <a href="{{ route('announcements.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Alle ansehen →</a>
                </div>
                @if($ankuendigungen->isEmpty())
                    <div class="px-6 py-8 text-sm text-gray-400 text-center">Keine aktiven Ankündigungen.</div>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach($ankuendigungen as $a)
                        <li class="px-6 py-3.5 flex items-start gap-3">
                            <span class="flex-shrink-0 mt-1.5 w-2 h-2 rounded-full
                                {{ $a->type === 'critical' ? 'bg-red-500' : ($a->type === 'maintenance' ? 'bg-amber-500' : 'bg-blue-400') }}">
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-700">{{ $a->message }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $a->starts_at->format('d.m.Y H:i') }}</p>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
