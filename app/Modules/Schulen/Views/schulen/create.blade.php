<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('schulen.index') }}" class="text-gray-400 hover:text-gray-600">← Schulen</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Neue Schule anlegen</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('schulen.store') }}" method="POST">
                    @csrf
                    @include('schulen::schulen._form')
                    <div class="mt-6 flex justify-end gap-3">
                        <a href="{{ route('schulen.index') }}"
                           class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Abbrechen
                        </a>
                        <x-primary-button>Schule anlegen</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
