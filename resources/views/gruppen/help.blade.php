<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('gruppen.index') }}" title="Zurück" class="text-gray-400 hover:text-gray-600 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
            <h2 class="font-semibold text-xl text-gray-800">Gruppenverwaltung – Hilfe & Anleitung</h2>
        </div>
    </x-slot>
    <div class="py-8"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Was ist dieses Modul?</h3>
            <p class="text-sm text-gray-600">Die <strong>Gruppenverwaltung</strong> organisiert Mitarbeiter in fachliche Gruppen (z. B. Abteilungen oder Teams). Gruppen werden u. a. für die Zuweisung von Aufgaben und Rollen & Aufgaben verwendet.</p>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Hauptfunktionen</h3>
            <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Gruppen anlegen:</strong> Neue Gruppe mit Namen und ggf. Beschreibung erstellen.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Mitglieder zuweisen:</strong> Benutzer einer Gruppe zuordnen.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Gruppenübersicht:</strong> Alle Gruppen mit Mitgliederzahl einsehen.</span></li>
            </ul>
        </div>

    </div></div>
</x-app-layout>
