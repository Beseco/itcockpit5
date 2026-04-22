<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('hh.dashboard.index') }}" title="Zurück"
               class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800">Haushaltsplanung – Hilfe & Anleitung</h2>
        </div>
    </x-slot>

    @include('hh::partials.nav')

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Was ist dieses Modul?</h3>
                <p class="text-sm text-gray-600">
                    Die <strong>Haushaltsplanung (HH)</strong> ist das digitale Werkzeug zur Verwaltung des
                    IT-Budgets. Haushaltsjahre werden mit Versionen und einzelnen Budget-Positionen gepflegt,
                    können importiert und als Excel oder PDF exportiert werden.
                </p>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Hauptfunktionen</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Haushaltsjahre:</strong> Anlegen, Status setzen (Entwurf → Vorläufig → Genehmigt → Archiviert), löschen.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Budget-Positionen:</strong> Positionen pro Kostenstelle und Sachkonto erfassen, bearbeiten, löschen.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Wiederkehrende Positionen:</strong> Regelmäßige Posten als wiederkehrend markieren und ins nächste Jahr übertragen.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Import:</strong> Budget-Positionen per CSV importieren (mit Duplikat-Erkennung).</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Export:</strong> Komplettes Haushaltsjahr als Excel oder PDF exportieren.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Kostenstellen & Sachkonten:</strong> Stammdaten für die Planung verwalten.</span></li>
                    <li class="flex gap-2"><span class="text-indigo-500 font-bold mt-0.5">→</span><span><strong>Audit-Log:</strong> Alle Änderungen werden protokolliert und sind nachvollziehbar.</span></li>
                </ul>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Rollen & Berechtigungen</h3>
                <div class="grid sm:grid-cols-2 gap-4 text-sm text-gray-600">
                    <div class="bg-indigo-50 rounded p-3">
                        <div class="font-semibold text-indigo-800 mb-1">Leiter</div>
                        <ul class="space-y-1 text-xs">
                            <li>→ Haushaltsjahre anlegen, bearbeiten, löschen</li>
                            <li>→ Status ändern (Entwurf → Genehmigt)</li>
                            <li>→ Positionen in genehmigten Jahren sperren</li>
                            <li>→ Wiederkehrende Positionen übertragen</li>
                            <li>→ Import durchführen</li>
                        </ul>
                    </div>
                    <div class="bg-gray-50 rounded p-3">
                        <div class="font-semibold text-gray-700 mb-1">Mitarbeiter</div>
                        <ul class="space-y-1 text-xs">
                            <li>→ Dashboard und Positionen einsehen</li>
                            <li>→ Kostenstellen und Sachkonten einsehen</li>
                            <li>→ Export (Excel / PDF)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Typischer Ablauf (Leiter)</h3>
                <ol class="space-y-2 text-sm text-gray-600 list-none">
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">1</span><span>Neues <strong>Haushaltsjahr</strong> anlegen (Status: Entwurf).</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">2</span><span>Wiederkehrende Positionen aus dem Vorjahr <strong>übertragen</strong>.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">3</span><span>Neue Positionen manuell erfassen oder per <strong>CSV importieren</strong>.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">4</span><span>Status auf <strong>Vorläufig</strong> setzen für die interne Abstimmung.</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">5</span><span>Nach Freigabe: Status auf <strong>Genehmigt</strong> setzen (Positionen werden schreibgeschützt).</span></li>
                    <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">6</span><span>Haushaltsjahr als <strong>Excel / PDF exportieren</strong>.</span></li>
                </ol>
            </div>

        </div>
    </div>
</x-app-layout>
