<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('baramundi.index') }}" title="Zurück" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800">Baramundi – Hilfe &amp; Anleitung</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Was ist das Modul? --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Was ist dieses Modul?</h3>
                <p class="text-sm text-gray-600">
                    Das <strong>Baramundi-Modul</strong> überwacht automatisch, ob für verwaltete Softwarepakete
                    neue Versionen auf dem Baramundi-Fileserver (UNC-Pfad) bereitliegen. Sobald Baramundi einen
                    neuen Versionsordner anlegt, wird das Modul benachrichtigt. Sobald ein Admin die
                    Installationsdatei manuell herunterlädt und in den Ordner kopiert, erkennt das Modul dies
                    beim nächsten Scan und erstellt eine Follow-up-Benachrichtigung im Ticketsystem.
                </p>
            </div>

            {{-- Ablauf --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Typischer Ablauf</h3>
                <ol class="space-y-3 text-sm text-gray-600 list-decimal list-inside">
                    <li>Baramundi legt automatisch einen neuen Versionsordner an (z.&nbsp;B. <code class="bg-gray-100 px-1 rounded">15.78.4-x64</code>) mit einer leeren Platzhalterdatei (0&nbsp;KB).</li>
                    <li>Der nächste Scan erkennt die neue Version → <strong>Zammad-Ticket wird erstellt</strong>, Status wechselt auf <em>Neue Version erkannt</em>.</li>
                    <li>Ein Admin lädt die Installationsdatei (z.&nbsp;B. <code class="bg-gray-100 px-1 rounded">.msi</code>) herunter und kopiert sie in den Versionsordner.</li>
                    <li>Der nächste Scan erkennt die Datei (&gt;0&nbsp;KB) → <strong>Notiz im bestehenden Ticket</strong>, Status wechselt auf <em>OK</em>.</li>
                    <li>Baramundi kann die Software jetzt automatisch verteilen.</li>
                </ol>
            </div>

            {{-- Hauptfunktionen --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Hauptfunktionen</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span>
                        <span><strong>Pakete überwachen:</strong> UNC-Pfad zum Baramundi-Share hinterlegen; das System prüft automatisch auf neue Versionsordner.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span>
                        <span><strong>Versionserkennung:</strong> Ordnernamen werden per Regex als Versionsnummern erkannt und per <code class="bg-gray-100 px-1 rounded">version_compare()</code> verglichen.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span>
                        <span><strong>Datei-Größenprüfung:</strong> Jedes Scan prüft, ob im Versionsordner eine Nicht-README-Datei &gt;0&nbsp;Byte vorhanden ist.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span>
                        <span><strong>Zammad-Integration:</strong> Erstellt ein Ticket bei neuer Version und fügt eine Notiz hinzu, sobald die Datei bereitgestellt wird. Alle Meldungen landen im selben Ticket.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span>
                        <span><strong>E-Mail-Fallback:</strong> Wenn Zammad nicht konfiguriert ist, werden E-Mails an die hinterlegte Benachrichtigungsadresse versendet.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span>
                        <span><strong>Manueller Scan:</strong> Jedes Paket kann über „Jetzt scannen" sofort geprüft werden, ohne auf das automatische Intervall zu warten.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span>
                        <span><strong>Ereignislog:</strong> Alle Ereignisse (neue Version, Datei bereitgestellt, Download-Fehler, SMB-Fehler) werden protokolliert.</span></li>
                </ul>
            </div>

            {{-- Diagnose-Commands --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Hilfreiche Konsolenbefehle</h3>
                <div class="space-y-4 text-sm">
                    <div>
                        <code class="block bg-gray-100 px-3 py-2 rounded font-mono text-xs">php artisan bara:scan --force</code>
                        <p class="text-gray-500 mt-1">Scan sofort ausführen (alle Pakete).</p>
                    </div>
                    <div>
                        <code class="block bg-gray-100 px-3 py-2 rounded font-mono text-xs">php artisan bara:diagnose --package=1 --raw</code>
                        <p class="text-gray-500 mt-1">Detaildiagnose: zeigt SMB-Ordnerinhalte, Dateigrößen und was der nächste Scan tun würde.</p>
                    </div>
                    <div>
                        <code class="block bg-gray-100 px-3 py-2 rounded font-mono text-xs">php artisan bara:zammad-test --package=1</code>
                        <p class="text-gray-500 mt-1">Testet die Zammad-Integration: erstellt ein Testticket und fügt eine Notiz hinzu.</p>
                    </div>
                    <div>
                        <code class="block bg-gray-100 px-3 py-2 rounded font-mono text-xs">php artisan bara:mail-test --to=admin@example.com</code>
                        <p class="text-gray-500 mt-1">Versendet alle E-Mail-Typen als Test an die angegebene Adresse.</p>
                    </div>
                </div>
            </div>

            {{-- Berechtigungen --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Berechtigungen</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left pb-2 text-xs font-medium text-gray-500 pr-6">Berechtigung</th>
                                <th class="text-left pb-2 text-xs font-medium text-gray-500">Erlaubt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-gray-600">
                            <tr>
                                <td class="py-2 pr-6 font-mono text-xs">baramundi.view</td>
                                <td class="py-2">Dashboard, Paketliste, Ereignislog einsehen · Manuellen Scan ausführen</td>
                            </tr>
                            <tr>
                                <td class="py-2 pr-6 font-mono text-xs">baramundi.edit</td>
                                <td class="py-2">Pakete anlegen, bearbeiten, löschen (schließt <em>view</em> ein)</td>
                            </tr>
                            <tr>
                                <td class="py-2 pr-6 font-mono text-xs">baramundi.config</td>
                                <td class="py-2">Einstellungen (SMB-Zugangsdaten, Scan-Intervall, Zammad-Gruppe) verwalten (schließt <em>edit</em> und <em>view</em> ein)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
