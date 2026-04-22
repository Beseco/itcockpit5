<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('tickets.index') }}" title="Zurück"
               class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800">Tickets – Hilfe & Anleitung</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Was ist dieses Modul?</h3>
                <p class="text-sm text-gray-600">
                    Das Modul <strong>Tickets</strong> zeigt IT-Helpdesk-Tickets aus dem angebundenen
                    Ticketsystem (z. B. Zammad) direkt im IT Cockpit. So behalten IT-Mitarbeiter
                    alle offenen Anfragen im Blick, ohne zwischen Anwendungen wechseln zu müssen.
                </p>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Hauptfunktionen</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Ticket-Übersicht:</strong> Offene, zugewiesene und eskalierte Tickets auf einen Blick.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Status & Priorität:</strong> Schnelle Einschätzung nach Dringlichkeit und Bearbeitungsstand.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Direktlink:</strong> Ticket im externen System mit einem Klick öffnen.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Einstellungen:</strong> API-Verbindung zum Ticketsystem und Anzeigefilter konfigurieren.</span></li>
                </ul>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Typischer Ablauf</h3>
                <ol class="space-y-2 text-sm text-gray-600 list-none">
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">1</span><span>In <strong>Einstellungen</strong> die API-URL und den Token zum Ticketsystem hinterlegen.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">2</span><span>Ticket-Übersicht öffnen – alle offenen Tickets werden geladen.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">3</span><span>Ticket anklicken → Direktlink öffnet das Ticket im externen System.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">4</span><span>Eskalierte oder lange offene Tickets identifizieren und priorisieren.</span></li>
                </ol>
            </div>

        </div>
    </div>
</x-app-layout>
