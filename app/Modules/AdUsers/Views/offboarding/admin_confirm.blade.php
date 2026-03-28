<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $type === 'deaktivierung' ? 'Deaktivierung' : 'Löschung' }} bestätigen – IT Cockpit</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center py-12 px-4">

    <div class="max-w-lg w-full">

        @php
            $isDeakt  = $type === 'deaktivierung';
            $color    = $isDeakt ? 'amber' : 'red';
            $bgHeader = $isDeakt ? 'bg-amber-600' : 'bg-red-700';
            $title    = $isDeakt ? 'Deaktivierung bestätigen' : 'Löschung bestätigen';
            $subtitle = $isDeakt
                ? 'Benutzerkonto wurde deaktiviert'
                : 'Alle Daten wurden gelöscht (60-Tage-Frist)';
            $alreadyDone = isset($done) && $done;
            $justDone    = isset($justDone) && $justDone;
            $token = $isDeakt ? $record->deaktivierung_token : $record->loeschung_token;
            $route = $isDeakt
                ? route('offboarding.admin.deaktivierung.submit', $token)
                : route('offboarding.admin.loeschung.submit', $token);
        @endphp

        <div class="{{ $bgHeader }} rounded-t-xl px-8 py-6">
            <p class="text-xs uppercase tracking-widest font-semibold mb-1"
               style="color: rgba(255,255,255,0.7)">IT Cockpit · Offboarding</p>
            <h1 class="text-white text-2xl font-bold">{{ $title }}</h1>
            <p class="text-sm mt-1" style="color: rgba(255,255,255,0.8)">{{ $subtitle }}</p>
        </div>

        <div class="bg-white rounded-b-xl shadow-lg px-8 py-8">

            @if ($alreadyDone)
                <div class="text-center py-6">
                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-800 mb-1">Bereits bestätigt</h2>
                    <p class="text-sm text-gray-500">
                        Diese Bestätigung wurde bereits abgegeben.
                    </p>
                </div>
            @elseif ($justDone)
                <div class="text-center py-6">
                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-800 mb-1">Bestätigung gespeichert</h2>
                    <p class="text-sm text-gray-500">
                        {{ $isDeakt ? 'Die Deaktivierung wurde bestätigt.' : 'Die Löschung wurde bestätigt. Der Vorgang ist abgeschlossen.' }}
                    </p>
                </div>
            @else
                {{-- Mitarbeiterdaten --}}
                <div class="mb-5 bg-gray-50 rounded-lg p-4 text-sm space-y-1.5">
                    <div><span class="text-gray-500 w-36 inline-block">Mitarbeiter:</span> <strong>{{ $record->voller_name }}</strong></div>
                    <div><span class="text-gray-500 w-36 inline-block">SAM-Account:</span> <code class="text-xs">{{ $record->samaccountname }}</code></div>
                    <div><span class="text-gray-500 w-36 inline-block">Ausgeschieden:</span> {{ $record->datum_ausscheiden->format('d.m.Y') }}</div>
                    @if (!$isDeakt)
                        <div><span class="text-gray-500 w-36 inline-block">Löschfrist:</span>
                            <strong class="text-red-700">{{ $record->datum_ausscheiden->addDays(60)->format('d.m.Y') }}</strong>
                        </div>
                    @endif
                </div>

                @if ($isDeakt)
                    <p class="text-sm text-gray-700 mb-5">
                        Bitte bestätigen Sie, dass das Benutzerkonto <strong>{{ $record->samaccountname }}</strong>
                        im Active Directory deaktiviert wurde.
                    </p>
                @else
                    <p class="text-sm text-gray-700 mb-2">
                        Bitte bestätigen Sie, dass folgende Daten gelöscht wurden:
                    </p>
                    <ul class="list-disc list-inside text-sm text-gray-700 mb-5 space-y-0.5">
                        <li>Benutzerkonto und Postfach im Active Directory</li>
                        <li>Serverlaufwerke und persönliche Daten</li>
                        <li>Terminalserver-Profil</li>
                    </ul>
                @endif

                <form action="{{ $route }}" method="POST">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Ihr Name zur Bestätigung *
                        </label>
                        <input type="text" name="bestaetigt_von" required
                               placeholder="Vor- und Nachname"
                               class="block w-full border-gray-300 rounded-md shadow-sm text-sm
                                      focus:border-{{ $color }}-500 focus:ring-{{ $color }}-500">
                    </div>
                    <button type="submit"
                            class="w-full py-3 {{ $bgHeader }} text-white font-semibold rounded-md
                                   hover:opacity-90 transition text-sm">
                        {{ $isDeakt ? 'Deaktivierung bestätigen' : 'Löschung bestätigen' }}
                    </button>
                </form>
            @endif
        </div>

        <p class="text-center text-xs text-gray-400 mt-4">IT Cockpit · Landratsamt Freising</p>
    </div>

</body>
</html>
