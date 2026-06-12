<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('vertragsmanagement.index') }}" title="Zurück" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800">Vertragsmanagement – Hilfe &amp; Anleitung</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Was ist dieses Modul?</h3>
                <p class="text-sm text-gray-600">
                    Das <strong>Vertragsmanagement</strong> verwaltet IT-Verträge inklusive Laufzeiten, Kündigungsfristen
                    und zugehörigen PDF-Dokumenten. Verträge können mit einem <strong>Dienstleister</strong> verknüpft werden.
                    Vor Vertragsablauf versendet das System automatisch <strong>wöchentliche Erinnerungen</strong> per E-Mail.
                </p>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Erinnerungs-Logik</h3>
                <ol class="space-y-2 text-sm text-gray-600 list-decimal list-inside">
                    <li>Ab <em>Vertragsende minus „Erinnerung Vorlauf (Wochen)"</em> beginnt die Erinnerungsphase.</li>
                    <li>Ein täglicher Cronjob (<code class="bg-gray-100 px-1 rounded">contracts:send-reminders</code>, 08:00&nbsp;Uhr) prüft alle aktiven Verträge.</li>
                    <li>Liegt das heutige Datum in der Erinnerungsphase und ist die letzte Erinnerung ≥&nbsp;7&nbsp;Tage her, wird eine E-Mail gesendet → also genau <strong>einmal pro Woche</strong>.</li>
                    <li>Empfänger: die Vertrags-E-Mail; ist diese leer, die Fallback-Adresse aus den Einstellungen; sonst die globale Absenderadresse.</li>
                    <li>Verträge, deren Vertragsende in der Vergangenheit liegt, werden automatisch auf <em>abgelaufen</em> gesetzt.</li>
                </ol>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Hauptfunktionen</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Verträge anlegen:</strong> Name, Dienstleister, Laufzeit, Kündigungsfrist, Vorlauf-Wochen und optionale E-Mail.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>PDF-Dokumente:</strong> Beliebig viele Vertragsdokumente hochladen (max. 20&nbsp;MB je Datei), sicher im Dateisystem gespeichert.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Status:</strong> aktiv, gekündigt, abgelaufen – farblich gekennzeichnet.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Automatische Erinnerungen:</strong> wöchentlich in der Vorlaufphase.</span></li>
                </ul>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Konsolenbefehle</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <code class="block bg-gray-100 px-3 py-2 rounded font-mono text-xs">php artisan contracts:send-reminders</code>
                        <p class="text-gray-500 mt-1">Erinnerungen sofort prüfen und versenden (läuft sonst täglich automatisch).</p>
                    </div>
                    <div>
                        <code class="block bg-gray-100 px-3 py-2 rounded font-mono text-xs">php artisan contracts:send-reminders --dry-run</code>
                        <p class="text-gray-500 mt-1">Nur anzeigen, welche Erinnerungen gesendet würden – ohne tatsächlichen Versand.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Berechtigungen</h3>
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gray-50 text-gray-600">
                        <tr><td class="py-2 pr-6 font-mono text-xs">vertragsmanagement.view</td><td class="py-2">Verträge und Dokumente einsehen, Dokumente herunterladen</td></tr>
                        <tr><td class="py-2 pr-6 font-mono text-xs">vertragsmanagement.edit</td><td class="py-2">Verträge anlegen/bearbeiten/löschen, Dokumente verwalten (schließt <em>view</em> ein)</td></tr>
                        <tr><td class="py-2 pr-6 font-mono text-xs">vertragsmanagement.config</td><td class="py-2">Einstellungen (Fallback-E-Mail) verwalten (schließt <em>edit</em> und <em>view</em> ein)</td></tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
