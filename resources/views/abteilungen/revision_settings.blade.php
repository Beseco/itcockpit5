<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Abteilungen – Revisions-Einstellungen</h2>
            <a href="{{ route('abteilungen.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Zur Übersicht</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('abteilungen.revision-settings.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Erinnerungs-E-Mail --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-1">Erinnerungs-E-Mail an Vorgesetzte</h3>
                    <p class="text-xs text-gray-400 mb-5">
                        Wenn aktiviert, erhalten Vorgesetzte (und Stellvertreter) automatisch eine Erinnerungs-E-Mail,
                        wenn das Revisionsdatum ihrer Abteilung überschritten und die Revision noch nicht abgeschlossen ist.
                    </p>

                    {{-- Aktivieren --}}
                    <div class="flex items-center gap-3 mb-5">
                        <input type="checkbox" id="enabled" name="enabled" value="1"
                               @checked(old('enabled', $settings->enabled))
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="enabled" class="text-sm font-medium text-gray-700">Erinnerungs-E-Mail aktivieren</label>
                    </div>

                    {{-- Intervall --}}
                    <div class="mb-5">
                        <x-input-label value="Versandintervall *" />
                        <div class="mt-2 flex flex-wrap gap-4">
                            @foreach([1 => 'Jede Woche (7 Tage)', 2 => 'Alle 2 Wochen (14 Tage)', 4 => 'Alle 4 Wochen'] as $value => $label)
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="radio" name="interval_weeks" value="{{ $value }}"
                                           @checked(old('interval_weeks', $settings->interval_weeks) == $value)
                                           class="text-indigo-600 focus:ring-indigo-500">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('interval_weeks')" class="mt-1" />
                    </div>

                    {{-- Wochentag und Stunde --}}
                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <x-input-label for="weekday" value="Wochentag *" />
                            <select id="weekday" name="weekday"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @foreach([1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('weekday', $settings->weekday) == $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('weekday')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="hour" value="Uhrzeit *" />
                            <select id="hour" name="hour"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @for($h = 7; $h <= 19; $h++)
                                    <option value="{{ $h }}" @selected(old('hour', $settings->hour) == $h)>
                                        {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00 Uhr
                                    </option>
                                @endfor
                            </select>
                            <x-input-error :messages="$errors->get('hour')" class="mt-1" />
                        </div>
                    </div>

                    @if($settings->last_sent_at)
                        <p class="mt-4 text-xs text-gray-400">Zuletzt versendet: {{ $settings->last_sent_at->format('d.m.Y H:i') }} Uhr</p>
                    @else
                        <p class="mt-4 text-xs text-gray-400">Noch nie versendet</p>
                    @endif
                </div>

                {{-- Software-Vorschläge --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-1">Neue-Software-Vorschläge</h3>
                    <p class="text-xs text-gray-400 mb-4">
                        Wenn Abteilungsleiter neue (noch nicht erfasste) Software vorschlagen, wird eine E-Mail an diese Adresse gesendet.
                    </p>

                    <div>
                        <x-input-label for="new_app_email" value="Empfänger-E-Mail *" />
                        <x-text-input id="new_app_email" name="new_app_email" type="email"
                                      class="mt-1 block w-full"
                                      :value="old('new_app_email', $settings->new_app_email)"
                                      placeholder="informatiotechnik@kreis-fs.de"
                                      required />
                        <x-input-error :messages="$errors->get('new_app_email')" class="mt-1" />
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-primary-button type="submit">Einstellungen speichern</x-primary-button>
                </div>
            </form>

            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-600">
                <strong>Hinweis:</strong> Die Erinnerungs-E-Mail wird an den Vorgesetzten und (falls vorhanden) den
                Stellvertreter der Abteilung gesendet, sofern eine E-Mail-Adresse im AD hinterlegt ist.
                Abteilungen ohne Vorgesetzten oder ohne überfälliges Revisionsdatum werden übersprungen.
            </div>

        </div>
    </div>
</x-app-layout>
