<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Module') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('modules.update', $module) }}">
                        @csrf
                        @method('PATCH')

                        <!-- Module Name (Read-only) -->
                        <div class="mb-4">
                            <x-input-label for="name" :value="__('Module Name (Technical)')" />
                            <x-text-input id="name" class="block mt-1 w-full bg-gray-100" type="text" name="name" :value="$module->name" disabled />
                            <p class="mt-1 text-sm text-gray-500">{{ __('The technical module name cannot be changed.') }}</p>
                        </div>

                        <!-- Display Name -->
                        <div class="mb-4">
                            <x-input-label for="display_name" :value="__('Display Name')" />
                            <x-text-input id="display_name" class="block mt-1 w-full" type="text" name="display_name" :value="old('display_name', $module->display_name)" required autofocus />
                            <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" rows="4" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $module->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <!-- Status (Read-only) -->
                        <div class="mb-4">
                            <x-input-label for="status" :value="__('Status')" />
                            <div class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $module->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $module->is_active ? __('Active') : __('Inactive') }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Use the Activate/Deactivate buttons on the module list to change the status.') }}</p>
                        </div>

                        <div class="flex items-center justify-between mt-6">
                            <a href="{{ route('modules.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Cancel') }}
                            </a>

                            <x-primary-button>
                                {{ __('Update Module') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
