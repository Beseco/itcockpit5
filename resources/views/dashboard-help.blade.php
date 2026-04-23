<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" title="Zurück" class="text-gray-400 hover:text-gray-600 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
            <h2 class="font-semibold text-xl text-gray-800">Dashboard – Hilfe & Anleitung</h2>
        </div>
    </x-slot>
    <div class="py-8"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Was ist das Dashboard?</h3>
            <p class="text-sm text-gray-600">Das <strong>Dashboard</strong> ist die Startseite des IT Cockpits. Es zeigt auf einen Blick die wichtigsten Informationen: anstehende Revisionen, offene Tickets, Zertifikatsablaufdaten und aktuelle Systemhinweise.</p>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Hauptfunktionen</h3>
            <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Übersichts-Kacheln:</strong> Schnelle Kennzahlen zu Applikationen, Servern, Zertifikaten und Tickets.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Anstehende Revisionen:</strong> Welche Applikationen müssen bald revidiert werden?</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Systemankündigungen:</strong> Aktuelle Hinweise der IT-Administration.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Navigation:</strong> Über die Seitenleiste alle Module erreichen.</span></li>
            </ul>
        </div>

    </div></div>
</x-app-layout>
