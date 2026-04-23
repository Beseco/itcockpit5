<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('aufgaben.index') }}" title="Zurück" class="text-gray-400 hover:text-gray-600 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
            <h2 class="font-semibold text-xl text-gray-800">Rollen & Aufgaben – Hilfe & Anleitung</h2>
        </div>
    </x-slot>
    <div class="py-8"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Was ist dieses Modul?</h3>
            <p class="text-sm text-gray-600"><strong>Rollen & Aufgaben</strong> verwaltet betriebliche Aufgaben und deren Zuweisung an Mitarbeiter. Es schafft Transparenz darüber, wer welche IT-Verantwortlichkeiten trägt.</p>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Hauptfunktionen</h3>
            <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Aufgaben anlegen:</strong> IT-Aufgaben/-Rollen mit Bezeichnung und Beschreibung erfassen.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Zuweisungen:</strong> Mitarbeiter als Hauptverantwortliche oder Vertreter zuordnen.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Gruppen-Filter:</strong> Aufgaben nach Gruppe/Team filtern.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Export:</strong> Aufgabenliste als Excel oder PDF exportieren.</span></li>
            </ul>
        </div>

    </div></div>
</x-app-layout>
