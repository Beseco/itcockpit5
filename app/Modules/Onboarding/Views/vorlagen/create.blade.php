<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('onboarding.vorlagen.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800">Neue Vorlage erstellen</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('onboarding.vorlagen.store') }}">
                @csrf

                @include('onboarding::vorlagen._form')

                <div class="mt-6 flex items-center justify-end gap-4">
                    <a href="{{ route('onboarding.vorlagen.index') }}"
                       class="text-sm text-gray-600 hover:text-gray-800">Abbrechen</a>
                    <x-primary-button type="submit">Vorlage speichern</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
