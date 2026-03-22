<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Stelle bearbeiten: {{ $stelle->bezeichnung }}</h2>
    </x-slot>
    <div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg">
            <form action="{{ route('stellen.update', $stelle) }}" method="POST">
                @csrf @method('PUT')
                @include('stellen._form')
            </form>
        </div>
    </div>
</x-app-layout>
