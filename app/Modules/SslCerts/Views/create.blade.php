<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">SSL-Zertifikat importieren</h2>
            <a href="{{ route('sslcerts.index') }}"
               class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Zurück
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">P12-Zertifikat hochladen</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    Das Zertifikat wird in PEM und Private Key konvertiert. Transport-PIN und P12-Datei werden nicht gespeichert.
                </p>
            </div>

            <form action="{{ route('sslcerts.store') }}" method="POST" enctype="multipart/form-data"
                  class="p-6 space-y-5" x-data="{ loading: false }" @submit="loading = true">
                @csrf

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Bezeichnung <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name"
                           value="{{ old('name') }}"
                           placeholder="z.B. Webserver example.com"
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                           required>
                    @error('name')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- P12-Datei --}}
                <div>
                    <label for="p12_file" class="block text-sm font-medium text-gray-700 mb-1">
                        P12-Zertifikat (.p12 / .pfx) <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="p12_file" id="p12_file"
                           accept=".p12,.pfx"
                           class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                           required>
                    @error('p12_file')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Transport-PIN --}}
                <div x-data="{ showPin: false }">
                    <label for="p12_pin" class="block text-sm font-medium text-gray-700 mb-1">
                        Transport-PIN <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showPin ? 'text' : 'password'" name="p12_pin" id="p12_pin"
                               placeholder="Transport-PIN eingeben"
                               class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500 pr-10"
                               required>
                        <button type="button" @click="showPin = !showPin"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                            <svg x-show="!showPin" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPin" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        <svg class="w-3.5 h-3.5 inline-block mr-0.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Der Transport-PIN wird nur zum Entschlüsseln verwendet und nicht gespeichert.
                    </p>
                    @error('p12_pin')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                @if($errors->any() && !$errors->has('name') && !$errors->has('p12_file') && !$errors->has('p12_pin'))
                    <div class="p-3 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="flex items-center justify-end pt-2">
                    <button type="submit" :disabled="loading"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50">
                        <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="loading ? 'Wird importiert...' : 'Importieren'"></span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
