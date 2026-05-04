<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Benutzerverwaltung – Mail-Einstellungen</h2>
            <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Zur Übersicht</a>
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
            @if(session('error'))
                <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-md text-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Inaktivitäts-Erinnerung --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">„Wir vermissen Sie"-Mail</h3>
                <p class="text-xs text-gray-400 mb-5">
                    Wenn aktiviert, erhalten Benutzer, die sich seit X Tagen nicht mehr angemeldet haben,
                    automatisch eine Erinnerungs-E-Mail. Benutzer, die sich noch nie angemeldet haben, werden
                    ausgeschlossen (dafür gibt es die Onboarding-Mail).
                </p>

                <form method="POST" action="{{ route('users.mail-settings.update') }}" class="space-y-5">
                    @csrf

                    <div class="flex items-center gap-3">
                        <input type="hidden" name="missing_mail_enabled" value="0">
                        <input type="checkbox" id="missing_mail_enabled" name="missing_mail_enabled" value="1"
                               @checked(old('missing_mail_enabled', $settings->missing_mail_enabled))
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="missing_mail_enabled" class="text-sm font-medium text-gray-700">
                            Inaktivitäts-Erinnerung aktivieren
                        </label>
                    </div>

                    <div>
                        <label for="missing_mail_days" class="block text-sm font-medium text-gray-700 mb-1">
                            Inaktivität ab (Tage) *
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number" id="missing_mail_days" name="missing_mail_days"
                                   value="{{ old('missing_mail_days', $settings->missing_mail_days) }}"
                                   min="1" max="365"
                                   class="w-28 border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <span class="text-sm text-gray-500">Tage ohne Aktivität → Mail wird versandt</span>
                        </div>
                        @error('missing_mail_days')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                            Einstellungen speichern
                        </button>
                    </div>
                </form>
            </div>

            {{-- Test-Mails --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">Test-Mails</h3>
                <p class="text-xs text-gray-400 mb-5">
                    Senden Sie sich eine Vorschau der jeweiligen Mail an <strong>{{ auth()->user()->email }}</strong>.
                </p>

                <div class="flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('users.test-mail', 'onboarding') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                            <svg class="w-4 h-4 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Onboarding-Mail testen
                        </button>
                    </form>

                    <form method="POST" action="{{ route('users.test-mail', 'missing') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                            <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            „Wir vermissen Sie"-Mail testen
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
