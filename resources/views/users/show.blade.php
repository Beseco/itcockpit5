<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('User Details') }}
            </h2>
            <a href="{{ route('users.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Name</h3>
                            <p class="text-gray-900">{{ $user->name }}</p>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Email</h3>
                            <p class="text-gray-900">{{ $user->email }}</p>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Role</h3>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($user->role === 'super-admin') bg-purple-100 text-purple-800
                                @elseif($user->role === 'admin') bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucwords(str_replace('-', ' ', $user->role)) }}
                            </span>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Status</h3>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Last Login</h3>
                            <p class="text-gray-900">{{ $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : 'Never' }}</p>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Created At</h3>
                            <p class="text-gray-900">{{ $user->created_at->format('Y-m-d H:i:s') }}</p>
                        </div>
                    </div>

                    <div class="mt-6 flex space-x-2">
                        <a href="{{ route('users.edit', $user) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Edit User
                        </a>
                        
                        <form action="{{ route('users.toggle-active', $user) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                        
                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline" 
                              onsubmit="return confirm('Are you sure you want to delete this user?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                Delete User
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
