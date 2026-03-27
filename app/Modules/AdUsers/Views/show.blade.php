<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">
                AD-Benutzer: {{ $user->anzeigename_or_name }}
            </h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('adusers.offboarding.create', ['aduser' => $user->id]) }}"
                   class="inline-flex items-center px-3 py-1.5 bg-red-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-800">
                    Mitarbeiter Offboarding
                </a>
                <a href="{{ route('adusers.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700">← Zurück zur Liste</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Hauptinformationen --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Benutzerdaten</h3>
                    @php $badge = $user->status_badge; @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-sm font-medium {{ $badge['class'] }}">
                        {{ $badge['label'] }}
                    </span>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 text-sm">
                    @foreach([
                        'Vorname'            => $user->vorname,
                        'Nachname'           => $user->nachname,
                        'Anzeigename'        => $user->anzeigename,
                        'Konto (SAM)'        => $user->samaccountname,
                        'E-Mail'             => $user->email,
                        'Organisation'       => $user->organisation,
                        'Abteilung'          => $user->abteilung,
                        'Telefon'            => $user->telefon,
                        'Letzte Sync'        => $user->letzter_import_at?->format('d.m.Y H:i'),
                        'AD vorhanden'       => $user->ad_vorhanden ? 'Ja' : 'Nein',
                        'AD aktiv'           => $user->ad_aktiv ? 'Ja' : 'Nein',
                    ] as $label => $value)
                        @if($value)
                        <div>
                            <dt class="text-gray-500">{{ $label }}</dt>
                            <dd class="mt-0.5 font-medium text-gray-800">{{ $value }}</dd>
                        </div>
                        @endif
                    @endforeach
                </dl>

                @if($user->distinguished_name)
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <dt class="text-xs text-gray-400">Distinguished Name (AD-Pfad)</dt>
                    <dd class="mt-0.5 text-xs font-mono text-gray-600 break-all">{{ $user->distinguished_name }}</dd>
                </div>
                @endif
            </div>

            {{-- Alle AD-Attribute (raw_data) --}}
            @if($user->raw_data)
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Alle AD-Attribute</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs divide-y divide-gray-100">
                        <thead>
                            <tr>
                                <th class="py-2 pr-4 text-left font-medium text-gray-500 w-1/3">Attribut</th>
                                <th class="py-2 text-left font-medium text-gray-500">Wert</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($user->raw_data as $key => $val)
                            @if($key !== 'count')
                            <tr>
                                <td class="py-1.5 pr-4 font-mono text-gray-500 align-top">{{ $key }}</td>
                                <td class="py-1.5 text-gray-700 break-all align-top">
                                    @if(is_array($val))
                                        {{ implode(', ', array_filter($val, fn($v) => $v !== 'count' && !is_array($v))) }}
                                    @else
                                        {{ $val }}
                                    @endif
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
