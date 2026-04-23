<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('roles.index') }}" title="Zurück" class="text-gray-400 hover:text-gray-600 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
            <h2 class="font-semibold text-xl text-gray-800">Rollenverwaltung – Hilfe & Anleitung</h2>
        </div>
    </x-slot>
    <div class="py-8"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Was ist dieses Modul?</h3>
            <p class="text-sm text-gray-600">Die <strong>Rollenverwaltung</strong> definiert, wer was im IT Cockpit darf. Rollen bündeln Berechtigungen und werden Benutzern zugewiesen. So wird z. B. gesteuert, wer Module sehen oder bearbeiten darf.</p>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Hauptfunktionen</h3>
            <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Rollen anlegen:</strong> Neue Rolle mit Namen und Beschreibung erstellen.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Berechtigungen zuweisen:</strong> Welche Aktionen (view, edit, config, delete …) hat die Rolle?</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Modul-Zugriff:</strong> Pro Modul steuerbar, ob eine Rolle Lese- oder Schreibzugriff hat.</span></li>
            </ul>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Typischer Ablauf</h3>
            <ol class="space-y-2 text-sm text-gray-600 list-none">
                <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">1</span><span>Neue Rolle anlegen (z. B. „IT-Mitarbeiter", „HH-Leiter").</span></li>
                <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">2</span><span>Berechtigungen für die benötigten Module setzen.</span></li>
                <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">3</span><span>Rolle in der Benutzerverwaltung den richtigen Benutzern zuweisen.</span></li>
            </ol>
        </div>

    </div></div>
</x-app-layout>
