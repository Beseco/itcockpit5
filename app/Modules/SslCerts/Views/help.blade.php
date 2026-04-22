<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('sslcerts.index') }}" title="Zurück"
               class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800">SSL-Zertifikate – Hilfe & Anleitung</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Was ist dieses Modul?</h3>
                <p class="text-sm text-gray-600">
                    Das Modul <strong>SSL-Zertifikate</strong> verwaltet alle TLS-/SSL-Zertifikate zentral.
                    Ablaufdaten werden überwacht und per E-Mail-Erinnerung rechtzeitig angezeigt,
                    damit kein Zertifikat unbemerkt abläuft.
                </p>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Hauptfunktionen</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Zertifikat importieren (Datei):</strong> PEM- oder P12-Datei hochladen – Metadaten werden automatisch ausgelesen.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Zertifikat importieren (URL):</strong> URL eingeben – das Zertifikat wird direkt vom Server abgerufen.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Ablauf-Übersicht:</strong> Farbliche Ampel-Anzeige je nach Restlaufzeit (grün / gelb / rot).</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>URLs zuordnen:</strong> Mehrere Domains/URLs, die ein Zertifikat nutzen, hinterlegen.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>E-Mail-Erinnerungen:</strong> Automatische Benachrichtigung vor Ablauf (konfigurierbar in Einstellungen).</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Download:</strong> Zertifikatsdatei jederzeit herunterladen.</span></li>
                </ul>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Typischer Ablauf</h3>
                <ol class="space-y-2 text-sm text-gray-600 list-none">
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">1</span><span><strong>Neues Zertifikat</strong> anlegen: Datei hochladen oder URL eingeben.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">2</span><span>Zugehörige <strong>URLs/Domains</strong> ergänzen.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">3</span><span>Ablaufstatus in der Übersicht regelmäßig prüfen.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">4</span><span>Bei Ablauf: Erneuertes Zertifikat importieren und alten Eintrag aktualisieren.</span></li>
                </ol>
            </div>

        </div>
    </div>
</x-app-layout>
