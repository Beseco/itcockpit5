<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Entsorgung eintragen</h2>
            <a href="{{ route('entsorgung.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700">← Zurück zur Liste</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('entsorgung.store') }}">
                    @csrf
                    @include('entsorgung::_form')
                    <div class="mt-6 flex items-center gap-3">
                        <x-primary-button type="submit">Eintragen</x-primary-button>
                        <a href="{{ route('entsorgung.index') }}"
                           class="text-sm text-gray-500 hover:text-gray-700">Abbrechen</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
