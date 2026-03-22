<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Announcement Details') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('announcements.edit', $announcement) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Edit
                </a>
                <a href="{{ route('announcements.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    Back to List
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Announcement Type Badge -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                            @if($announcement->type === 'critical') bg-red-100 text-red-800
                            @elseif($announcement->type === 'maintenance') bg-yellow-100 text-yellow-800
                            @else bg-blue-100 text-blue-800
                            @endif">
                            {{ ucfirst($announcement->type) }}
                        </span>
                    </div>

                    <!-- Message -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                        <div class="p-4 bg-gray-50 rounded-md border border-gray-200">
                            <p class="text-gray-900 whitespace-pre-wrap">{{ $announcement->message }}</p>
                        </div>
                    </div>

                    <!-- Time Window -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Starts At</label>
                            <p class="text-gray-900">
                                {{ $announcement->starts_at ? $announcement->starts_at->format('F j, Y g:i A') : 'Immediately' }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ends At</label>
                            <p class="text-gray-900">
                                {{ $announcement->ends_at ? $announcement->ends_at->format('F j, Y g:i A') : 'No end date' }}
                            </p>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        @if($announcement->is_fixed)
                            <div class="flex items-center">
                                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Fixed
                                </span>
                                <span class="ml-3 text-sm text-gray-600">
                                    Marked as fixed on {{ $announcement->fixed_at->format('F j, Y g:i A') }}
                                </span>
                            </div>
                        @else
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                Active
                            </span>
                        @endif
                    </div>

                    <!-- Timestamps -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 pt-6 border-t border-gray-200">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Created</label>
                            <p class="text-gray-600 text-sm">{{ $announcement->created_at->format('F j, Y g:i A') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last Updated</label>
                            <p class="text-gray-600 text-sm">{{ $announcement->updated_at->format('F j, Y g:i A') }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                        <div>
                            @if($announcement->type === 'critical' && !$announcement->is_fixed)
                                <form action="{{ route('announcements.mark-as-fixed', $announcement) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                        Mark as Fixed
                                    </button>
                                </form>
                            @endif
                        </div>
                        <div x-data="{ showDeleteConfirm: false }">
                            <button @click="showDeleteConfirm = true" type="button" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                Delete Announcement
                            </button>
                            
                            <!-- Delete Confirmation Modal -->
                            <div x-show="showDeleteConfirm" 
                                 x-cloak
                                 @click.away="showDeleteConfirm = false"
                                 class="fixed inset-0 z-50 overflow-y-auto" 
                                 style="display: none;">
                                <div class="flex items-center justify-center min-h-screen px-4">
                                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                                    
                                    <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                                        <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Delete</h3>
                                        <p class="text-sm text-gray-500 mb-4">
                                            Are you sure you want to delete this announcement? This action cannot be undone.
                                        </p>
                                        <div class="flex justify-end space-x-3">
                                            <button @click="showDeleteConfirm = false" 
                                                    type="button" 
                                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                                Cancel
                                            </button>
                                            <form action="{{ route('announcements.destroy', $announcement) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
