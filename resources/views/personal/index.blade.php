<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Persönlicher Bereich</h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- LINKE SPALTE (2/3) --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Card 1: Benutzerinfo --}}
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-5 flex items-center gap-5">
                        <div class="flex-shrink-0 h-16 w-16 rounded-full bg-indigo-600 flex items-center justify-center text-white text-2xl font-bold">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xl font-bold text-gray-900">{{ $user->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $user->email }}</p>
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach($user->getRoleNames() as $role)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                        {{ $role }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        @if($user->last_login_at)
                        <div class="flex-shrink-0 text-xs text-gray-400 text-right">
                            Letzter Login:<br>
                            {{ $user->last_login_at->format('d.m.Y H:i') }}
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Card 2: Gruppen & Rollen --}}
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-900">Meine Gruppen</h3>
                    </div>
                    @if($user->gruppen->isEmpty())
                        <div class="px-6 py-6 text-sm text-gray-400 text-center">Keiner Gruppe zugeordnet.</div>
                    @else
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Gruppe</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rollen der Gruppe</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($user->gruppen as $gruppe)
                                <tr>
                                    <td class="px-4 py-2 font-medium text-gray-800">{{ $gruppe->name }}</td>
                                    <td class="px-4 py-2">
                                        @forelse($gruppe->roles as $role)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 mr-1">
                                                {{ $role->name }}
                                            </span>
                                        @empty
                                            <span class="text-gray-400 text-xs">—</span>
                                        @endforelse
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                {{-- Card 3: Meine Bestellungen --}}
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">Meine Bestellungen</h3>
                        @if($bestellungen->isNotEmpty())
                            <a href="{{ route('orders.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800">Alle ansehen →</a>
                        @endif
                    </div>
                    @if($bestellungen->isEmpty())
                        <div class="px-6 py-6 text-sm text-gray-400 text-center">Keine Bestellungen vorhanden.</div>
                    @else
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Artikel</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Preis</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($bestellungen as $bestellung)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-gray-500 whitespace-nowrap">{{ $bestellung->order_date->format('d.m.Y') }}</td>
                                    <td class="px-4 py-2 text-gray-800 max-w-xs truncate">{{ $bestellung->subject }}</td>
                                    <td class="px-4 py-2 text-right text-gray-700 whitespace-nowrap">{{ number_format($bestellung->price_gross, 2, ',', '.') }} €</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            {{ $bestellung->status == 6 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ \App\Models\Order::STATUS_LABELS[$bestellung->status] ?? $bestellung->status }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                {{-- Card 4: Aktive Ankündigungen --}}
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">Aktuelle Ankündigungen</h3>
                        <a href="{{ route('announcements.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800">Alle ansehen →</a>
                    </div>
                    @if($ankuendigungen->isEmpty())
                        <div class="px-6 py-6 text-sm text-gray-400 text-center">Keine aktiven Ankündigungen.</div>
                    @else
                        <ul class="divide-y divide-gray-100">
                            @foreach($ankuendigungen as $a)
                            <li class="px-6 py-3 flex items-start gap-3">
                                <span class="flex-shrink-0 mt-0.5 w-2 h-2 rounded-full
                                    {{ $a->type === 'critical' ? 'bg-red-500' : ($a->type === 'maintenance' ? 'bg-yellow-500' : 'bg-blue-400') }}">
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-700 line-clamp-2">{{ $a->message }}</p>
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

                {{-- Card 5: Meine Rollen & Aufgaben --}}
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-900">Meine Rollen & Aufgaben</h3>
                    </div>
                    @if($aufgabenZuweisungen->isEmpty())
                        <div class="px-6 py-6 text-sm text-gray-400 text-center">Keine Aufgaben zugeordnet.</div>
                    @else
                        <ul class="divide-y divide-gray-100">
                            @foreach($aufgabenZuweisungen as $az)
                            <li class="px-4 py-3">
                                <p class="text-sm font-medium text-gray-800">{{ $az->aufgabe?->name ?? '—' }}</p>
                                <div class="mt-1 flex items-center gap-2 flex-wrap">
                                    @if($az->gruppe)
                                        <span class="text-xs text-gray-500">{{ $az->gruppe->name }}</span>
                                    @endif
                                    @if($az->admin_user_id === Auth::id())
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">Admin</span>
                                    @endif
                                    @if($az->stellvertreter_user_id === Auth::id())
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Stellvertreter</span>
                                    @endif
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Card 6: Meine Stelle --}}
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-900">Meine Stelle</h3>
                    </div>
                    @if(!$stelle)
                        <div class="px-6 py-6 text-sm text-gray-400 text-center">Keine Stelle zugeordnet.</div>
                    @else
                        <div class="px-5 py-4 space-y-3">
                            <div>
                                <p class="text-base font-semibold text-gray-900">{{ $stelle->stellenbeschreibung?->bezeichnung ?? '—' }}</p>
                                @if($stelle->stellennummer)
                                    <p class="text-xs font-mono text-gray-500 mt-0.5">{{ $stelle->stellennummer }}</p>
                                @endif
                            </div>

                            <dl class="space-y-1.5 text-sm">
                                @if($stelle->gruppe)
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Gruppe</dt>
                                    <dd class="text-gray-700 font-medium text-right">{{ $stelle->gruppe->name }}</dd>
                                </div>
                                @endif
                                @if($stelle->haushalt_bewertung)
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">HH-Bewertung</dt>
                                    <dd><span class="px-1.5 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">{{ $stelle->haushalt_bewertung }}</span></dd>
                                </div>
                                @endif
                                @if($stelle->bes_gruppe)
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Bes.-Gruppe</dt>
                                    <dd><span class="px-1.5 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-700">{{ $stelle->bes_gruppe }}</span></dd>
                                </div>
                                @endif
                                @if($stelle->belegung !== null)
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Belegung</dt>
                                    <dd class="text-gray-700">{{ number_format($stelle->belegung, 0) }} %</dd>
                                </div>
                                @endif
                            </dl>

                            @if($stelle->stellenbeschreibung?->arbeitsvorgaenge->isNotEmpty())
                            <div class="pt-2 border-t border-gray-100">
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Arbeitsvorgänge</p>
                                <ul class="space-y-1">
                                    @foreach($stelle->stellenbeschreibung->arbeitsvorgaenge as $av)
                                    <li class="flex items-center justify-between text-xs">
                                        <span class="text-gray-700 truncate mr-2">{{ $av->betreff }}</span>
                                        <span class="flex-shrink-0 font-semibold text-indigo-600">{{ $av->anteil }}%</span>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            <a href="{{ route('stellen.show', $stelle) }}"
                               class="block text-center text-xs text-indigo-600 hover:text-indigo-800 pt-2 border-t border-gray-100">
                                Vollständige Beschreibung ansehen →
                            </a>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
