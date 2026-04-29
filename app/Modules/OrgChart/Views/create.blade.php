<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('orgchart.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Neue Organigramm-Version</h2>
        </div>
    </x-slot>

    <div class="py-8 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <form action="{{ route('orgchart.store') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <x-input-label for="name" value="Name der Version *" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                  value="{{ old('name') }}" placeholder="z. B. Ist-Stand 2026" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="description" value="Beschreibung" />
                    <textarea id="description" name="description" rows="2"
                              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="status" value="Status" />
                        <select id="status" name="status"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            @foreach($statusOptions as $val => $label)
                                <option value="{{ $val }}" @selected(old('status', 'entwurf') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="color_scheme" value="Farbschema" />
                        <select id="color_scheme" name="color_scheme"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            @foreach($colorSchemeOptions as $val => $label)
                                <option value="{{ $val }}" @selected(old('color_scheme', 'klassisch') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <x-input-label for="notes" value="Planungsnotizen" />
                    <textarea id="notes" name="notes" rows="3"
                              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                              placeholder="Hintergründe, Entscheidungshistorie, offene Fragen…">{{ old('notes') }}</textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                    <a href="{{ route('orgchart.index') }}"
                       class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-md text-gray-700">Abbrechen</a>
                    <button type="submit"
                            class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-semibold">
                        Anlegen & Struktur bearbeiten
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
