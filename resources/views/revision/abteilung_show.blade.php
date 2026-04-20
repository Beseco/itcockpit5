<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Softwareprüfung: {{ $abteilung->anzeigename }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-10 px-4">

<div class="max-w-3xl mx-auto">

    {{-- Header --}}
    <div class="bg-indigo-700 rounded-t-xl px-8 py-6">
        <p class="text-xs font-semibold uppercase tracking-widest text-indigo-300 mb-1">IT Cockpit · Abteilungsrevision</p>
        <h1 class="text-2xl font-bold text-white">{{ $abteilung->anzeigename }}</h1>
        @if($abteilung->revision_date)
            <p class="text-sm text-indigo-300 mt-1">Revisionsdatum: {{ $abteilung->revision_date->format('d.m.Y') }}</p>
        @endif
    </div>

    <div class="bg-white rounded-b-xl shadow border border-t-0 border-gray-200 px-8 py-8">

        @if($alreadyDone)
            <div class="text-center py-8">
                <div class="text-5xl mb-4">✅</div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Rückmeldung bereits übermittelt</h2>
                <p class="text-gray-500">Diese Revision wurde bereits abgeschlossen. Vielen Dank!</p>
            </div>
        @else

            <p class="text-gray-600 mb-6">
                Bitte überprüfen Sie die unten aufgeführten Applikationen Ihrer Abteilung.
                Tragen Sie Anmerkungen oder Änderungswünsche direkt in die Felder ein und schlagen Sie
                ggf. noch nicht erfasste Software vor. Ihre Rückmeldung geht direkt an die IT-Abteilung.
            </p>

            <form action="{{ route('abteilung-revision.submit', $token) }}" method="POST">
                @csrf

                {{-- Applikationen --}}
                @php $applikationen = $abteilung->applikationen->sortBy('name'); @endphp

                @if($applikationen->isEmpty())
                    <div class="rounded-lg bg-yellow-50 border border-yellow-200 px-5 py-4 mb-6 text-sm text-yellow-700">
                        Für diese Abteilung sind noch keine Applikationen erfasst.
                    </div>
                @else
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">
                        Erfasste Applikationen ({{ $applikationen->count() }})
                    </h2>

                    <div class="space-y-4 mb-8">
                        @foreach($applikationen as $app)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $app->name }}</p>
                                    @if($app->einsatzzweck)
                                        <p class="text-xs text-gray-500 mt-0.5">{{ Str::limit($app->einsatzzweck, 120) }}</p>
                                    @endif
                                    <div class="flex flex-wrap items-center gap-2 mt-1.5 text-xs text-gray-500">
                                        @if($app->adminUser)
                                            <span>IT-Admin: <strong>{{ $app->adminUser->name }}</strong></span>
                                        @endif
                                        @if($app->verantwortlichAdUser)
                                            <span>· Verantw.: <strong>{{ $app->verantwortlichAdUser->anzeigename }}</strong></span>
                                        @endif
                                        @if($app->hersteller)
                                            <span>· Hersteller: {{ $app->hersteller }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex gap-1 shrink-0">
                                    @foreach(['confidentiality' => 'C', 'integrity' => 'I', 'availability' => 'V'] as $field => $label)
                                        @php $val = $app->$field; @endphp
                                        <span class="text-xs font-bold px-1.5 py-0.5 rounded
                                            {{ $val === 'C' ? 'bg-red-100 text-red-700' : ($val === 'B' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') }}">
                                            {{ $label }}:{{ $val }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">
                                    Anmerkungen / Änderungswünsche zu dieser Applikation
                                </label>
                                <textarea name="feedback[{{ $app->id }}]" rows="2"
                                          placeholder="z.B. nicht mehr in Verwendung, anderer Ansprechpartner, ..."
                                          class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 resize-none"></textarea>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif

                {{-- Neue Software --}}
                <div class="mb-8">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Neue Software vorschlagen</h2>
                    <div id="neue-software-container" class="space-y-2">
                        <div class="neue-software-row flex gap-2">
                            <input type="text" name="neue_software[0][name]" placeholder="Name der Software"
                                   class="flex-1 text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <input type="text" name="neue_software[0][zweck]" placeholder="Einsatzzweck (optional)"
                                   class="flex-1 text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    <button type="button" onclick="addSoftwareRow()"
                            class="mt-2 text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                        + Weitere Software hinzufügen
                    </button>
                </div>

                {{-- Allgemeine Anmerkungen --}}
                <div class="mb-8">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Allgemeine Anmerkungen</h2>
                    <textarea name="anmerkungen" rows="3" placeholder="Sonstige Hinweise an die IT-Abteilung..."
                              class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                        Rückmeldung senden
                    </button>
                </div>

            </form>
        @endif

    </div>

    <p class="text-center text-xs text-gray-400 mt-6">
        IT Cockpit &middot; automatisch generiert
    </p>
</div>

<script>
var softwareIndex = 1;
function addSoftwareRow() {
    var container = document.getElementById('neue-software-container');
    var div = document.createElement('div');
    div.className = 'neue-software-row flex gap-2';
    div.innerHTML = '<input type="text" name="neue_software[' + softwareIndex + '][name]" placeholder="Name der Software" class="flex-1 text-sm border-gray-300 rounded-md shadow-sm">'
                  + '<input type="text" name="neue_software[' + softwareIndex + '][zweck]" placeholder="Einsatzzweck (optional)" class="flex-1 text-sm border-gray-300 rounded-md shadow-sm">';
    container.appendChild(div);
    softwareIndex++;
}
</script>

</body>
</html>
