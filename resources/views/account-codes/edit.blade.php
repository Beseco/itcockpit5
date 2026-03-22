<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sachkonto bearbeiten: {{ $accountCode->code }}</h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('account-codes.update', $accountCode) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="code" value="Code *" />
                            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full"
                                          value="{{ old('code', $accountCode->code) }}" required />
                            <x-input-error :messages="$errors->get('code')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="description" value="Bezeichnung" />
                            <x-text-input id="description" name="description" type="text" class="mt-1 block w-full"
                                          value="{{ old('description', $accountCode->description) }}" />
                        </div>
                    </div>
                    <div class="flex gap-4 mt-6">
                        <x-primary-button>Speichern</x-primary-button>
                        <a href="{{ route('account-codes.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition">Abbrechen</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
