<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('announcements.index') }}" title="Zurück" class="text-gray-400 hover:text-gray-600 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
            <h2 class="font-semibold text-xl text-gray-800">Ankündigungen – Hilfe & Anleitung</h2>
        </div>
    </x-slot>
    <div class="py-8"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Was ist dieses Modul?</h3>
            <p class="text-sm text-gray-600"><strong>Ankündigungen</strong> ermöglicht das Veröffentlichen von Hinweisen und Neuigkeiten für alle IT-Cockpit-Nutzer. Ankündigungen erscheinen auf dem Dashboard und informieren das Team über wichtige Ereignisse oder Änderungen.</p>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Hauptfunktionen</h3>
            <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Ankündigung erstellen:</strong> Neue Meldung mit Titel, Inhalt und optionalem Gültigkeitszeitraum anlegen.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Sichtbarkeit steuern:</strong> Ankündigungen können aktiviert oder deaktiviert werden.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Bearbeiten & Löschen:</strong> Bestehende Ankündigungen jederzeit aktualisieren oder entfernen.</span></li>
            </ul>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Typischer Ablauf</h3>
            <ol class="space-y-2 text-sm text-gray-600 list-decimal list-inside">
                <li>„Neue Ankündigung" klicken und Titel sowie Text eingeben.</li>
                <li>Gültigkeitszeitraum festlegen (optional).</li>
                <li>Speichern – die Ankündigung ist sofort für alle Nutzer sichtbar.</li>
                <li>Veraltete Ankündigungen deaktivieren oder löschen.</li>
            </ol>
        </div>

    </div></div>
</x-app-layout>
