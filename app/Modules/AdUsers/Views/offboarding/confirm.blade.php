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
                    <p class="text-sm text-gray-700 mb-3">
                        Hiermit bestätige ich, dass auf folgenden Geräten
                    </p>
                    <ul class="list-disc list-inside text-sm text-gray-700 space-y-1 mb-4">
                        <li>dem lokalen PC</li>
                        <li>den Serverlaufwerken</li>
                        <li>dem Terminalserver</li>
                        <li>Tablets und Smartphones</li>
                        <li>in Programmen (Outlook, Word, etc.)</li>
                        <li>Internet-Browsern</li>
                    </ul>
                    <p class="text-sm text-gray-700 mb-4">
                        keine privaten Daten gespeichert sind.
                    </p>
                    <p class="text-sm text-gray-700">
                        Die mir zur Verfügung gestellten Benutzerkonten, Geräte und Datenablagen können
                        bedenkenlos gelöscht werden.
                    </p>
                </div>

                {{-- Digitale Unterschrift --}}
                <form action="{{ route('offboarding.confirm.submit', $record->bestaetigungstoken) }}" method="POST">
                    @csrf

                    @if ($errors->any())
                        <div class="mb-4 bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded text-sm">
                            {{ $errors->first() }}
                        </div>
                    @endif

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

                    <div class="mt-6 flex items-center justify-between">
                        <p class="text-xs text-gray-400">
                            Ort: Freising, Datum: {{ now()->format('d.m.Y') }}
                        </p>
                        <button type="submit"
                                class="inline-flex items-center px-6 py-3 bg-red-700 text-white font-semibold rounded-md hover:bg-red-800">
                            Hiermit bestätige ich ✓
                        </button>
                    </div>
                </form>
            @endif

        </div>

        <p class="text-center text-xs text-gray-400 mt-4">Einstufung: Intern · IT Cockpit</p>
    </div>

</body>
</html>
