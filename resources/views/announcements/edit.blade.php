<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Announcement') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('announcements.update', $announcement) }}" x-data="{ type: '{{ old('type', $announcement->type) }}' }">
                        @csrf
                        @method('PUT')

                        <!-- Type -->
                        <div class="mb-4">
                            <x-input-label for="type" :value="__('Type')" />
                            <select id="type" name="type" x-model="type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">Select a type</option>
                                <option value="info" {{ old('type', $announcement->type) === 'info' ? 'selected' : '' }}>Info</option>
                                <option value="maintenance" {{ old('type', $announcement->type) === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                <option value="critical" {{ old('type', $announcement->type) === 'critical' ? 'selected' : '' }}>Critical</option>
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                            
                            <!-- Type Preview -->
                            <div class="mt-2">
                                <div x-show="type === 'info'" class="text-sm text-blue-600">
                                    ℹ️ Info announcements are displayed with blue styling
                                </div>
                                <div x-show="type === 'maintenance'" class="text-sm text-yellow-600">
                                    🔧 Maintenance announcements are displayed with yellow styling
                                </div>
                                <div x-show="type === 'critical'" class="text-sm text-red-600">
                                    ⚠️ Critical announcements are displayed with red styling and can be marked as fixed
                                </div>
                            </div>
                        </div>

                        <!-- Message -->
                        <div class="mb-4">
                            <x-input-label for="message" :value="__('Message')" />
                            <textarea id="message" name="message" rows="4" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>{{ old('message', $announcement->message) }}</textarea>
                            <x-input-error :messages="$errors->get('message')" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">Enter the announcement message that will be displayed to users.</p>
                        </div>

                        <!-- Starts At -->
                        <div class="mb-4">
                            <x-input-label for="starts_at" :value="__('Starts At (Optional)')" />
                            <x-text-input id="starts_at" class="block mt-1 w-full" type="datetime-local" name="starts_at" :value="old('starts_at', $announcement->starts_at ? $announcement->starts_at->format('Y-m-d\TH:i') : '')" />
                            <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">Leave blank to display immediately.</p>
                        </div>

                        <!-- Ends At -->
                        <div class="mb-4">
                            <x-input-label for="ends_at" :value="__('Ends At (Optional)')" />
                            <x-text-input id="ends_at" class="block mt-1 w-full" type="datetime-local" name="ends_at" :value="old('ends_at', $announcement->ends_at ? $announcement->ends_at->format('Y-m-d\TH:i') : '')" />
                            <x-input-error :messages="$errors->get('ends_at')" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">Leave blank for indefinite display. Must be after start date.</p>
                        </div>

                        <!-- Fixed Status Display -->
                        @if($announcement->is_fixed)
                            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                                <p class="text-sm text-green-800">
                                    ✅ This announcement was marked as fixed on {{ $announcement->fixed_at->format('Y-m-d H:i') }}
                                </p>
                            </div>
                        @endif

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('announcements.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2">
                                Cancel
                            </a>
                            <x-primary-button>
                                {{ __('Update Announcement') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
