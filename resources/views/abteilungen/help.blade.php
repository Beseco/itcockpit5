<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('abteilungen.index') }}" title="Zurück" class="text-gray-400 hover:text-gray-600 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
            <h2 class="font-semibold text-xl text-gray-800">Abteilungen / Sachgebiete – Hilfe & Anleitung</h2>
        </div>
    </x-slot>
    <div class="py-8"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Was ist dieses Modul?</h3>
            <p class="text-sm text-gray-600"><strong>Abteilungen / Sachgebiete</strong> verwaltet die Organisationseinheiten des Landratsamtes. Jede Abteilung kann Software-Verantwortliche haben und erhält Revisionsbenachrichtigungen für ihre Applikationen.</p>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Hauptfunktionen</h3>
            <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Abteilungen verwalten:</strong> Anlegen, bearbeiten und löschen von Abteilungen/Sachgebieten.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Verantwortliche:</strong> IT-Ansprechpartner und Abteilungsverantwortliche hinterlegen.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Revisionseinstellungen:</strong> Konfiguration der automatischen Revisions-E-Mails an die Abteilungen.</span></li>
                <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Test-E-Mail:</strong> Revisionsbenachrichtigung für eine Abteilung testweise versenden.</span></li>
            </ul>
        </div>

    </div></div>
</x-app-layout>
