<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Audit Log Details') }}
            </h2>
            <a href="{{ route('audit-logs.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="space-y-6">
                        <!-- Basic Information -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Log ID</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $auditLog->id }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Timestamp</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ $auditLog->created_at->format('Y-m-d H:i:s') }}
                                        <span class="text-gray-500 text-xs">({{ $auditLog->created_at->diffForHumans() }})</span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">User</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        @if($auditLog->user)
                                            {{ $auditLog->user->name }}
                                            <span class="text-gray-500">({{ $auditLog->user->email }})</span>
                                        @else
                                            <span class="text-gray-400">System</span>
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Module</dt>
                                    <dd class="mt-1">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ $auditLog->module }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-sm font-medium text-gray-500">Action</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $auditLog->action }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Payload Details -->
                        @if($auditLog->payload && count($auditLog->payload) > 0)
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Payload Details</h3>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <pre class="text-sm text-gray-800 whitespace-pre-wrap overflow-x-auto">{{ json_encode($auditLog->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                            </div>
                        @else
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Payload Details</h3>
                                <p class="text-sm text-gray-500">No additional payload data available.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
