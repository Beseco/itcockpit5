<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Sicherheitswarnungen – Einstellungen</h2>
            <a href="{{ route('wid.index') }}"
               class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Zurück
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                 class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-md text-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">WID-Portal API-Verbindung</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    API-Schlüssel und URL für das WID-Portal des LSI Bayern.
                    Täglicher automatischer Abruf erfolgt um 06:00 Uhr.
                </p>
            </div>
            <form action="{{ route('wid.settings.update') }}" method="POST" class="p-6 space-y-5">
                @csrf
                @method('PUT')

                {{-- API-URL --}}
                <div>
                    <label for="api_url" class="block text-sm font-medium text-gray-700 mb-1">API-URL</label>
                    <input type="url" name="api_url" id="api_url"
                           value="{{ old('api_url', $settings->api_url) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                           required>
                    <p class="text-xs text-gray-400 mt-1">
                        Behördennetz: <code class="font-mono">https://wid.lsi.bybn.de/content</code> |
                        Internet: <code class="font-mono">https://wid.lsi.bayern.de/content</code>
                    </p>
                    @error('api_url')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- API-Key --}}
                <div x-data="{ showKey: false }">
                    <label for="api_key" class="block text-sm font-medium text-gray-700 mb-1">API-Schlüssel</label>
                    <div class="relative">
                        <input :type="showKey ? 'text' : 'password'" name="api_key" id="api_key"
                               placeholder="{{ $settings->api_key ? '••••••••••••••••' : 'API-Schlüssel eingeben' }}"
                               class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500 pr-10">
                        <button type="button" @click="showKey = !showKey"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                            <svg x-show="!showKey" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showKey" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Leer lassen, um den bestehenden Schlüssel beizubehalten.</p>
                    @error('api_key')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Aktiviert --}}
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="enabled" id="enabled" value="1"
                           {{ old('enabled', $settings->enabled) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <label for="enabled" class="text-sm text-gray-700">WID-Abruf aktivieren</label>
                </div>

                {{-- Max Items --}}
                <div>
                    <label for="max_items" class="block text-sm font-medium text-gray-700 mb-1">
                        Maximale Anzahl Warnungen
                    </label>
                    <input type="number" name="max_items" id="max_items" min="1" max="200"
                           value="{{ old('max_items', $settings->max_items) }}"
                           class="w-32 rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">Wie viele Einträge pro Abruf geladen werden (max. 200).</p>
                    @error('max_items')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Mindest-Klassifizierung --}}
                <div>
                    <label for="min_classification" class="block text-sm font-medium text-gray-700 mb-1">
                        Mindest-Klassifizierung für Anzeige
                    </label>
                    <select name="min_classification" id="min_classification"
                            class="rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @foreach(\App\Modules\Wid\Models\WidAdvisory::CLASSIFICATIONS as $cls)
                            <option value="{{ $cls }}" {{ old('min_classification', $settings->min_classification) === $cls ? 'selected' : '' }}>
                                {{ ucfirst($cls) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">
                        Warnungen unterhalb dieser Klassifizierung werden nicht angezeigt.
                    </p>
                    @error('min_classification')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end pt-2 gap-3">
                    <form action="{{ route('wid.fetch-now') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-50 text-indigo-700 border border-indigo-200 text-sm font-medium rounded-md hover:bg-indigo-100">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Jetzt abrufen
                        </button>
                    </form>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                        Speichern
                    </button>
                </div>
            </form>
        </div>

        {{-- Info --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-xs text-blue-800 space-y-1">
            <p class="font-medium">Hinweise zur WID-API:</p>
            <ul class="list-disc list-inside space-y-0.5 text-blue-700">
                <li>Der API-Schlüssel beginnt mit <code class="font-mono">lsiwid_</code></li>
                <li>Im Behördennetz: <code class="font-mono">https://wid.lsi.bybn.de/content</code></li>
                <li>Der automatische Abruf erfolgt täglich um 06:00 Uhr</li>
                <li>Klassifizierungen: keine → niedrig → mittel → hoch → kritisch</li>
            </ul>
        </div>

    </div>
</x-app-layout>
