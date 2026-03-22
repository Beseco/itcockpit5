<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Module Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Name') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Display Name') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Description') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($modules as $module)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="font-mono text-sm">{{ $module->name }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $module->display_name }}</td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 max-w-md">{{ $module->description }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $module->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $module->is_active ? __('Active') : __('Inactive') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('modules.edit', $module) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">{{ __('Edit') }}</a>
                                            
                                            @if ($module->is_active)
                                                @if ($module->name !== 'base')
                                                    <form action="{{ route('modules.deactivate', $module) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                                            {{ __('Deactivate') }}
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-gray-400">{{ __('Cannot deactivate') }}</span>
                                                @endif
                                            @else
                                                <form action="{{ route('modules.activate', $module) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-green-600 hover:text-green-900">
                                                        {{ __('Activate') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">{{ __('No modules found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
