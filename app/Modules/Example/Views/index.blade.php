<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Example Module') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Welcome to the Example Module</h3>
                    
                    <p class="text-gray-700 mb-4">
                        This is a demonstration module that shows how the IT Cockpit module system works.
                    </p>

                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    This module demonstrates the following features:
                                </p>
                                <ul class="mt-2 text-sm text-blue-700 list-disc list-inside">
                                    <li>Automatic module discovery and registration</li>
                                    <li>Sidebar navigation integration</li>
                                    <li>Dashboard widget display</li>
                                    <li>Permission-based access control</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-gray-900 mb-2">Module Information</h4>
                            <dl class="space-y-2 text-sm">
                                <div>
                                    <dt class="font-medium text-gray-700">Name:</dt>
                                    <dd class="text-gray-600">Example Module</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-700">Version:</dt>
                                    <dd class="text-gray-600">1.0.0</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-700">Status:</dt>
                                    <dd class="text-green-600">Active</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-gray-900 mb-2">Your Permissions</h4>
                            <ul class="space-y-2 text-sm">
                                <li class="flex items-center text-gray-600">
                                    <svg class="h-4 w-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    View Example Module
                                </li>
                                @if(auth()->user()->hasModulePermission('example', 'edit'))
                                <li class="flex items-center text-gray-600">
                                    <svg class="h-4 w-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Edit Example Module
                                </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
