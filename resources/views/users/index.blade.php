<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('User Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Create Button -->
            <div class="mb-4">
                <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    {{ __('Create New User') }}
                </a>
            </div>
            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)" 
                     class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ __(session('success')) }}
                </div>
            @endif

            @if (session('error'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)" 
                     class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ __(session('error')) }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('users.index') }}" class="mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700">{{ __('Role') }}</label>
                                <select name="role" id="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">{{ __('All Roles') }}</option>
                                    <option value="super-admin" {{ request('role') === 'super-admin' ? 'selected' : '' }}>{{ __('Super Admin') }}</option>
                                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>{{ __('Admin') }}</option>
                                    <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>{{ __('User') }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="active" class="block text-sm font-medium text-gray-700">{{ __('Status') }}</label>
                                <select name="active" id="active" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">{{ __('All Status') }}</option>
                                    <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                    <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                    {{ __('Filter') }}
                                </button>
                                <a href="{{ route('users.index') }}" class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                    {{ __('Clear') }}
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Users Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Name') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Email') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Role') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Last Login') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($users as $user)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $user->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $user->email }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                @if($user->role === 'super-admin') bg-purple-100 text-purple-800
                                                @elseif($user->role === 'admin') bg-blue-100 text-blue-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                                {{ ucwords(str_replace('-', ' ', $user->role)) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $user->is_active ? __('Active') : __('Inactive') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : __('Never') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" x-data="{ showDeleteConfirm: false }">
                                            <a href="{{ route('users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">{{ __('Edit') }}</a>

                                            @if (!session('impersonating_original_id') && $user->id !== auth()->id() && (auth()->user()->isSuperAdmin() || auth()->user()->can('base.users.edit')) && !$user->isSuperAdmin())
                                            <form action="{{ route('users.impersonate', $user) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-purple-600 hover:text-purple-900 mr-3" title="Als diesen Benutzer anmelden">
                                                    Anmelden als
                                                </button>
                                            </form>
                                            @endif

                                            <form action="{{ route('users.toggle-active', $user) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900 mr-3">
                                                    {{ $user->is_active ? __('Deactivate') : __('Activate') }}
                                                </button>
                                            </form>

                                            <button @click="showDeleteConfirm = true" type="button" class="text-red-600 hover:text-red-900">
                                                {{ __('Delete') }}
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
                                                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Confirm Delete') }}</h3>
                                                        <p class="text-sm text-gray-500 mb-4">
                                                            {{ __('Are you sure you want to delete this user? This action cannot be undone.') }}
                                                        </p>
                                                        <div class="flex justify-end space-x-3">
                                                            <button @click="showDeleteConfirm = false" 
                                                                    type="button" 
                                                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                                                {{ __('Cancel') }}
                                                            </button>
                                                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                                                    {{ __('Delete') }}
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">{{ __('No users found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
