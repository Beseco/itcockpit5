<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bestätigung Ausscheiden – IT Cockpit</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center py-12 px-4">

    <div class="max-w-2xl w-full">

        {{-- Header --}}
        <div class="bg-red-700 rounded-t-xl px-8 py-6">
            <p class="text-red-200 text-xs uppercase tracking-widest font-semibold mb-1">IT Cockpit · Landratsamt Freising</p>
            <h1 class="text-white text-2xl font-bold">Checkliste Mitarbeiter Ausscheiden</h1>
            <p class="text-red-200 text-sm mt-1">Bestätigung-Löschung</p>
        </div>

        <div class="bg-white rounded-b-xl shadow-lg px-8 py-8">

            @if (isset($alreadyDone) && $alreadyDone)
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-2">Bereits bestätigt</h2>
                    <p class="text-gray-500">Diese Bestätigung wurde bereits am {{ $record->bestaetigung_erhalten_at?->format('d.m.Y') }} abgegeben.</p>
                </div>
            @elseif (isset($justDone) && $justDone)
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-2">Vielen Dank!</h2>
                    <p class="text-gray-600">Ihre Bestätigung wurde erfolgreich übermittelt.<br>
                    Die IT-Abteilung wurde benachrichtigt.</p>
                </div>
            @else
                {{-- Stammdaten --}}
                <div class="mb-6">
                    <h2 class="text-base font-semibold text-gray-700 uppercase tracking-wider mb-3 pb-1 border-b border-gray-200">
                        1. Stammdaten Mitarbeiter
                    </h2>
                    <div class="grid grid-cols-2 gap-y-3 text-sm">
                        <div class="text-gray-500">Vorname:</div>
                        <div class="font-medium">{{ $record->vorname }}</div>
                        <div class="text-gray-500">Nachname:</div>
                        <div class="font-medium">{{ $record->nachname }}</div>
                        @if ($record->personalnummer)
                            <div class="text-gray-500">Personalnummer:</div>
                            <div>{{ $record->personalnummer }}</div>
                        @endif
                        <div class="text-gray-500">Abteilung/Sachgebiet:</div>
                        <div>{{ $record->abteilung ?? '—' }}</div>
                        <div class="text-gray-500">Beendigung Dienstverhältnis zum:</div>
                        <div class="font-semibold text-red-700">{{ $record->datum_ausscheiden->format('d.m.Y') }}</div>
                    </div>
                </div>

                {{-- Bestätigungstext --}}
                <div class="mb-6">
                    <h2 class="text-base font-semibold text-gray-700 uppercase tracking-wider mb-3 pb-1 border-b border-gray-200">
                        2. Bestätigung
                    </h2>
                    {{-- Hinweis Deaktivierung + Löschung --}}
                    <div class="bg-amber-50 border border-amber-300 rounded-lg p-4 mb-5 text-sm">
                        <p class="font-semibold text-amber-800 mb-2">Wichtige Termine:</p>
                        <ul class="space-y-1 text-amber-700">
                            <li>🔒 <strong>Deaktivierung des Benutzerkontos:</strong> {{ $record->datum_ausscheiden->format('d.m.Y') }}</li>
                            <li>🗑 <strong>Löschung aller Daten:</strong> {{ $record->datum_ausscheiden->addDays(60)->format('d.m.Y') }} (60 Tage nach Ausscheiden)</li>
                        </ul>
                    </div>

                    <p class="text-sm text-gray-700 mb-4">
                        Ich bestätige hiermit, dass auf folgenden Geräten <strong>keine privaten Daten</strong> gespeichert sind
                        und diese bedenkenlos gelöscht werden können:
                    </p>

                    <div x-data="{ all: false }" class="space-y-2 mb-5">
                        @foreach([
                            'item_pc'        => 'dem lokalen PC',
                            'item_server'    => 'den Serverlaufwerken',
                            'item_terminal'  => 'dem Terminalserver',
                            'item_mobile'    => 'Tablets und Smartphones',
                            'item_programme' => 'in Programmen (Outlook, Word, etc.)',
                            'item_browser'   => 'Internet-Browsern',
                        ] as $name => $label)
                            <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer check-item">
                                <input type="checkbox" name="{{ $name }}" value="1"
                                       class="w-4 h-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                <span class="text-sm text-gray-700">Auf <strong>{{ $label }}</strong> sind keine privaten Daten gespeichert.</span>
                            </label>
                        @endforeach
                    </div>

                    <p class="text-sm text-gray-700 border-t border-gray-200 pt-4">
                        Die mir zur Verfügung gestellten Benutzerkonten, Geräte und Datenablagen können
                        bedenkenlos gelöscht werden.
                    </p>
                </div>

                {{-- Digitale Unterschrift --}}
                <form action="{{ route('offboarding.confirm.submit', $record->bestaetigungstoken) }}" method="POST">

                    <div id="checkbox-error" class="hidden mb-4 bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded text-sm">
                        <p class="font-semibold">Bitte alle Punkte abhaken und Namen eingeben.</p>
                    </div>

                    <div class="border-t border-gray-200 pt-6 mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Vor- und Nachname zur Bestätigung *
                        </label>
                        <input type="text" name="bestaetigung_name"
                               placeholder="Ihr vollständiger Name"
                               class="block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm"
                               required value="{{ old('bestaetigung_name') }}">
                        <p class="mt-1 text-xs text-gray-400">
                            Bitte geben Sie Ihren Namen ein. Dies gilt als digitale Unterschrift.
                        </p>
                    </div>

                    <input type="hidden" name="alle_bestaetigt" value="1">

                    <div class="mt-6 flex items-center justify-between">
                        <p class="text-xs text-gray-400">
                            Ort: Freising, Datum: {{ now()->format('d.m.Y') }}
                        </p>
                        <button type="button" id="submit-btn"
                                onclick="submitForm()"
                                class="inline-flex items-center px-6 py-3 bg-red-700 text-white font-semibold rounded-md hover:bg-red-800">
                            Hiermit bestätige ich ✓
                        </button>
                    </div>
                </form>

                <script>
                function submitForm() {
                    const boxes = document.querySelectorAll('input[type=checkbox]');
                    const name  = document.querySelector('input[name=bestaetigung_name]').value.trim();
                    const allChecked = Array.from(boxes).every(b => b.checked);
                    const err = document.getElementById('checkbox-error');

                    if (!allChecked || !name) {
                        err.classList.remove('hidden');
                        err.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return;
                    }
                    err.classList.add('hidden');
                    document.querySelector('form').submit();
                }
                </script>
            @endif

        </div>

        <p class="text-center text-xs text-gray-400 mt-4">Einstufung: Intern · IT Cockpit</p>
    </div>

</body>
</html>
