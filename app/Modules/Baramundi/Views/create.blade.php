<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('baramundi.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800">Baramundi – Neues Paket</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('baramundi.packages.store') }}" class="space-y-5"
                      x-data="{ downloadType: 'none' }">
                    @csrf

                    @include('baramundi::_form')

                    <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                        <a href="{{ route('baramundi.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                            Abbrechen
                        </a>
                        <x-primary-button type="submit">Paket anlegen</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
