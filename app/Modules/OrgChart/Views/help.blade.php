<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('orgchart.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Hilfe: Organigramm-Planer</h2>
        </div>
    </x-slot>
    <div class="py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white shadow-sm rounded-lg p-6 space-y-4 text-sm text-gray-700">
            <h3 class="font-semibold text-gray-900 text-base">Was ist dieses Modul?</h3>
            <p>Der Organigramm-Planer ermöglicht es, verschiedene Aufbauorganisationen zu modellieren, miteinander zu vergleichen und grafisch darzustellen. Ideal für Organisationsuntersuchungen oder die Planung von Umstrukturierungen.</p>

            <h3 class="font-semibold text-gray-900 text-base mt-4">Hauptfunktionen</h3>
            <ul class="list-disc list-inside space-y-1 text-gray-600">
                <li><strong>Versionen</strong> – Mehrere Szenarien (Ist-Stand, Soll-Konzept, Variante A/B) parallel pflegen</li>
                <li><strong>Hierarchische Struktur</strong> – Leitungsebene → Stabsstellen → Gruppen → Untergruppen → Aufgaben</li>
                <li><strong>Personalkapazität</strong> – FTE (Vollzeitäquivalente) pro Gruppe hinterlegen</li>
                <li><strong>Schnittstellen</strong> – Verbindungen zwischen Gruppen definieren und dokumentieren</li>
                <li><strong>Grafische Ansicht</strong> – Organigramm mit 4 wählbaren Farbschemata visualisieren</li>
                <li><strong>Duplizieren</strong> – Bestehende Version als neuen Entwurf klonen</li>
                <li><strong>PDF / Druck</strong> – Grafik exportieren oder im Browser drucken (A3 Querformat)</li>
            </ul>

            <h3 class="font-semibold text-gray-900 text-base mt-4">Typischer Ablauf</h3>
            <ol class="list-decimal list-inside space-y-1 text-gray-600">
                <li>„Neue Version" anlegen, Status auf „Entwurf" setzen</li>
                <li>Im Editor Knoten anlegen: zuerst Rahmen (Gruppen), dann Untergruppen, dann Aufgaben</li>
                <li>Personalkapazitäten (FTE) und Farben hinterlegen</li>
                <li>Schnittstellen zwischen Gruppen definieren</li>
                <li>Grafische Ansicht prüfen, ggf. Farbschema anpassen</li>
                <li>Status auf „In Abstimmung" setzen und teilen</li>
                <li>Nach Freigabe: Status auf „Aktiv" setzen (archiviert vorherige aktive Version automatisch)</li>
            </ol>
        </div>
    </div>
</x-app-layout>
