<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 flex-wrap">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dienstleistungsmatrix</h2>
            <div class="flex gap-2 ml-auto flex-wrap">
                <a href="{{ route('schulen.index') }}"
                   class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                    Schulen verwalten
                </a>
                <a href="{{ route('schulen.dienste.index') }}"
                   class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                    Dienstleistungen
                </a>
                <a href="{{ route('schulen.vze') }}"
                   class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                    VZE-Rechner
                </a>
                <a href="{{ route('schulen.protokoll') }}"
                   class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                    Protokoll
                </a>
                <a href="{{ route('schulen.export', ['matrix', 'pdf']) }}"
                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    PDF
                </a>
                <a href="{{ route('schulen.export', ['matrix', 'xlsx']) }}"
                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Excel
                </a>
                @can('schulen.config')
                <a href="{{ route('schulen.einstellungen') }}"
                   class="inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded-md text-xs font-medium text-white hover:bg-indigo-700">
                    Einstellungen
                </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                     class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filter --}}
            <div class="bg-white shadow-sm sm:rounded-lg mb-4 p-4">
                <form action="{{ route('schulen.matrix') }}" method="GET" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Schultyp</label>
                        <select name="filter_typ" onchange="this.form.submit()"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle Schultypen</option>
                            @foreach ($schulTypen as $typ)
                                <option value="{{ $typ->id }}" @selected($filterTyp == $typ->id)>{{ $typ->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Kategorie</label>
                        <select name="filter_kategorie" onchange="this.form.submit()"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle Kategorien</option>
                            @foreach ($kategorien as $kat)
                                <option value="{{ $kat->id }}" @selected($filterKategorie == $kat->id)>{{ $kat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Typ</label>
                        <select name="filter_eintrag_typ" onchange="this.form.submit()"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle</option>
                            <option value="dienstleistung" @selected(($filterEintragTyp ?? '') === 'dienstleistung')>Nur Dienstleistungen</option>
                            <option value="voraussetzung"  @selected(($filterEintragTyp ?? '') === 'voraussetzung')>Nur Betriebsvoraussetzungen</option>
                        </select>
                    </div>
                    @if ($filterTyp || $filterKategorie || $filterEintragTyp)
                        <a href="{{ route('schulen.matrix', ['filter_typ' => '', 'filter_kategorie' => '', 'filter_eintrag_typ' => '']) }}"
                           class="text-xs text-indigo-600 hover:underline self-end pb-1">Filter zurücksetzen</a>
                    @endif
                </form>
            </div>

            {{-- Legende --}}
            <div class="flex flex-wrap gap-3 mb-4 text-xs">
                @foreach (\App\Modules\Schulen\Models\SchuleDienstleistung::STATUS_LABELS as $key => $label)
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded {{ \App\Modules\Schulen\Models\SchuleDienstleistung::STATUS_COLORS[$key] }}">
                        {{ \App\Modules\Schulen\Models\SchuleDienstleistung::STATUS_ICONS[$key] }} {{ $label }}
                    </span>
                @endforeach
            </div>

            @if ($schulen->isEmpty() || $dienstleistungen->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                    @if ($schulen->isEmpty())
                        <p class="mb-2">Noch keine Schulen angelegt.</p>
                        @can('schulen.edit')
                            <a href="{{ route('schulen.create') }}" class="text-indigo-600 hover:underline">Erste Schule anlegen →</a>
                        @endcan
                    @else
                        <p class="mb-2">Noch keine Dienstleistungen angelegt.</p>
                        @can('schulen.edit')
                            <a href="{{ route('schulen.dienste.create') }}" class="text-indigo-600 hover:underline">Erste Dienstleistung anlegen →</a>
                        @endcan
                    @endif
                </div>
            @else
                {{-- Alpine-Wrapper: Matrix + Modal --}}
                <div x-data="{
                    modalOpen: false,
                    formAction: '',
                    modalSchule: '',
                    modalDienst: '',
                    selectedStatus: 'nicht_vorhanden',
                    stundenOverride: '',
                    notizen: '',
                    openModal(schuleId, dienstId, status, schuleName, dienstName, stunden, notizen) {
                        this.formAction = '/schulen/' + schuleId + '/dienste/' + dienstId;
                        this.modalSchule = schuleName;
                        this.modalDienst = dienstName;
                        this.selectedStatus = status;
                        this.stundenOverride = stunden || '';
                        this.notizen = notizen || '';
                        this.modalOpen = true;
                    }
                }">

                    {{-- Matrix-Tabelle --}}
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="overflow-auto" style="max-height: 75vh;">
                            <table class="border-collapse text-xs" style="min-width: max-content;">
                                <thead>
                                    {{-- Schultyp-Gruppenköpfe --}}
                                    <tr class="bg-gray-50 sticky top-0 z-20">
                                        <th class="sticky left-0 z-30 bg-gray-50 border border-gray-200 px-3 py-2 text-left text-gray-500 min-w-[220px]"></th>
                                        @foreach ($schulTypen as $typ)
                                            @php $gruppe = $schulenGruppen->get($typ->id, collect()) @endphp
                                            @if ($gruppe->isNotEmpty())
                                                <th colspan="{{ $gruppe->count() }}"
                                                    class="border border-gray-200 px-3 py-2 text-center font-semibold text-gray-700 {{ $typ->farbe_klassen }}">
                                                    {{ $typ->name }} ({{ $gruppe->count() }})
                                                </th>
                                            @endif
                                        @endforeach
                                        <th colspan="3" class="border border-gray-200 px-3 py-2 text-center font-semibold text-gray-600 bg-violet-50">
                                            VZE
                                        </th>
                                    </tr>
                                    {{-- Schul-Namen --}}
                                    <tr class="bg-white sticky z-20" style="top: 37px;">
                                        <th class="sticky left-0 z-30 bg-white border border-gray-200 px-3 py-2 text-left text-gray-600 font-semibold min-w-[220px]">
                                            Dienstleistung
                                        </th>
                                        @foreach ($schulen as $schule)
                                            <th class="border border-gray-200 px-2 py-1 text-center font-medium text-gray-700 w-[90px]">
                                                <a href="{{ route('schulen.show', $schule) }}"
                                                   title="{{ $schule->name }}"
                                                   class="hover:text-indigo-600 block"
                                                   style="writing-mode: vertical-lr; transform: rotate(180deg); height: 90px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    {{ $schule->kurzname ?: $schule->name }}
                                                </a>
                                            </th>
                                        @endforeach
                                        <th class="border border-gray-200 px-2 py-2 text-center text-xs font-medium text-gray-500 bg-violet-50 w-[70px]">1 Schule</th>
                                        <th class="border border-gray-200 px-2 py-2 text-center text-xs font-medium text-gray-500 bg-violet-50 w-[70px]">Aktiv</th>
                                        <th class="border border-gray-200 px-2 py-2 text-center text-xs font-medium text-gray-500 bg-violet-50 w-[70px]">Alle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $sortedKats = $kategorien->filter(fn($k) => $diensteGruppen->has($k->id));
                                        $ohneKat    = $diensteGruppen->get('', collect())->merge($diensteGruppen->get(null, collect()));
                                    @endphp

                                    @foreach ($sortedKats as $kat)
                                        <tr class="bg-indigo-50">
                                            <td colspan="{{ $schulen->count() + 4 }}"
                                                class="sticky left-0 border border-gray-200 px-3 py-1.5 font-semibold text-indigo-700 text-xs uppercase tracking-wide">
                                                {{ $kat->name }}
                                            </td>
                                        </tr>
                                        @foreach ($diensteGruppen->get($kat->id, collect()) as $dienst)
                                            @php
                                                $h = $dienst->jahresstunden();
                                                $vzeBase = \App\Modules\Schulen\Models\Dienstleistung::VZE_JAHRESSTUNDEN;
                                                $istStunden = 0;
                                                foreach ($schulen as $s) {
                                                    $p = $pivots->get($s->id)?->firstWhere('dienstleistung_id', $dienst->id);
                                                    if ($p && $p->status === 'aktiv') {
                                                        $istStunden += $p->stunden_override ?? $h ?? 0;
                                                    }
                                                }
                                            @endphp
                                            <tr class="hover:bg-gray-50">
                                                <td class="sticky left-0 z-10 bg-white border border-gray-200 px-3 py-1.5 text-gray-800 font-medium min-w-[220px]">
                                                    @include('schulen::dienstleistungen._voraussetzung_icon', ['dienst' => $dienst])
                                                    <a href="{{ route('schulen.dienste.show', $dienst) }}"
                                                       @if($dienst->beschreibung) title="{{ $dienst->beschreibung }}" @endif
                                                       class="hover:text-indigo-600 {{ $dienst->beschreibung ? 'cursor-help decoration-dotted underline-offset-2 hover:underline' : '' }}">{{ $dienst->name }}</a>
                                                    @if ($dienst->dokumentation_url)
                                                        <a href="{{ $dienst->dokumentation_url }}" target="_blank" rel="noopener"
                                                           title="Dokumentation öffnen"
                                                           class="ml-1 text-gray-400 hover:text-indigo-600">📖</a>
                                                    @endif
                                                </td>
                                                @foreach ($schulen as $schule)
                                                    @php
                                                        $pivot  = $pivots->get($schule->id)?->firstWhere('dienstleistung_id', $dienst->id);
                                                        $status = $pivot?->status ?? 'nicht_vorhanden';
                                                        $colors = \App\Modules\Schulen\Models\SchuleDienstleistung::STATUS_COLORS[$status];
                                                        $icon   = \App\Modules\Schulen\Models\SchuleDienstleistung::STATUS_ICONS[$status];
                                                    @endphp
                                                    <td class="border border-gray-200 px-1 py-1 text-center">
                                                        @can('schulen.edit')
                                                            <button type="button"
                                                                @click="openModal({{ $schule->id }}, {{ $dienst->id }}, '{{ $status }}', @js($schule->name), @js($dienst->name), {{ $pivot && $pivot->stunden_override !== null ? $pivot->stunden_override : 'null' }}, @js($pivot?->notizen ?? ''))"
                                                                @if($pivot?->notizen) title="{{ $pivot->notizen }}" @endif
                                                                class="inline-flex items-center justify-center w-8 h-8 rounded hover:ring-2 hover:ring-indigo-400 transition {{ $colors }} {{ $pivot?->notizen ? 'ring-1 ring-offset-1 ring-gray-400' : '' }}">
                                                                {{ $icon }}
                                                            </button>
                                                        @else
                                                            <span @if($pivot?->notizen) title="{{ $pivot->notizen }}" @endif
                                                                  class="inline-flex items-center justify-center w-8 h-8 rounded {{ $colors }} {{ $pivot?->notizen ? 'ring-1 ring-offset-1 ring-gray-400' : '' }}">
                                                                {{ $icon }}
                                                            </span>
                                                        @endcan
                                                    </td>
                                                @endforeach
                                                {{-- VZE-Spalten --}}
                                                <td class="border border-gray-200 px-2 py-1 text-center bg-violet-50 text-xs text-violet-700 font-medium whitespace-nowrap">
                                                    {{ $h !== null ? number_format($h / $vzeBase, 3, ',', '.') : '—' }}
                                                </td>
                                                <td class="border border-gray-200 px-2 py-1 text-center bg-violet-50 text-xs font-semibold text-green-700 whitespace-nowrap">
                                                    {{ $istStunden > 0 ? number_format($istStunden / $vzeBase, 3, ',', '.') : '—' }}
                                                </td>
                                                <td class="border border-gray-200 px-2 py-1 text-center bg-violet-50 text-xs text-violet-600 whitespace-nowrap">
                                                    {{ $h !== null ? number_format(($schulen->count() * $h) / $vzeBase, 3, ',', '.') : '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach

                                    @if ($ohneKat->isNotEmpty())
                                        <tr class="bg-gray-50">
                                            <td colspan="{{ $schulen->count() + 4 }}"
                                                class="sticky left-0 border border-gray-200 px-3 py-1.5 font-semibold text-gray-500 text-xs uppercase tracking-wide">
                                                Ohne Kategorie
                                            </td>
                                        </tr>
                                        @foreach ($ohneKat as $dienst)
                                            @php
                                                $h = $dienst->jahresstunden();
                                                $vzeBase = \App\Modules\Schulen\Models\Dienstleistung::VZE_JAHRESSTUNDEN;
                                                $istStunden = 0;
                                                foreach ($schulen as $s) {
                                                    $p = $pivots->get($s->id)?->firstWhere('dienstleistung_id', $dienst->id);
                                                    if ($p && $p->status === 'aktiv') {
                                                        $istStunden += $p->stunden_override ?? $h ?? 0;
                                                    }
                                                }
                                            @endphp
                                            <tr class="hover:bg-gray-50">
                                                <td class="sticky left-0 z-10 bg-white border border-gray-200 px-3 py-1.5 text-gray-800 font-medium">
                                                    @include('schulen::dienstleistungen._voraussetzung_icon', ['dienst' => $dienst])
                                                    <a href="{{ route('schulen.dienste.show', $dienst) }}"
                                                       @if($dienst->beschreibung) title="{{ $dienst->beschreibung }}" @endif
                                                       class="hover:text-indigo-600 {{ $dienst->beschreibung ? 'cursor-help decoration-dotted underline-offset-2 hover:underline' : '' }}">{{ $dienst->name }}</a>
                                                    @if ($dienst->dokumentation_url)
                                                        <a href="{{ $dienst->dokumentation_url }}" target="_blank" rel="noopener"
                                                           title="Dokumentation öffnen"
                                                           class="ml-1 text-gray-400 hover:text-indigo-600">📖</a>
                                                    @endif
                                                </td>
                                                @foreach ($schulen as $schule)
                                                    @php
                                                        $pivot  = $pivots->get($schule->id)?->firstWhere('dienstleistung_id', $dienst->id);
                                                        $status = $pivot?->status ?? 'nicht_vorhanden';
                                                        $colors = \App\Modules\Schulen\Models\SchuleDienstleistung::STATUS_COLORS[$status];
                                                        $icon   = \App\Modules\Schulen\Models\SchuleDienstleistung::STATUS_ICONS[$status];
                                                    @endphp
                                                    <td class="border border-gray-200 px-1 py-1 text-center">
                                                        @can('schulen.edit')
                                                            <button type="button"
                                                                @click="openModal({{ $schule->id }}, {{ $dienst->id }}, '{{ $status }}', @js($schule->name), @js($dienst->name), {{ $pivot && $pivot->stunden_override !== null ? $pivot->stunden_override : 'null' }}, @js($pivot?->notizen ?? ''))"
                                                                @if($pivot?->notizen) title="{{ $pivot->notizen }}" @endif
                                                                class="inline-flex items-center justify-center w-8 h-8 rounded hover:ring-2 hover:ring-indigo-400 transition {{ $colors }} {{ $pivot?->notizen ? 'ring-1 ring-offset-1 ring-gray-400' : '' }}">
                                                                {{ $icon }}
                                                            </button>
                                                        @else
                                                            <span @if($pivot?->notizen) title="{{ $pivot->notizen }}" @endif
                                                                  class="inline-flex items-center justify-center w-8 h-8 rounded {{ $colors }} {{ $pivot?->notizen ? 'ring-1 ring-offset-1 ring-gray-400' : '' }}">
                                                                {{ $icon }}
                                                            </span>
                                                        @endcan
                                                    </td>
                                                @endforeach
                                                {{-- VZE-Spalten --}}
                                                <td class="border border-gray-200 px-2 py-1 text-center bg-violet-50 text-xs text-violet-700 font-medium whitespace-nowrap">
                                                    {{ $h !== null ? number_format($h / $vzeBase, 3, ',', '.') : '—' }}
                                                </td>
                                                <td class="border border-gray-200 px-2 py-1 text-center bg-violet-50 text-xs font-semibold text-green-700 whitespace-nowrap">
                                                    {{ $istStunden > 0 ? number_format($istStunden / $vzeBase, 3, ',', '.') : '—' }}
                                                </td>
                                                <td class="border border-gray-200 px-2 py-1 text-center bg-violet-50 text-xs text-violet-600 whitespace-nowrap">
                                                    {{ $h !== null ? number_format(($schulen->count() * $h) / $vzeBase, 3, ',', '.') : '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                                @php
                                    $vzeBase       = \App\Modules\Schulen\Models\Dienstleistung::VZE_JAHRESSTUNDEN;
                                    $sumEin        = 0;
                                    $sumIst        = 0;
                                    $sumAlle       = 0;
                                    foreach ($dienstleistungen as $d) {
                                        $dh = $d->jahresstunden();
                                        if ($dh === null) continue;
                                        $sumEin  += $dh;
                                        $sumAlle += $schulen->count() * $dh;
                                        foreach ($schulen as $s) {
                                            $p = $pivots->get($s->id)?->firstWhere('dienstleistung_id', $d->id);
                                            if ($p && $p->status === 'aktiv') {
                                                $sumIst += $p->stunden_override ?? $dh;
                                            }
                                        }
                                    }
                                @endphp
                                <tfoot>
                                    <tr class="bg-gray-100 font-semibold text-xs border-t-2 border-gray-300">
                                        <td class="sticky left-0 z-10 bg-gray-100 border border-gray-300 px-3 py-2 text-gray-700 uppercase tracking-wide">
                                            Gesamt
                                        </td>
                                        <td colspan="{{ $schulen->count() }}" class="border border-gray-200 px-2 py-2 text-center text-gray-400 text-xs italic">
                                            {{ $dienstleistungen->count() }} Dienstleistungen
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-center bg-violet-100 text-violet-800 whitespace-nowrap">
                                            {{ number_format($sumEin / $vzeBase, 3, ',', '.') }} VZE
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-center bg-violet-100 text-green-800 whitespace-nowrap">
                                            {{ number_format($sumIst / $vzeBase, 3, ',', '.') }} VZE
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-center bg-violet-100 text-violet-800 whitespace-nowrap">
                                            {{ number_format($sumAlle / $vzeBase, 3, ',', '.') }} VZE
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- Modal: Zell-Status bearbeiten (nur für Benutzer mit Edit-Recht) --}}
                    @can('schulen.edit')
                    <div x-cloak x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                         @click.self="modalOpen = false">
                        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6" @click.stop>
                            <h3 class="text-lg font-semibold text-gray-800 mb-1" x-text="modalDienst"></h3>
                            <p class="text-sm text-gray-500 mb-4" x-text="modalSchule"></p>

                            <form :action="formAction" method="POST" @submit="modalOpen = false">
                                @csrf
                                @method('PUT')

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <div class="space-y-1.5">
                                        @foreach (\App\Modules\Schulen\Models\SchuleDienstleistung::STATUS_LABELS as $sKey => $sLabel)
                                            <label class="flex items-center gap-3 p-2 rounded border cursor-pointer hover:opacity-90
                                                          {{ \App\Modules\Schulen\Models\SchuleDienstleistung::STATUS_COLORS[$sKey] }}">
                                                <input type="radio" name="status" value="{{ $sKey }}"
                                                       x-model="selectedStatus"
                                                       class="text-indigo-600">
                                                <span class="font-medium text-sm">
                                                    {{ \App\Modules\Schulen\Models\SchuleDienstleistung::STATUS_ICONS[$sKey] }}
                                                    {{ $sLabel }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Stunden/Jahr (Override)</label>
                                    <input type="number" name="stunden_override" x-model="stundenOverride"
                                           step="0.5" min="0" placeholder="Standard aus Dienstleistung"
                                           class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                    <p class="text-xs text-gray-400 mt-1">Leer lassen = Standardwert der Dienstleistung</p>
                                </div>

                                <div class="mb-5">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Notiz</label>
                                    <input type="text" name="notizen" x-model="notizen" maxlength="500"
                                           class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                </div>

                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="modalOpen = false"
                                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        Abbrechen
                                    </button>
                                    <button type="submit"
                                            class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-indigo-700">
                                        Speichern
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endcan

                </div>{{-- Ende Alpine-Wrapper --}}
            @endif
        </div>
    </div>
</x-app-layout>
