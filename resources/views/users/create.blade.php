<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create New User') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6" x-data="{ showPassword: false, generatePassword: true }">
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf

                        <!-- Name -->
                        <div class="mb-4">
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <x-input-label for="password" :value="__('Password')" />
                            <div class="relative">
                                <input id="password" 
                                       class="block mt-1 w-full pr-10 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" 
                                       x-bind:type="showPassword ? 'text' : 'password'" 
                                       name="password" />
                                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5">
                                    <span x-show="!showPassword">👁️</span>
                                    <span x-show="showPassword" x-cloak>🙈</span>
                                </button>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Leer lassen für automatische Generierung') }}</p>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <!-- Roles -->
                        <div class="mb-4">
                            <x-input-label :value="__('Roles')" />
                            <div class="mt-2 space-y-1">
                                @foreach ($roles as $role)
                                    <label class="inline-flex items-center mr-4">
                                        <input type="checkbox"
                                               name="roles[]"
                                               value="{{ $role->name }}"
                                               {{ in_array($role->name, old('roles', [])) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-700">{{ $role->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Rollen bestimmen den Modulzugriff des Benutzers') }}</p>
                            <x-input-error :messages="$errors->get('roles')" class="mt-2" />
                        </div>

                        <!-- Module Access Preview -->
                        <div class="mb-4" x-data="{ 
                            selectedRoles: {{ json_encode(old('roles', [])) }},
                            rolePermissions: {{ json_encode($roles->mapWithKeys(fn($role) => [$role->name => $role->permissions->pluck('name')->toArray()])->toArray()) }},
                            get moduleAccess() {
                                let permissions = new Set();
                                this.selectedRoles.forEach(roleName => {
                                    if (this.rolePermissions[roleName]) {
                                        this.rolePermissions[roleName].forEach(perm => permissions.add(perm));
                                    }
                                });
                                
                                let modules = new Set();
                                permissions.forEach(perm => {
                                    let parts = perm.split('.');
                                    if (parts.length >= 2) {
                                        modules.add(parts[0]);
                                    }
                                });
                                
                                return Array.from(modules);
                            }
                        }"
                        @change="selectedRoles = Array.from(document.querySelectorAll('input[name=\'roles[]\']:checked')).map(el => el.value)">
                            <x-input-label :value="__('Modulzugriff (basierend auf Rollen)')" />
                            <div class="mt-2 p-3 bg-gray-50 rounded-md border border-gray-200">
                                <template x-if="moduleAccess.length === 0">
                                    <p class="text-sm text-gray-500">{{ __('Keine Module ausgewählt') }}</p>
                                </template>
                                <template x-if="moduleAccess.length > 0">
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="module in moduleAccess" :key="module">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800" x-text="module"></span>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Gruppen -->
                        @if(isset($gruppen) && $gruppen->isNotEmpty())
                        <div class="mb-4">
                            <x-input-label value="Gruppen" />
                            <p class="text-xs text-gray-500 mb-2">Gruppen-Rollen werden automatisch an den Benutzer vererbt.</p>
                            <div class="mt-2 space-y-1 max-h-40 overflow-y-auto border border-gray-200 rounded-md p-3">
                                @foreach ($gruppen as $gruppe)
                                    <label class="flex items-center mr-4 mb-1">
                                        <input type="checkbox" name="gruppe_ids[]" value="{{ $gruppe->id }}"
                                               {{ in_array($gruppe->id, old('gruppe_ids', [])) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-700">{{ $gruppe->name }}</span>
                                    </label>
                                    @foreach($gruppe->children as $child)
                                    <label class="flex items-center mr-4 mb-1 ml-4">
                                        <input type="checkbox" name="gruppe_ids[]" value="{{ $child->id }}"
                                               {{ in_array($child->id, old('gruppe_ids', [])) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-600">└─ {{ $child->name }}</span>
                                    </label>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- Send Credentials -->
                        <div class="mb-4">
                            <label for="send_credentials" class="inline-flex items-center">
                                <input id="send_credentials" type="checkbox" name="send_credentials" value="1" {{ old('send_credentials') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-600">{{ __('Zugangsdaten per E-Mail senden') }}</span>
                            </label>
                            <x-input-error :messages="$errors->get('send_credentials')" class="mt-2" />
                        </div>

                        <!-- Is Active -->
                        <div class="mb-4">
                            <label for="is_active" class="inline-flex items-center">
                                <input id="is_active" type="checkbox" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-600">{{ __('Active') }}</span>
                            </label>
                            <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('users.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button>
                                {{ __('Create User') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
