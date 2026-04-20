<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neue Software vorschlagen</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-10 px-4">
<div class="max-w-2xl mx-auto">

    <div class="bg-indigo-700 rounded-t-xl px-8 py-5">
        <p class="text-xs font-semibold uppercase tracking-widest text-indigo-300 mb-0.5">IT Cockpit · Abteilungsrevision</p>
        <h1 class="text-xl font-bold text-white">{{ $abteilung->anzeigename }}</h1>
    </div>

    <div class="bg-indigo-600 px-8 py-2 text-sm text-indigo-200">
        Neue Software vorschlagen
    </div>

    <div class="bg-white rounded-b-xl shadow border border-t-0 border-gray-200 px-8 py-7">

        <p class="text-gray-600 mb-6 text-sm leading-relaxed">
            Wird in Ihrer Abteilung Software eingesetzt, die hier noch nicht erfasst ist?
            Tragen Sie diese bitte unten ein. Ihr Vorschlag wird direkt an die IT-Abteilung weitergeleitet.
        </p>

        <form id="neue-app-form" action="{{ route('abteilung-revision.neue-app.submit', $token) }}" method="POST">
            @csrf

            <div id="app-rows" class="space-y-4 mb-5">
                <div class="app-row border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <div class="grid grid-cols-1 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Name der Software <span class="text-red-500">*</span></label>
                            <input type="text" name="apps[0][name]" placeholder="z.B. Microsoft Teams"
                                   class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Einsatzzweck</label>
                            <input type="text" name="apps[0][einsatzzweck]" placeholder="Wofür wird die Software verwendet?"
                                   class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Hersteller</label>
                            <input type="text" name="apps[0][hersteller]" placeholder="z.B. Microsoft"
                                   class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" onclick="addRow()"
                    class="mb-6 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                + Weitere Software hinzufügen
            </button>

            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <button type="submit"
                        class="inline-flex items-center px-5 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 text-sm transition"
                        onclick="return validateApps()">
                    Vorschläge senden &amp; Abschließen
                </button>
            </div>
        </form>

        {{-- Skip-Formular außerhalb, kein required-Feld --}}
        <form action="{{ route('abteilung-revision.neue-app.submit', $token) }}" method="POST" class="mt-4 text-center">
            @csrf
            <input type="hidden" name="skip" value="1">
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 underline">
                Keine neuen Apps – Revision abschließen
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-gray-400 mt-5">IT Cockpit &middot; automatisch generiert</p>
</div>

<script>
var idx = 1;

function validateApps() {
    var names = document.querySelectorAll('[name^="apps"][name$="[name]"]');
    var filled = Array.from(names).some(function(el) { return el.value.trim() !== ''; });
    if (!filled) {
        alert('Bitte tragen Sie mindestens eine Software ein oder wählen Sie "Keine neuen Apps".');
        return false;
    }
    return true;
}

function addRow() {
    var tpl = '<div class="app-row border border-gray-200 rounded-lg p-4 bg-gray-50">'
        + '<div class="grid grid-cols-1 gap-3">'
        + '<div><label class="block text-xs font-medium text-gray-600 mb-1">Name der Software</label>'
        + '<input type="text" name="apps[' + idx + '][name]" placeholder="z.B. Adobe Acrobat"'
        + ' class="w-full text-sm border-gray-300 rounded-md shadow-sm"></div>'
        + '<div><label class="block text-xs font-medium text-gray-600 mb-1">Einsatzzweck</label>'
        + '<input type="text" name="apps[' + idx + '][einsatzzweck]" placeholder="Wofür wird die Software verwendet?"'
        + ' class="w-full text-sm border-gray-300 rounded-md shadow-sm"></div>'
        + '<div><label class="block text-xs font-medium text-gray-600 mb-1">Hersteller</label>'
        + '<input type="text" name="apps[' + idx + '][hersteller]"'
        + ' class="w-full text-sm border-gray-300 rounded-md shadow-sm"></div>'
        + '</div></div>';
    document.getElementById('app-rows').insertAdjacentHTML('beforeend', tpl);
    idx++;
}
</script>
</body>
</html>
