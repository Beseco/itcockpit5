<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <span class="text-gray-400 text-sm">Eingehender Anruf</span>
            <span class="text-gray-300">/</span>
            <h2 class="font-semibold text-xl text-gray-800 font-mono">{{ $result['e164'] ?? $result['input'] }}</h2>
        </div>
    </x-slot>

    @php
        $matches = $result['matches'];
        $e164    = $result['e164'];
    @endphp

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- ── Kein Treffer ─────────────────────────────────────────── --}}
            @if(empty($matches))
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center">
                    <p class="text-3xl font-mono text-gray-800">{{ $e164 ?? $result['input'] }}</p>
                    <p class="mt-2 text-sm text-gray-500">Unbekannte Nummer – kein Eintrag gefunden.</p>
                    @can('dienstleister.create')
                        <a href="{{ route('dienstleister.create', ['telefon' => $e164 ?? $result['input']]) }}"
                           class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            + Als Dienstleister anlegen
                        </a>
                    @endcan
                </div>

            {{-- ── Mehrere Treffer ──────────────────────────────────────── --}}
            @elseif(count($matches) > 1)
                <p class="text-sm text-gray-500">{{ count($matches) }} mögliche Treffer für <span class="font-mono">{{ $e164 }}</span>:</p>
                <div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
                    @foreach($matches as $m)
                        @if($m['kind'] === 'dienstleister')
                            @php $d = $m['dienstleister']; @endphp
                            <a href="{{ route('dienstleister.show', $d) }}" class="block px-5 py-3 hover:bg-gray-50">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <span class="font-medium text-gray-900">{{ $d->firmenname }}</span>
                                        <span class="ml-2 text-xs text-gray-400">{{ \App\Models\Dienstleister::TYPEN[$d->dienstleister_typ] ?? $d->dienstleister_typ }}</span>
                                        @if($m['contact'])
                                            <p class="text-xs text-gray-500 mt-0.5">→ {{ trim(($m['contact']->vorname ?? '') . ' ' . $m['contact']->nachname) }}{{ $m['contact']->funktion ? ' · ' . $m['contact']->funktion : '' }}</p>
                                        @endif
                                    </div>
                                    @if($m['match_type'] === 'range')
                                        <span class="shrink-0 text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Nummernbereich</span>
                                    @endif
                                </div>
                            </a>
                        @else
                            @php $u = $m['aduser']; @endphp
                            <div class="px-5 py-3 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <span class="font-medium text-gray-900">{{ $u->anzeigename_or_name }}</span>
                                    <span class="ml-2 text-xs text-gray-400">intern{{ $u->abteilung ? ' · ' . $u->abteilung : '' }}</span>
                                </div>
                                @if($m['match_type'] === 'range')
                                    <span class="shrink-0 text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Nummernbereich</span>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>

            {{-- ── Genau ein Treffer ────────────────────────────────────── --}}
            @else
                @php $m = $matches[0]; @endphp

                @if($m['kind'] === 'aduser')
                    @php $u = $m['aduser']; @endphp
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <span class="text-xs px-2 py-0.5 rounded-full bg-sky-100 text-sky-700">Interner Kontakt</span>
                            @if($m['match_type'] === 'range')
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">möglicher Treffer (Nummernbereich)</span>
                            @endif
                        </div>
                        <p class="mt-3 text-2xl font-semibold text-gray-900">{{ $u->anzeigename_or_name }}</p>
                        <dl class="mt-3 text-sm space-y-1.5">
                            @if($u->abteilung)<div class="flex gap-3"><dt class="w-28 text-gray-400">Abteilung</dt><dd class="text-gray-700">{{ $u->abteilung }}</dd></div>@endif
                            @if($u->telefon)<div class="flex gap-3"><dt class="w-28 text-gray-400">Telefon</dt><dd class="font-mono text-gray-700">{{ $u->telefon }}</dd></div>@endif
                            @if($u->email)<div class="flex gap-3"><dt class="w-28 text-gray-400">E-Mail</dt><dd class="text-gray-700">{{ $u->email }}</dd></div>@endif
                            <div class="flex gap-3"><dt class="w-28 text-gray-400">Benutzer</dt><dd class="font-mono text-gray-500">{{ $u->samaccountname }}</dd></div>
                        </dl>
                    </div>
                @else
                    @php
                        $d = $m['dienstleister'];
                        $orders = $d->orders()->latest('order_date')->limit(5)->get();
                    @endphp
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $d->status === 'gesperrt' ? 'bg-red-100 text-red-700' : 'bg-indigo-100 text-indigo-700' }}">
                                        {{ \App\Models\Dienstleister::STATUS[$d->status] ?? $d->status }}
                                    </span>
                                    @if($d->dienstleister_typ)
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ \App\Models\Dienstleister::TYPEN[$d->dienstleister_typ] ?? $d->dienstleister_typ }}</span>
                                    @endif
                                    @if($d->kritischer_dienstleister)
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700">kritisch</span>
                                    @endif
                                    @if($m['match_type'] === 'range')
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">möglicher Treffer (Nummernbereich)</span>
                                    @endif
                                </div>
                                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $d->firmenname }}</p>
                                @if($d->ort)<p class="text-sm text-gray-400">{{ $d->plz }} {{ $d->ort }}</p>@endif
                            </div>
                            <a href="{{ route('dienstleister.show', $d) }}"
                               class="shrink-0 inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                Detailseite
                            </a>
                        </div>

                        {{-- Matchender / erster Ansprechpartner hervorheben --}}
                        @php $hauptKontakt = $m['contact'] ?? $d->kontakte->first(); @endphp
                        @if($hauptKontakt)
                            <div class="mt-4 p-3 bg-gray-50 rounded-md">
                                <p class="text-xs text-gray-400 mb-0.5">Ansprechpartner{{ $m['via'] === 'ansprechpartner' ? ' (Anrufer)' : '' }}</p>
                                <p class="text-sm font-medium text-gray-800">
                                    {{ trim(($hauptKontakt->vorname ?? '') . ' ' . $hauptKontakt->nachname) }}
                                    @if($hauptKontakt->funktion)<span class="font-normal text-gray-500">· {{ $hauptKontakt->funktion }}</span>@endif
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5 space-x-3">
                                    @if($hauptKontakt->telefon)<span class="font-mono">☎ {{ $hauptKontakt->telefon }}</span>@endif
                                    @if($hauptKontakt->handy)<span class="font-mono">📱 {{ $hauptKontakt->handy }}</span>@endif
                                    @if($hauptKontakt->email)<span>{{ $hauptKontakt->email }}</span>@endif
                                </p>
                            </div>
                        @endif

                        <dl class="mt-4 text-sm space-y-2">
                            @if($d->telefon)
                                <div class="flex gap-3"><dt class="w-32 text-gray-400">Zentrale</dt><dd class="font-mono text-gray-700">{{ $d->telefon }}</dd></div>
                            @endif
                            @if($d->fachgebiet)
                                <div class="flex gap-3"><dt class="w-32 text-gray-400">Fachgebiet</dt><dd class="text-gray-700">{{ $d->fachgebiet }}</dd></div>
                            @endif
                            @if($d->leistungsbeschreibung)
                                <div class="flex gap-3"><dt class="w-32 text-gray-400">Leistung</dt><dd class="text-gray-700">{{ \Illuminate\Support\Str::limit($d->leistungsbeschreibung, 160) }}</dd></div>
                            @endif
                            <div class="flex gap-3"><dt class="w-32 text-gray-400">AV-Vertrag</dt>
                                <dd class="{{ $d->av_vertrag_vorhanden ? 'text-green-700' : 'text-gray-500' }}">
                                    {{ $d->av_vertrag_vorhanden ? '✓ vorhanden' . ($d->av_vertrag_datum ? ' (' . $d->av_vertrag_datum->format('d.m.Y') . ')' : '') : '– nicht hinterlegt' }}
                                </dd>
                            </div>
                            @if($d->bemerkungen)
                                <div class="flex gap-3"><dt class="w-32 text-gray-400">Notiz</dt><dd class="text-gray-600">{{ \Illuminate\Support\Str::limit($d->bemerkungen, 200) }}</dd></div>
                            @endif
                        </dl>

                        @if($orders->isNotEmpty())
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <p class="text-xs text-gray-400 mb-2">Letzte Vorgänge</p>
                                <ul class="space-y-1.5">
                                    @foreach($orders as $o)
                                        <li class="flex items-center justify-between text-sm gap-3">
                                            <span class="text-gray-700 truncate">{{ $o->subject }}</span>
                                            <span class="text-xs text-gray-400 shrink-0">{{ $o->order_date?->format('d.m.Y') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif
            @endif

        </div>
    </div>
</x-app-layout>
