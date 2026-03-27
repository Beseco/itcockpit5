<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('adusers.offboarding.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Offboarding: {{ $record->voller_name }}
            </h2>
            <span class="px-2 py-0.5 text-xs font-semibold rounded-full
                {{ \App\Modules\AdUsers\Models\OffboardingRecord::STATUS_COLORS[$record->status] }}">
                {{ \App\Modules\AdUsers\Models\OffboardingRecord::STATUS_LABELS[$record->status] }}
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-5">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                     class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            {{-- E-Mail senden Modal nach Neuanlage --}}
            @if (session('ask_send_email') && $record->email_bestaetigung && $record->status === 'ausstehend')
                <div x-data="{ open: true }" x-show="open"
                     class="bg-blue-50 border border-blue-300 rounded-lg p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-semibold text-blue-800 mb-1">Bestätigungsmail senden?</h3>
                            <p class="text-sm text-blue-700">
                                Soll eine Bestätigungs-E-Mail an <strong>{{ $record->email_bestaetigung }}</strong> gesendet werden?
                                Der Mitarbeiter kann die Bestätigung dann digital unterzeichnen.
                            </p>
                        </div>
                        <button @click="open = false" class="text-blue-400 hover:text-blue-600 ml-4">✕</button>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <form action="{{ route('adusers.offboarding.send-email', $record) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700">
                                Ja, E-Mail senden
                            </button>
                        </form>
                        <button @click="open = false"
                                class="px-4 py-2 bg-white border border-gray-300 text-sm font-semibold rounded-md hover:bg-gray-50">
                            Nein, später
                        </button>
                    </div>
                </div>
            @endif

            {{-- Stammdaten --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Stammdaten</h3>
                </div>
                <div class="p-6 grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div><span class="text-gray-500">Vorname:</span> <span class="ml-1 font-medium">{{ $record->vorname }}</span></div>
                    <div><span class="text-gray-500">Nachname:</span> <span class="ml-1 font-medium">{{ $record->nachname }}</span></div>
                    <div><span class="text-gray-500">SAM-Account:</span> <span class="ml-1 font-mono text-xs">{{ $record->samaccountname }}</span></div>
                    @if ($record->personalnummer)
                        <div><span class="text-gray-500">Personalnummer:</span> <span class="ml-1">{{ $record->personalnummer }}</span></div>
                    @endif
                    <div><span class="text-gray-500">Abteilung:</span> <span class="ml-1">{{ $record->abteilung ?? '—' }}</span></div>
                    <div><span class="text-gray-500">Ausscheiden:</span> <span class="ml-1 font-semibold">{{ $record->datum_ausscheiden->format('d.m.Y') }}</span></div>
                    <div><span class="text-gray-500">Angelegt von:</span> <span class="ml-1">{{ $record->anleger_name }}</span></div>
                    <div><span class="text-gray-500">Angelegt am:</span> <span class="ml-1">{{ $record->created_at->format('d.m.Y') }}</span></div>
                    @if ($record->legacy_id)
                        <div><span class="text-gray-500">Legacy-ID:</span> <span class="ml-1 text-gray-400 text-xs">#{{ $record->legacy_id }}</span></div>
                    @endif
                    @if ($record->bemerkungen)
                        <div class="col-span-3">
                            <span class="text-gray-500">Bemerkungen:</span>
                            <p class="mt-1 text-gray-800 whitespace-pre-line">{{ $record->bemerkungen }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Digitale Bestätigung --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Digitale Bestätigung (Mitarbeiter)</h3>
                    @if ($record->email_bestaetigung && in_array($record->status, ['ausstehend', 'bestaetigung_angefragt']))
                        <form action="{{ route('adusers.offboarding.send-email', $record) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                                {{ $record->bestaetigung_angefragt_at ? 'E-Mail erneut senden' : 'Bestätigungsmail senden' }}
                            </button>
                        </form>
                    @endif
                </div>
                <div class="p-6 text-sm space-y-2">
                    @if ($record->email_bestaetigung)
                        <div><span class="text-gray-500">E-Mail:</span> <span class="ml-1">{{ $record->email_bestaetigung }}</span></div>
                    @else
                        <p class="text-gray-400 italic">Keine E-Mail-Adresse hinterlegt.</p>
                    @endif
                    @if ($record->bestaetigung_angefragt_at)
                        <div><span class="text-gray-500">Mail gesendet:</span> <span class="ml-1">{{ $record->bestaetigung_angefragt_at->format('d.m.Y H:i') }} Uhr</span></div>
                    @endif
                    @if ($record->bestaetigung_erhalten_at)
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 bg-green-500 rounded-full"></span>
                            <span class="font-semibold text-green-700">Bestätigt von: {{ $record->bestaetigung_name }}</span>
                        </div>
                        <div><span class="text-gray-500">Zeitpunkt:</span> <span class="ml-1">{{ $record->bestaetigung_erhalten_at->format('d.m.Y H:i') }} Uhr</span></div>
                        <div><span class="text-gray-500">IP:</span> <span class="ml-1 font-mono text-xs">{{ $record->bestaetigung_ip }}</span></div>
                    @elseif (!$record->bestaetigung_angefragt_at)
                        <p class="text-yellow-700 text-xs">Noch keine Bestätigungsmail versendet.</p>
                    @else
                        <p class="text-blue-600 text-xs">Bestätigung steht noch aus.</p>
                    @endif
                </div>
            </div>

            {{-- PDFs --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Dokumente</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach (['personalmeldung' => 'Personalmeldung', 'bestaetigung' => 'Bestätigung (gescannt)'] as $type => $label)
                        @php
                            $hasPdf  = $type === 'personalmeldung' ? $record->personalmeldung_pdf : $record->bestaetigung_pdf;
                            $pdfName = $type === 'personalmeldung' ? $record->personalmeldung_pdf_name : $record->bestaetigung_pdf_name;
                        @endphp
                        <div class="p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            {{-- Linke Seite: Label + Download --}}
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 flex-shrink-0 flex items-center justify-center rounded-lg {{ $hasPdf ? 'bg-indigo-100' : 'bg-gray-100' }}">
                                    <svg class="w-5 h-5 {{ $hasPdf ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $label }}</p>
                                    @if ($hasPdf)
                                        <a href="{{ route('adusers.offboarding.download', [$record, $type]) }}"
                                           target="_blank"
                                           class="text-sm text-indigo-600 hover:text-indigo-800 hover:underline truncate block">
                                            {{ $pdfName ?? $type . '.pdf' }}
                                        </a>
                                    @else
                                        <p class="text-sm text-gray-400 italic">Noch kein Dokument hochgeladen</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Rechte Seite: Upload --}}
                            <form action="{{ route('adusers.offboarding.upload', $record) }}" method="POST"
                                  enctype="multipart/form-data"
                                  class="flex items-center gap-2 flex-shrink-0">
                                @csrf
                                <input type="hidden" name="type" value="{{ $type }}">
                                <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-md border border-gray-300 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                    </svg>
                                    <span id="label-{{ $type }}">PDF wählen</span>
                                    <input type="file" name="pdf" accept="application/pdf" class="hidden"
                                           onchange="document.getElementById('label-{{ $type }}').textContent = this.files[0]?.name ?? 'PDF wählen'">
                                </label>
                                <button type="submit"
                                        class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700 transition">
                                    {{ $hasPdf ? 'Ersetzen' : 'Hochladen' }}
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Konten löschen / Abschließen --}}
            @if ($record->status !== 'abgeschlossen')
                <div class="bg-white shadow-sm sm:rounded-lg p-6" x-data="{ open: false }">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">Konten gelöscht markieren</h3>
                    <button @click="open = !open"
                            class="inline-flex items-center px-4 py-2 bg-orange-600 text-white text-xs font-semibold rounded-md hover:bg-orange-700">
                        Konten wurden gelöscht
                    </button>
                    <div x-show="open" class="mt-4">
                        <form action="{{ route('adusers.offboarding.mark-deleted', $record) }}" method="POST"
                              class="flex items-end gap-3">
                            @csrf
                            <div>
                                <x-input-label for="datum_geloescht" value="Löschdatum *" />
                                <x-text-input id="datum_geloescht" name="datum_geloescht" type="date"
                                              class="mt-1" value="{{ date('Y-m-d') }}" required />
                            </div>
                            <button type="submit"
                                    class="px-4 py-2 bg-red-700 text-white text-xs font-semibold rounded-md hover:bg-red-800">
                                Bestätigen &amp; Abschließen
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-800">
                    ✓ Abgeschlossen: Konten gelöscht am {{ $record->datum_geloescht?->format('d.m.Y') }}
                    von <strong>{{ $record->geloescht_von }}</strong>
                </div>
            @endif

            {{-- Löschen --}}
            <div x-data="{ open: false }" class="flex justify-end">
                <button @click="open = true"
                        class="text-xs text-red-500 hover:text-red-700 hover:underline">
                    Vorgang endgültig löschen
                </button>
                <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" x-transition style="display:none">
                    <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Vorgang löschen?</h3>
                        <p class="text-sm text-gray-600 mb-4">Dieser Offboarding-Vorgang wird unwiderruflich gelöscht.</p>
                        <div class="flex justify-end gap-3">
                            <button @click="open = false" class="px-4 py-2 text-sm bg-gray-100 rounded-md">Abbrechen</button>
                            <form action="{{ route('adusers.offboarding.destroy', $record) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-4 py-2 text-sm bg-red-600 text-white rounded-md">Löschen</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
