<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Neuen Dienstleister anlegen
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('dienstleister.store') }}" method="POST">
                    @csrf
                    @include('dienstleister._form', ['dienstleister' => null])

                    <div class="flex items-center gap-4 mt-6 pt-6 border-t">
                        <x-primary-button>Dienstleister speichern</x-primary-button>
                        <a href="{{ route('dienstleister.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 transition ease-in-out duration-150">
                            Abbrechen
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
