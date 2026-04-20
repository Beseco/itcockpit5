<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Änderungen übernommen</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">
<div class="max-w-lg w-full">
    <div class="bg-indigo-700 rounded-t-xl px-8 py-6">
        <p class="text-xs font-semibold uppercase tracking-widest text-indigo-300 mb-1">IT Cockpit · Abteilungsrevision</p>
        <h1 class="text-xl font-bold text-white">{{ $proposal->applikation->name }}</h1>
    </div>
    <div class="bg-white rounded-b-xl shadow border border-t-0 border-gray-200 px-8 py-8">
        @if($alreadyApproved)
            <div class="text-center py-4">
                <div class="text-4xl mb-4">ℹ️</div>
                <h2 class="text-lg font-bold text-gray-800 mb-2">Bereits übernommen</h2>
                <p class="text-gray-500 text-sm">Diese Änderungen wurden bereits am {{ $proposal->approved_at->format('d.m.Y H:i') }} Uhr übernommen.</p>
            </div>
        @else
            <div class="text-center mb-6">
                <div class="text-4xl mb-4">✅</div>
                <h2 class="text-lg font-bold text-gray-800 mb-1">Änderungen übernommen</h2>
                <p class="text-gray-500 text-sm">Die vorgeschlagenen Änderungen wurden in die Applikation eingetragen.</p>
            </div>

            @if($proposal->proposed_data)
            <div class="border border-gray-200 rounded-lg overflow-hidden text-sm">
                <div class="bg-gray-50 px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide border-b">Übernommene Änderungen</div>
                @php
                    $schutzbedarf = ['A' => 'Normal', 'B' => 'Hoch', 'C' => 'Sehr hoch'];
                    $labels = [
                        'einsatzzweck'        => 'Beschreibung',
                        'ansprechpartner'     => 'Ansprechpartner',
                        'verantwortlich_name' => 'Verfahrensverantwortlicher',
                        'confidentiality'     => 'Vertraulichkeit',
                        'integrity'           => 'Integrität',
                        'availability'        => 'Verfügbarkeit',
                    ];
                    $orig = $proposal->original_data;
                    $prop = $proposal->proposed_data;
                @endphp
                @foreach($labels as $key => $label)
                    @php
                        $o = $orig[$key] ?? null;
                        $p = $prop[$key] ?? null;
                        if (in_array($key, ['confidentiality','integrity','availability'])) {
                            $o = $o ? ($schutzbedarf[$o] ?? $o) . ' (' . $o . ')' : null;
                            $p = $p ? ($schutzbedarf[$p] ?? $p) . ' (' . $p . ')' : null;
                        }
                        if ($o === $p) continue;
                    @endphp
                    <div class="px-4 py-2.5 border-b border-gray-100 last:border-0">
                        <div class="text-xs text-gray-400 mb-1">{{ $label }}</div>
                        <div class="flex gap-2 items-start">
                            <span class="text-red-600 line-through text-xs flex-1">{{ $o ?: '—' }}</span>
                            <span class="text-gray-400">→</span>
                            <span class="text-green-700 font-medium text-xs flex-1">{{ $p ?: '—' }}</span>
                        </div>
                    </div>
                @endforeach
                @if($proposal->reason)
                <div class="px-4 py-2.5 bg-yellow-50 border-b border-gray-100 last:border-0">
                    <div class="text-xs text-gray-400 mb-1">Begründung Schutzbedarf</div>
                    <div class="text-xs text-gray-700">{{ $proposal->reason }}</div>
                </div>
                @endif
                @if($proposal->kommentar)
                <div class="px-4 py-2.5 bg-blue-50 border-b border-gray-100 last:border-0">
                    <div class="text-xs text-gray-400 mb-1">Allgemeiner Kommentar</div>
                    <div class="text-xs text-gray-700">{{ $proposal->kommentar }}</div>
                </div>
                @endif
                @if($proposal->nicht_vorhanden)
                <div class="px-4 py-2.5 bg-red-50">
                    <div class="text-xs font-semibold text-red-600">⚠ App als nicht mehr vorhanden / benötigt gemeldet</div>
                </div>
                @endif
            </div>
            @endif
        @endif
    </div>
    <p class="text-center text-xs text-gray-400 mt-5">IT Cockpit &middot; automatisch generiert</p>
</div>
</body>
</html>
