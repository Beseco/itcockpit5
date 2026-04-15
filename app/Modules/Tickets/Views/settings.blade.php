<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Tickets – Einstellungen</h2>
            <a href="{{ route('tickets.index') }}"
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

        {{-- Einstellungen Formular --}}
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">Zammad-Verbindung</h3>
                <p class="text-xs text-gray-500 mt-0.5">URL und API-Token für die Zammad REST-API.</p>
            </div>
            <form action="{{ route('tickets.settings.update') }}" method="POST" class="p-6 space-y-5">
                @csrf

                <div>
                    <label for="url" class="block text-sm font-medium text-gray-700 mb-1">Zammad URL</label>
                    <input type="url" name="url" id="url"
                           value="{{ old('url', $settings->url) }}"
                           placeholder="https://zammad.example.com"
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                           required>
                    @error('url')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div x-data="{ showToken: false }">
                    <label for="api_token" class="block text-sm font-medium text-gray-700 mb-1">API Token</label>
                    <div class="relative">
                        <input :type="showToken ? 'text' : 'password'" name="api_token" id="api_token"
                               placeholder="{{ $settings->api_token ? '••••••••••••••••' : 'API-Token eingeben' }}"
                               class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500 pr-10">
                        <button type="button" @click="showToken = !showToken"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                            <svg x-show="!showToken" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showToken" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        Leer lassen, um den bestehenden Token beizubehalten.
                    </p>
                    @error('api_token')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="enabled" id="enabled" value="1"
                           {{ old('enabled', $settings->enabled) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <label for="enabled" class="text-sm text-gray-700">Zammad-Anbindung aktivieren</label>
                </div>

                <div class="flex items-center justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                        Speichern
                    </button>
                </div>
            </form>
        </div>

        {{-- Scoring & E-Mail-Benachrichtigung --}}
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">Scoring & E-Mail-Benachrichtigung</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    Jeden Freitag um 12:00 Uhr wird ein Score je Benutzer berechnet. Gelbe Tickets (+0,5) und rote Tickets (+1,0) fließen ein.
                </p>
            </div>
            <form action="{{ route('tickets.settings.update') }}" method="POST" class="p-6 space-y-5">
                @csrf

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="email_enabled" id="email_enabled" value="1"
                           {{ old('email_enabled', $settings->email_enabled) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <label for="email_enabled" class="text-sm text-gray-700">E-Mail-Benachrichtigung aktivieren</label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="email_threshold" class="block text-sm font-medium text-gray-700 mb-1">
                            Mindest-Score für E-Mail
                        </label>
                        <input type="number" name="email_threshold" id="email_threshold" step="0.5" min="0"
                               value="{{ old('email_threshold', $settings->email_threshold ?? 3.0) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">E-Mail nur versenden, wenn Score ≥ diesem Wert</p>
                    </div>
                    <div>
                        <label for="score_green_max" class="block text-sm font-medium text-gray-700 mb-1">
                            Grenze Grün → Gelb
                        </label>
                        <input type="number" name="score_green_max" id="score_green_max" step="0.5" min="0"
                               value="{{ old('score_green_max', $settings->score_green_max ?? 3.0) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">Score ≤ diesem Wert → grün</p>
                    </div>
                    <div>
                        <label for="score_red_min" class="block text-sm font-medium text-gray-700 mb-1">
                            Grenze Gelb → Rot
                        </label>
                        <input type="number" name="score_red_min" id="score_red_min" step="0.5" min="0"
                               value="{{ old('score_red_min', $settings->score_red_min ?? 6.0) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">Score ≥ diesem Wert → rot</p>
                    </div>
                </div>

                <div class="flex items-center justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                        Speichern
                    </button>
                </div>
            </form>
        </div>

        {{-- Verbindungstest --}}
        <div class="bg-white shadow rounded-lg overflow-hidden"
             x-data="{
                 testing: false,
                 result: null,
                 async test() {
                     this.testing = true;
                     this.result = null;
                     try {
                         const res = await fetch('{{ route('tickets.settings.test-connection') }}', {
                             method: 'POST',
                             headers: {
                                 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                 'Accept': 'application/json',
                             },
                         });
                         this.result = await res.json();
                     } catch (e) {
                         this.result = { success: false, message: 'Netzwerkfehler: ' + e.message };
                     }
                     this.testing = false;
                 }
             }">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">Verbindungstest</h3>
                <p class="text-xs text-gray-500 mt-0.5">Prüft die Verbindung mit den gespeicherten Einstellungen.</p>
            </div>
            <div class="p-6">
                <button @click="test()" :disabled="testing"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50">
                    <svg x-show="testing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="testing ? 'Teste...' : 'Verbindung testen'"></span>
                </button>

                <div x-show="result" x-cloak class="mt-4">
                    <div x-show="result?.success"
                         class="p-3 bg-green-50 border border-green-200 rounded-md text-sm text-green-700">
                        <span x-text="result?.message"></span>
                    </div>
                    <div x-show="!result?.success"
                         class="p-3 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">
                        <span x-text="result?.message"></span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
