<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('adusers.index') }}" title="Zurück"
               class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800">AD-Benutzer – Hilfe & Anleitung</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Was ist dieses Modul?</h3>
                <p class="text-sm text-gray-600">
                    Das Modul <strong>AD-Benutzer</strong> synchronisiert Benutzerkonten aus dem Active Directory
                    in das IT Cockpit. Es bietet eine zentrale Übersicht über alle Benutzer und ermöglicht
                    einen strukturierten Offboarding-Prozess für ausgeschiedene Mitarbeiter.
                </p>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Hauptfunktionen</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Benutzerübersicht:</strong> Alle AD-Konten durchsuchen und filtern (Name, Abteilung, Status).</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>AD-Synchronisation:</strong> Manuell per „Jetzt synchronisieren"-Button oder automatisch per Zeitplan.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Offboarding:</strong> Strukturierter Ablauf für das Sperren und Dokumentieren von ausgeschiedenen Mitarbeitern.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Einstellungen:</strong> AD-Verbindung (Server, Bind-User, Suchpfade) konfigurieren.</span></li>
                </ul>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Typischer Ablauf</h3>
                <ol class="space-y-2 text-sm text-gray-600 list-none">
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">1</span><span>Unter <strong>Einstellungen</strong> die AD-Verbindung einrichten und testen.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">2</span><span><strong>Synchronisation</strong> starten – Benutzer werden importiert/aktualisiert.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">3</span><span>Benutzer in der Liste suchen und Detailinformationen einsehen.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">4</span><span>Bei Ausscheiden eines Mitarbeiters: <strong>Offboarding</strong> einleiten und dokumentieren.</span></li>
                </ol>
            </div>

        </div>
    </div>
</x-app-layout>
