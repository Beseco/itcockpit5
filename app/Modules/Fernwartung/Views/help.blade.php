<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('fernwartung.index') }}" title="Zurück"
               class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800">Fernwartung – Hilfe & Anleitung</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Was ist dieses Modul?</h3>
                <p class="text-sm text-gray-600">
                    Das Modul <strong>Fernwartung</strong> dokumentiert und verwaltet Remote-Zugriffe auf
                    Systeme. Fernwartungssessions werden mit Ziel-System, verwendetem Tool, Techniker und
                    Zeitraum erfasst – für eine lückenlose Nachvollziehbarkeit.
                </p>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Hauptfunktionen</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Session erfassen:</strong> Neue Fernwartung mit Ziel, Tool und Beschreibung anlegen.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Session beenden:</strong> Endzeit einer laufenden Fernwartung setzen.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Tools verwalten:</strong> Verwendete Fernwartungs-Tools (z. B. TeamViewer, RDP) hinterlegen.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Übersicht:</strong> Alle Sessions mit Status (laufend / abgeschlossen) auf einen Blick.</span></li>
                </ul>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Typischer Ablauf</h3>
                <ol class="space-y-2 text-sm text-gray-600 list-none">
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">1</span><span>Fernwartung beginnt → <strong>„Neue Fernwartung"</strong> anlegen.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">2</span><span>Ziel-System, Tool und Zweck eintragen, speichern.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">3</span><span>Remote-Arbeit durchführen.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">4</span><span>Nach Abschluss: Session in der Liste <strong>beenden</strong> (Endzeit setzen).</span></li>
                </ol>
            </div>

        </div>
    </div>
</x-app-layout>
