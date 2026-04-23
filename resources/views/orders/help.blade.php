<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('orders.index') }}" title="Zurück" class="text-gray-400 hover:text-gray-600 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
            <h2 class="font-semibold text-xl text-gray-800">Bestellungen – Hilfe & Anleitung</h2>
        </div>
    </x-slot>
    <div class="py-8"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Was ist dieses Modul?</h3>
            <p class="text-sm text-gray-600"><strong>Bestellungen</strong> verwaltet IT-Beschaffungsvorgänge. Bestellungen können erfasst, einem Haushaltsprojekt zugeordnet und durch den Genehmigungsprozess geführt werden.</p>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Hauptfunktionen</h3>
            <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Bestellung anlegen:</strong> Artikel, Menge, Preis und Lieferant erfassen.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>HH-Projekt-Verknüpfung:</strong> Bestellung einem Haushaltsplan-Projekt zuordnen für automatische Budgetverfolgung.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Status verfolgen:</strong> Offen → Genehmigt → Bestellt → Geliefert.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Export:</strong> Bestellliste als Excel oder PDF exportieren.</span></li>
            </ul>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Typischer Ablauf</h3>
            <ol class="space-y-2 text-sm text-gray-600 list-decimal list-inside">
                <li>„Neue Bestellung" anlegen und Artikel sowie Kosten eintragen.</li>
                <li>HH-Projekt zuordnen, damit die Kosten dem Budget abgezogen werden.</li>
                <li>Bestellung genehmigen lassen und Status aktualisieren.</li>
                <li>Nach Lieferung als „Geliefert" markieren.</li>
            </ol>
        </div>

    </div></div>
</x-app-layout>
