<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('reminders.index') }}" title="Zurück" class="text-gray-400 hover:text-gray-600 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
            <h2 class="font-semibold text-xl text-gray-800">Erinnerungen – Hilfe & Anleitung</h2>
        </div>
    </x-slot>
    <div class="py-8"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Was ist dieses Modul?</h3>
            <p class="text-sm text-gray-600"><strong>Erinnerungen</strong> ermöglicht das Einrichten von Fälligkeits- und Terminbenachrichtigungen. Du wirst per E-Mail oder Dashboard-Hinweis rechtzeitig an wichtige Fristen erinnert.</p>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Hauptfunktionen</h3>
            <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Erinnerung anlegen:</strong> Titel, Fälligkeitsdatum und Benachrichtigungsvorlauf festlegen.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Verknüpfungen:</strong> Erinnerungen können mit Applikationen, Zertifikaten oder anderen Objekten verknüpft werden.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Benachrichtigungskanal:</strong> E-Mail-Versand an den zuständigen Mitarbeiter.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Erledigt markieren:</strong> Abgearbeitete Erinnerungen abhaken.</span></li>
            </ul>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Typischer Ablauf</h3>
            <ol class="space-y-2 text-sm text-gray-600 list-decimal list-inside">
                <li>„Neue Erinnerung" anlegen und Fälligkeitsdatum sowie Vorlauf eintragen.</li>
                <li>Zuständigen Empfänger auswählen.</li>
                <li>Das System versendet die Benachrichtigung automatisch zum konfigurierten Zeitpunkt.</li>
                <li>Nach Bearbeitung die Erinnerung als erledigt markieren.</li>
            </ol>
        </div>

    </div></div>
</x-app-layout>
