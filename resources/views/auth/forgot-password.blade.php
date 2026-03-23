<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 text-center">Passwort zurücksetzen</h2>
        <p class="text-sm text-gray-600 text-center mt-1">E-Mail-Adresse eingeben um einen Reset-Link zu erhalten</p>
    </div>

    <div class="mb-4 text-sm text-gray-600 bg-blue-50 border border-blue-200 rounded-lg p-3">
        Passwort vergessen? Kein Problem. Gib deine E-Mail-Adresse ein und wir senden dir einen Link zum Zurücksetzen deines Passworts zu.
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="'E-Mail-Adresse'" />
            <x-text-input id="email" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full justify-center bg-indigo-600 hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900">
                Reset-Link senden
            </x-primary-button>
        </div>

        <div class="mt-4 text-center">
            <a class="text-sm text-indigo-600 hover:text-indigo-800 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                Zurück zur Anmeldung
            </a>
        </div>
    </form>
</x-guest-layout>
