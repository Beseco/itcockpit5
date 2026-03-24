<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Aufgabe bearbeiten: {{ $aufgabe->name }}</h2>
            <a href="{{ route('aufgaben.show', $aufgabe) }}"
               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                Ansicht
            </a>
        </div>
    </x-slot>
    <div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg">
            <form action="{{ route('aufgaben.update', $aufgabe) }}" method="POST">
                @csrf @method('PUT')
                @include('aufgaben._form')
            </form>
        </div>
    </div>
</x-app-layout>
