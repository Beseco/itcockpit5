<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Persönlicher Bereich</h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                 class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-md text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- PROFIL-HEADER --}}
        <div class="bg-white shadow rounded-xl overflow-hidden">
            {{-- Banner --}}
            <div class="h-24 bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-500"></div>

            <div class="px-6 pb-6">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 -mt-12">

                    {{-- Avatar mit Upload --}}
                    <form action="{{ route('personal.avatar') }}" method="POST" enctype="multipart/form-data"
                          x-data="{ preview: null }"
                          @change="
                              const file = $event.target.files[0];
                              if (file) {
                                  preview = URL.createObjectURL(file);
                                  $el.submit();
                              }
                          ">
                        @csrf
                        <label class="relative cursor-pointer group block w-24 h-24" title="Profilbild ändern">
                            <div class="w-24 h-24 rounded-full ring-4 ring-white shadow-lg overflow-hidden bg-indigo-600 flex items-center justify-center">
                                <template x-if="preview">
                                    <img :src="preview" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!preview">
                                    @if($user->avatarUrl())
                                        <img src="{{ $user->avatarUrl() }}" class="w-full h-full object-cover" alt="">
                                    @else
                                        <span class="text-white text-3xl font-bold select-none">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </span>
                                    @endif
                                </template>
                            </div>
                            {{-- Hover-Overlay --}}
                            <div class="absolute inset-0 rounded-full bg-black bg-opacity-40 flex items-center justify-center
                                        opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <input type="file" name="avatar" accept="image/*" class="hidden">
                        </label>
                    </form>

                    {{-- User Info --}}
                    <div class="flex-1 min-w-0 sm:ml-4">
                        <h3 class="text-2xl font-bold text-gray-900 leading-tight">{{ $user->name }}</h3>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $user->email }}</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach($user->getRoleNames() as $role)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                    {{ $role }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    {{-- Letzter Login --}}
                    @if($user->last_login_at)
                    <div class="flex-shrink-0 text-right">
                        <p class="text-xs text-gray-400">Letzter Login</p>
                        <p class="text-sm font-medium text-gray-600">{{ $user->last_login_at->format('d.m.Y H:i') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- LINKE SPALTE (2/3) --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Gruppen & Rollen --}}
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
                        <div class="divide-y divide-gray-50">
                            @foreach($user->gruppen as $gruppe)
                            <div class="px-6 py-3 flex items-center justify-between gap-4">
                                <span class="text-sm font-medium text-gray-800">{{ $gruppe->name }}</span>
                                <div class="flex flex-wrap gap-1 justify-end">
                                    @forelse($gruppe->roles as $role)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                            {{ $role->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-400">—</span>
                                    @endforelse
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Bestellungen --}}
                <div class="bg-white shadow rounded-xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
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

            {{-- RECHTE SPALTE (1/3) --}}
            <div class="space-y-6">

                {{-- Rollen & Aufgaben --}}
                <div class="bg-white shadow rounded-xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-gray-800">Meine Rollen & Aufgaben</h3>
                    </div>
                    @if($aufgabenZuweisungen->isEmpty())
                        <div class="px-6 py-8 text-sm text-gray-400 text-center">Keine Aufgaben zugeordnet.</div>
                    @else
                        <ul class="divide-y divide-gray-100">
                            @foreach($aufgabenZuweisungen as $az)
                            <li class="px-5 py-3">
                                <p class="text-sm font-medium text-gray-800">{{ $az->aufgabe?->name ?? '—' }}</p>
                                <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                    @if($az->gruppe)
                                        <span class="text-xs text-gray-500">{{ $az->gruppe->name }}</span>
                                    @endif
                                    @if($az->admin_user_id === Auth::id())
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">Admin</span>
                                    @endif
                                    @if($az->stellvertreter_user_id === Auth::id())
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Stellvertreter</span>
                                    @endif
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Meine Stelle --}}
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
                        <div class="px-5 py-4 space-y-3">
                            <div>
                                <p class="text-base font-semibold text-gray-900 leading-snug">
                                    {{ $stelle->stellenbeschreibung?->bezeichnung ?? '—' }}
                                </p>
                                @if($stelle->stellennummer)
                                    <p class="text-xs font-mono text-gray-400 mt-0.5">{{ $stelle->stellennummer }}</p>
                                @endif
                            </div>

                            <dl class="space-y-2 text-sm">
                                @if($stelle->gruppe)
                                <div class="flex justify-between items-center">
                                    <dt class="text-gray-500">Gruppe</dt>
                                    <dd class="font-medium text-gray-700">{{ $stelle->gruppe->name }}</dd>
                                </div>
                                @endif
                                @if($stelle->haushalt_bewertung)
                                <div class="flex justify-between items-center">
                                    <dt class="text-gray-500">HH-Bewertung</dt>
                                    <dd><span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">{{ $stelle->haushalt_bewertung }}</span></dd>
                                </div>
                                @endif
                                @if($stelle->bes_gruppe)
                                <div class="flex justify-between items-center">
                                    <dt class="text-gray-500">Bes.-Gruppe</dt>
                                    <dd><span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">{{ $stelle->bes_gruppe }}</span></dd>
                                </div>
                                @endif
                                @if($stelle->belegung !== null)
                                <div class="flex justify-between items-center">
                                    <dt class="text-gray-500">Belegung</dt>
                                    <dd class="font-medium text-gray-700">{{ number_format($stelle->belegung, 0) }} %</dd>
                                </div>
                                @endif
                            </dl>

                            @if($stelle->stellenbeschreibung?->arbeitsvorgaenge->isNotEmpty())
                            <div class="pt-3 border-t border-gray-100">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Arbeitsvorgänge</p>
                                <ul class="space-y-1.5">
                                    @foreach($stelle->stellenbeschreibung->arbeitsvorgaenge as $av)
                                    <li class="flex items-center justify-between text-xs">
                                        <span class="text-gray-600 truncate mr-2">{{ $av->betreff }}</span>
                                        <span class="flex-shrink-0 font-bold text-indigo-600">{{ $av->anteil }}%</span>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            <a href="{{ route('stellen.show', $stelle) }}"
                               class="block text-center text-xs text-indigo-600 hover:text-indigo-800 font-medium pt-2 border-t border-gray-100">
                                Vollständige Beschreibung ansehen →
                            </a>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
