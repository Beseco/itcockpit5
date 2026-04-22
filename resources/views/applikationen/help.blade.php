<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('applikationen.index') }}" title="Zurück"
               class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800">Applikationen – Hilfe & Anleitung</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Was ist dieses Modul?</h3>
                <p class="text-sm text-gray-600">
                    Das Modul <strong>Applikationen</strong> ist das zentrale Softwareverzeichnis des Landratsamtes.
                    Alle eingesetzten Anwendungen werden hier dokumentiert – inklusive Verantwortlichkeiten,
                    Revisionsfristen und Abteilungszuordnungen. So behält die IT den Überblick über den
                    gesamten Softwarebestand und kann Revisionen fristgerecht durchführen.
                </p>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Hauptfunktionen</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Applikation anlegen:</strong> Software mit Name, Hersteller, Version und Beschreibung erfassen.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Abteilungszuordnung:</strong> Welche Abteilungen nutzen die Software? Verantwortliche zuweisen.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Revisionen:</strong> Revisionsfristen hinterlegen und den Revisionsablauf steuern (Anforderung → Bestätigung).</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>E-Mail-Benachrichtigungen:</strong> Automatische Erinnerungen an Verantwortliche bei fälligen Revisionen.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Export:</strong> Gesamte Applikationsliste als Excel oder PDF herunterladen.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Schutzbedarf:</strong> Datenschutzrelevanz und Schutzbedarf pro Applikation dokumentieren.</span></li>
                </ul>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Typischer Ablauf</h3>
                <ol class="space-y-2 text-sm text-gray-600 list-none">
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">1</span><span>Neue Software im Einsatz → <strong>Applikation anlegen</strong> mit allen relevanten Metadaten.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">2</span><span>Zuständige <strong>Abteilungen und Verantwortliche</strong> zuordnen.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">3</span><span><strong>Revisionsfrist</strong> setzen – das System erinnert automatisch per E-Mail.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">4</span><span>Abteilungsverantwortliche bestätigen die Revision im System.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">5</span><span>Liste bei Bedarf als <strong>Excel oder PDF exportieren</strong>.</span></li>
                </ol>
            </div>

        </div>
    </div>
</x-app-layout>
