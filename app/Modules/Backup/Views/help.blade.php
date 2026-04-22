<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('backup.index') }}" title="Zurück"
               class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800">Backup – Hilfe & Anleitung</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Was ist dieses Modul?</h3>
                <p class="text-sm text-gray-600">
                    Das Modul <strong>Backup</strong> überwacht und verwaltet Datensicherungen.
                    Es zeigt den aktuellen Backup-Status auf einen Blick und ermöglicht das manuelle
                    Auslösen sowie den Download von Sicherungsdateien.
                </p>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Hauptfunktionen</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Status-Übersicht:</strong> Ampel-Anzeige (Grün/Gelb/Rot) für den aktuellen Backup-Zustand.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Manuelles Backup:</strong> Sicherung per Klick sofort auslösen.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Download:</strong> Ältere Backup-Dateien direkt herunterladen.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Einstellungen:</strong> Automatischen Backup-Zeitplan und Speicherpfad konfigurieren.</span></li>
                </ul>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Typischer Ablauf</h3>
                <ol class="space-y-2 text-sm text-gray-600 list-none">
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">1</span><span>Status der letzten Backups in der Übersicht prüfen.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">2</span><span>Bei Bedarf manuell ein <strong>Backup erstellen</strong>.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">3</span><span>Im Fehlerfall: Protokoll auswerten und Einstellungen anpassen.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">4</span><span>Zur Wiederherstellung: Gewünschtes Backup <strong>herunterladen</strong>.</span></li>
                </ol>
            </div>

        </div>
    </div>
</x-app-layout>
