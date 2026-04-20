<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revision: {{ $app->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-10 px-4">
<div class="max-w-2xl mx-auto">

    {{-- Header --}}
    <div class="bg-indigo-700 rounded-t-xl px-8 py-5">
        <p class="text-xs font-semibold uppercase tracking-widest text-indigo-300 mb-0.5">IT Cockpit · Abteilungsrevision</p>
        <h1 class="text-xl font-bold text-white">{{ $abteilung->anzeigename }}</h1>
    </div>

    {{-- Fortschritt --}}
    <div class="bg-indigo-600 px-8 py-2 flex items-center justify-between text-sm text-indigo-200">
        <span>Applikation {{ $current }} von {{ $total }}</span>
        <div class="flex gap-1">
            @for($i = 1; $i <= $total; $i++)
                <span class="w-3 h-3 rounded-full {{ $i < $current ? 'bg-indigo-300' : ($i === $current ? 'bg-white' : 'bg-indigo-500') }}"></span>
            @endfor
        </div>
    </div>

    <div class="bg-white rounded-b-xl shadow border border-t-0 border-gray-200 px-8 py-7">

        @if($errors->any())
            <div class="mb-5 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- App-Info-Karte --}}
        <div class="mb-6">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">{{ $app->name }}</h2>
                    @if($app->abteilung)
                        <p class="text-xs text-gray-400 mt-0.5">{{ $app->abteilung->anzeigename }}</p>
                    @endif
                </div>
                <div class="flex gap-1 shrink-0">
                    @foreach(['C' => $app->confidentiality, 'I' => $app->integrity, 'V' => $app->availability] as $label => $val)
                        <span class="text-xs font-bold px-2 py-0.5 rounded
                            {{ $val === 'C' ? 'bg-red-100 text-red-700' : ($val === 'B' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') }}">
                            {{ $label }}:{{ $val }}
                        </span>
                    @endforeach
                </div>
            </div>

            <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                @if($app->einsatzzweck)
                <div class="col-span-2">
                    <dt class="text-xs font-medium text-gray-400">Beschreibung</dt>
                    <dd class="text-gray-700 mt-0.5">{{ $app->einsatzzweck }}</dd>
                </div>
                @endif
                @if($app->baustein)
                <div>
                    <dt class="text-xs font-medium text-gray-400">Typ (Baustein)</dt>
                    <dd class="text-gray-700">{{ $app->baustein }}</dd>
                </div>
                @endif
                @if($app->hersteller)
                <div>
                    <dt class="text-xs font-medium text-gray-400">Hersteller</dt>
                    <dd class="text-gray-700">{{ $app->hersteller }}</dd>
                </div>
                @endif
                @if($app->adminUser)
                <div>
                    <dt class="text-xs font-medium text-gray-400">IT-Administrator</dt>
                    <dd class="text-gray-700">{{ $app->adminUser->name }}</dd>
                </div>
                @endif
                @if($app->verantwortlichAdUser)
                <div>
                    <dt class="text-xs font-medium text-gray-400">Verfahrensverantwortlicher</dt>
                    <dd class="text-gray-700">{{ $app->verantwortlichAdUser->anzeigename }}</dd>
                </div>
                @endif
                @if($app->ansprechpartner)
                <div>
                    <dt class="text-xs font-medium text-gray-400">Ansprechpartner</dt>
                    <dd class="text-gray-700">{{ $app->ansprechpartner }}</dd>
                </div>
                @endif
                @if($app->revision_date)
                <div>
                    <dt class="text-xs font-medium text-gray-400">Revisionsdatum</dt>
                    <dd class="text-gray-700">{{ $app->revision_date->format('d.m.Y') }}</dd>
                </div>
                @endif
                @if($app->servers->isNotEmpty())
                <div class="col-span-2">
                    <dt class="text-xs font-medium text-gray-400">Server</dt>
                    <dd class="text-gray-700">{{ $app->servers->pluck('name')->join(', ') }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <div class="border-t border-gray-100 pt-5 mb-1">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-4">Ihre Rückmeldung</p>
        </div>

        <form action="{{ route('abteilung-revision.app.submit', [$token, $app->id]) }}" method="POST">
            @csrf

            {{-- Beschreibung --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Beschreibung / Einsatzzweck</label>
                <textarea name="einsatzzweck" rows="3"
                          class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('einsatzzweck', $app->einsatzzweck) }}</textarea>
            </div>

            {{-- Ansprechpartner --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Ansprechpartner</label>
                <input type="text" name="ansprechpartner"
                       value="{{ old('ansprechpartner', $app->ansprechpartner) }}"
                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            {{-- Verfahrensverantwortlicher --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Verfahrensverantwortlicher</label>
                <select name="verantwortlich_ad_user_id"
                        class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">— kein Verfahrensverantwortlicher —</option>
                    @foreach($adUsers as $u)
                        <option value="{{ $u->id }}"
                            {{ old('verantwortlich_ad_user_id', $app->verantwortlich_ad_user_id) == $u->id ? 'selected' : '' }}>
                            {{ $u->anzeigename }}{{ $u->email ? ' (' . $u->email . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Schutzbedarf --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Schutzbedarf</label>
                <div class="grid grid-cols-3 gap-3">
                    @foreach(['confidentiality' => 'Vertraulichkeit (C)', 'integrity' => 'Integrität (I)', 'availability' => 'Verfügbarkeit (V)'] as $field => $label)
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ $label }}</label>
                        <select name="{{ $field }}" id="sel_{{ $field }}" onchange="checkSchutzbedarf()"
                                class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach(['A' => 'A – Normal', 'B' => 'B – Hoch', 'C' => 'C – Sehr hoch'] as $val => $lbl)
                                <option value="{{ $val }}" data-orig="{{ $app->$field }}"
                                    {{ old($field, $app->$field) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach
                </div>

                <div id="reason-box" class="mt-3 {{ old('reason') ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Begründung für Schutzbedarf-Änderung <span class="text-red-500">*</span>
                    </label>
                    <textarea id="reason" name="reason" rows="2"
                              class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500
                                     {{ $errors->has('reason') ? 'border-red-400' : '' }}">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Buttons --}}
            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <button type="submit" name="skip" value="1"
                        class="text-sm text-gray-500 hover:text-gray-700 underline">
                    Keine Änderungen – Weiter
                </button>
                <button type="submit"
                        class="inline-flex items-center px-5 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 text-sm transition">
                    Rückmeldung senden &amp; Weiter →
                </button>
            </div>
        </form>

    </div>

    <p class="text-center text-xs text-gray-400 mt-5">IT Cockpit &middot; automatisch generiert</p>
</div>

<script>
var origC = '{{ $app->confidentiality }}';
var origI = '{{ $app->integrity }}';
var origA = '{{ $app->availability }}';

function checkSchutzbedarf() {
    var c = document.querySelector('[name=confidentiality]').value;
    var i = document.querySelector('[name=integrity]').value;
    var a = document.querySelector('[name=availability]').value;
    var changed = (c !== origC || i !== origI || a !== origA);
    document.getElementById('reason-box').classList.toggle('hidden', !changed);
    document.getElementById('reason').required = changed;
}
checkSchutzbedarf();
</script>
</body>
</html>
