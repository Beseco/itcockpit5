<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">SSL-Zertifikate · Einstellungen</h2>
            <a href="{{ route('sslcerts.index') }}"
               class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Zurück
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- E-Mail-Benachrichtigungen --}}
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">Ablauf-Benachrichtigungen</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    Automatische E-Mails bei ablaufenden Zertifikaten. Der Versand erfolgt täglich um 08:00 Uhr.
                </p>
            </div>

            <form action="{{ route('sslcerts.settings.update') }}" method="POST" class="p-6 space-y-5">
                @csrf

                {{-- Aktivieren --}}
                <div class="flex items-center justify-between py-2">
                    <div>
                        <div class="text-sm font-medium text-gray-700">Benachrichtigungen aktivieren</div>
                        <div class="text-xs text-gray-500 mt-0.5">E-Mails werden nur versendet wenn aktiviert und eine Adresse hinterlegt ist</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="notifications_enabled" value="1" class="sr-only peer"
                               {{ $settings->notifications_enabled ? 'checked' : '' }}>
                        <div class="w-10 h-5 bg-gray-200 peer-focus:ring-2 peer-focus:ring-indigo-300 rounded-full peer
                                    peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-0.5 after:left-0.5
                                    after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all
                                    peer-checked:after:translate-x-5"></div>
                    </label>
                </div>

                {{-- E-Mail-Adresse --}}
                <div>
                    <label for="notification_email" class="block text-sm font-medium text-gray-700 mb-1">
                        Benachrichtigungs-E-Mail
                    </label>
                    <input type="email" name="notification_email" id="notification_email"
                           value="{{ old('notification_email', $settings->notification_email) }}"
                           placeholder="z.B. admin@example.com"
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('notification_email')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-400 mt-1">
                        Zusätzlich wird immer auch der hinterlegte Verantwortliche des jeweiligen Zertifikats benachrichtigt.
                    </p>
                </div>

                {{-- Versandzeitpunkte (info) --}}
                <div class="rounded-lg bg-gray-50 border border-gray-200 p-4 space-y-2">
                    <div class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Versandzeitpunkte</div>
                    <div class="flex items-start gap-3 text-sm">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700 shrink-0 mt-0.5">30 Tage</span>
                        <span class="text-gray-600">Erste Warnung – einmalig wenn noch 30 Tage oder weniger verbleiben</span>
                    </div>
                    <div class="flex items-start gap-3 text-sm">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700 shrink-0 mt-0.5">14 Tage</span>
                        <span class="text-gray-600">Zweite Warnung – einmalig wenn noch 14 Tage oder weniger verbleiben</span>
                    </div>
                    <div class="flex items-start gap-3 text-sm">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 shrink-0 mt-0.5">7 Tage</span>
                        <span class="text-gray-600">Tägliche Warnung – täglich ab 7 Tagen oder weniger bis zum Ablauf</span>
                    </div>
                </div>

                <div class="flex items-center justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                        Speichern
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
