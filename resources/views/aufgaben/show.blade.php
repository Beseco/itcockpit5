<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                @if($aufgabe->parent)
                    <p class="text-sm text-gray-500 mb-1">
                        <a href="{{ route('aufgaben.show', $aufgabe->parent) }}" class="hover:underline">{{ $aufgabe->parent->name }}</a>
                        &rsaquo;
                    </p>
                @endif
                <h2 class="text-xl font-semibold text-gray-800">{{ $aufgabe->name }}</h2>
            </div>
            <div class="flex items-center gap-2 no-print">
                <button onclick="window.print()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Drucken
                </button>
                @can('base.aufgaben.edit')
                    <a href="{{ route('aufgaben.edit', $aufgabe) }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                        Bearbeiten
                    </a>
                @endcan
                <a href="{{ route('aufgaben.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                    Zurück
                </a>
            </div>
        </div>
    </x-slot>

    @push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css" media="screen">
    <style>
        @media print {
            /* Sidebar, Top-Nav, Header-Buttons, Footer ausblenden */
            body > div > div:first-child,          /* Overlay */
            body > div > div:nth-child(2),          /* Mobile sidebar */
            body > div > div:nth-child(3),          /* Desktop sidebar */
            .no-print,
            nav, header, footer { display: none !important; }

            /* Content-Bereich ohne Sidebar-Einrückung */
            .lg\\:pl-64 { padding-left: 0 !important; }

            /* Seitenränder */
            body { background: white !important; }
            .py-6 { padding-top: 0.5rem !important; padding-bottom: 0 !important; }
            .max-w-4xl { max-width: 100% !important; }
            .shadow, .rounded-lg { box-shadow: none !important; border-radius: 0 !important; }
            .bg-white { background: white !important; }

            /* Seitenumbrüche */
            .aufgabe-block { page-break-inside: avoid; }

            /* Drucktitel */
            .print-title { display: block !important; }

            /* Links im Druck als normalen Text */
            .no-print-link { color: inherit !important; text-decoration: none !important; }
            .no-print { display: none !important; }
        }
        .print-title { display: none; }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        marked.setOptions({ breaks: true });
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.md-render[data-md]').forEach(function (el) {
                el.innerHTML = marked.parse(el.dataset.md);
            });
        });
    </script>
    @endpush

    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Drucktitel (nur beim Drucken sichtbar) --}}
        <div class="print-title" style="margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:2px solid #1e293b;">
            <p style="font-size:0.75rem; color:#64748b; margin:0 0 0.25rem;">IT Cockpit – Rollen & Aufgaben</p>
            <h1 style="font-size:1.4rem; font-weight:700; color:#0f172a; margin:0;">{{ $aufgabe->name }}</h1>
        </div>

        <div class="bg-white shadow rounded-lg p-6">

            {{-- Eigene Beschreibung + Zuweisungen (Ebene 0) --}}
            @include('aufgaben._show_node', ['node' => $aufgabe, 'depth' => 0])

        </div>
    </div>
</x-app-layout>
