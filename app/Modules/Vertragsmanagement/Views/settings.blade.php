<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('vertragsmanagement.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800">Vertragsmanagement – Einstellungen</h2>
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

            <form method="POST" action="{{ route('vertragsmanagement.settings.update') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-5">
                @csrf @method('PUT')

                <div>
                    <x-input-label for="fallback_email" value="Fallback-Benachrichtigungs-E-Mail" />
                    <x-text-input id="fallback_email" name="fallback_email" type="email" class="mt-1 block w-full"
                                  value="{{ old('fallback_email', $settings->fallback_email) }}"
                                  placeholder="it-vertraege@example.de" />
                    <p class="mt-1 text-xs text-gray-400">
                        Wird für Erinnerungen genutzt, wenn beim Vertrag keine eigene E-Mail hinterlegt ist.
                        Ist auch diese leer, greift die globale Absenderadresse aus der Mail-Konfiguration.
                    </p>
                    <x-input-error :messages="$errors->get('fallback_email')" class="mt-1" />
                </div>

                <div class="flex justify-end">
                    <x-primary-button type="submit">Einstellungen speichern</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
