<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('IP Address Details') }}: {{ $ipAddress->ip_address }}
            </h2>
            <a href="{{ route('network.vlans.show', $ipAddress->vlan) }}" class="text-indigo-600 hover:text-indigo-900">
                {{ __('Back to VLAN') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)" 
                     class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ __(session('success')) }}
                </div>
            @endif

            @if (session('error'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)" 
                     class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ __(session('error')) }}
                </div>
            @endif

            <!-- IP Information Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">{{ __('IP Address Information') }}</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- IP Address -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">{{ __('IP Address') }}</label>
                            <p class="mt-1 text-2xl font-mono font-bold text-gray-900">{{ $ipAddress->ip_address }}</p>
                            @if($ipAddress->isInDhcpRange())
                                <span class="mt-2 inline-block px-2 py-1 text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ __('DHCP') }}
                                </span>
                            @endif
                        </div>

                        <!-- DNS Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('DNS Name') }}</label>
                            @if(auth()->user()->hasModulePermission('network', 'edit'))
                                <div x-data="{ editing: false, value: '{{ $ipAddress->dns_name ?? '' }}' }">
                                    <div x-show="!editing">
                                        <p class="mt-1 text-sm text-gray-900">
                                            <span x-text="value || '-'"></span>
                                            <button @click="editing = true" class="ml-2 text-indigo-600 hover:text-indigo-900 text-xs">
                                                {{ __('Edit') }}
                                            </button>
                                        </p>
                                    </div>
                                    <div x-show="editing" x-cloak>
                                        <input type="text" x-model="value" 
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                               placeholder="{{ __('Enter DNS name') }}">
                                        <div class="mt-2 flex gap-2">
                                            <button @click="saveField('dns_name', value); editing = false" 
                                                    class="px-3 py-1 bg-gray-800 text-white rounded-md hover:bg-gray-700 text-xs">
                                                {{ __('Save') }}
                                            </button>
                                            <button @click="editing = false; value = '{{ $ipAddress->dns_name ?? '' }}'" 
                                                    class="px-3 py-1 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 text-xs">
                                                {{ __('Cancel') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <p class="mt-1 text-sm text-gray-900">{{ $ipAddress->dns_name ?? '-' }}</p>
                            @endif
                        </div>

                        <!-- MAC Address -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('MAC Address') }}</label>
                            <p class="mt-1 text-sm font-mono text-gray-900">
                                {{ $ipAddress->getFormattedMacAddress() ?? __('Not resolved') }}
                            </p>
                        </div>

                        <!-- Online Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Status') }}</label>
                            <p class="mt-1">
                                @if($ipAddress->last_scanned_at === null)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-700">
                                        {{ __('Not Scanned') }}
                                    </span>
                                @elseif($ipAddress->is_online)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        {{ __('Online') }}
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-300 text-gray-700">
                                        {{ __('Offline') }}
                                    </span>
                                @endif
                            </p>
                        </div>

                        <!-- Ping Response Time -->
                        @if($ipAddress->is_online && $ipAddress->ping_ms)
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Ping Response Time') }}</label>
                            <p class="mt-1 text-sm text-gray-900">{{ number_format($ipAddress->ping_ms, 2) }} ms</p>
                        </div>
                        @endif

                        <!-- Comment -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">{{ __('Comment') }}</label>
                            @if(auth()->user()->hasModulePermission('network', 'edit'))
                                <div x-data="{ editing: false, value: '{{ $ipAddress->comment ?? '' }}' }">
                                    <div x-show="!editing">
                                        <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">
                                            <span x-text="value || '-'"></span>
                                            <button @click="editing = true" class="ml-2 text-indigo-600 hover:text-indigo-900 text-xs">
                                                {{ __('Edit') }}
                                            </button>
                                        </p>
                                    </div>
                                    <div x-show="editing" x-cloak>
                                        <textarea x-model="value" rows="3"
                                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                                  placeholder="{{ __('Enter comment') }}"></textarea>
                                        <div class="mt-2 flex gap-2">
                                            <button @click="saveField('comment', value); editing = false" 
                                                    class="px-3 py-1 bg-gray-800 text-white rounded-md hover:bg-gray-700 text-xs">
                                                {{ __('Save') }}
                                            </button>
                                            <button @click="editing = false; value = '{{ $ipAddress->comment ?? '' }}'" 
                                                    class="px-3 py-1 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 text-xs">
                                                {{ __('Cancel') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $ipAddress->comment ?? '-' }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- VLAN Information Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">{{ __('VLAN Information') }}</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('VLAN ID') }}</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $ipAddress->vlan->vlan_id }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('VLAN Name') }}</label>
                            <p class="mt-1 text-sm">
                                <a href="{{ route('network.vlans.show', $ipAddress->vlan) }}" class="text-indigo-600 hover:text-indigo-900">
                                    {{ $ipAddress->vlan->vlan_name }}
                                </a>
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Network Address') }}</label>
                            <p class="mt-1 text-sm font-mono text-gray-900">
                                {{ $ipAddress->vlan->network_address }}/{{ $ipAddress->vlan->cidr_suffix }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Gateway') }}</label>
                            <p class="mt-1 text-sm font-mono text-gray-900">{{ $ipAddress->vlan->gateway ?? '-' }}</p>
                        </div>

                        @if($ipAddress->vlan->dhcp_from && $ipAddress->vlan->dhcp_to)
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">{{ __('DHCP Range') }}</label>
                            <p class="mt-1 text-sm font-mono text-gray-900">
                                {{ $ipAddress->vlan->dhcp_from }} - {{ $ipAddress->vlan->dhcp_to }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Scan History Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">{{ __('Scan History') }}</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Last Scanned') }}</label>
                            <p class="mt-1 text-sm text-gray-900">
                                @if($ipAddress->last_scanned_at)
                                    {{ $ipAddress->last_scanned_at->diffForHumans() }}
                                    <span class="text-xs text-gray-500">({{ $ipAddress->last_scanned_at->format('M d, Y H:i') }})</span>
                                @else
                                    {{ __('Never scanned') }}
                                @endif
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Last Online') }}</label>
                            <p class="mt-1 text-sm text-gray-900">
                                @if($ipAddress->last_online_at)
                                    {{ $ipAddress->last_online_at->diffForHumans() }}
                                    <span class="text-xs text-gray-500">({{ $ipAddress->last_online_at->format('M d, Y H:i') }})</span>
                                @else
                                    {{ __('Never online') }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center">
                        <div class="flex gap-2">
                            @if($previousIp)
                                <a href="{{ route('network.ip-addresses.show', $previousIp) }}" 
                                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                    {{ __('Previous IP') }}
                                </a>
                            @else
                                <button disabled 
                                        class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-500 uppercase tracking-widest cursor-not-allowed">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                    {{ __('Previous IP') }}
                                </button>
                            @endif

                            @if($nextIp)
                                <a href="{{ route('network.ip-addresses.show', $nextIp) }}" 
                                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                    {{ __('Next IP') }}
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            @else
                                <button disabled 
                                        class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-500 uppercase tracking-widest cursor-not-allowed">
                                    {{ __('Next IP') }}
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            @endif
                        </div>

                        <a href="{{ route('network.vlans.show', $ipAddress->vlan) }}" 
                           class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            {{ __('Back to VLAN') }}
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @if(auth()->user()->hasModulePermission('network', 'edit'))
    <script>
        function saveField(field, value) {
            // Show loading state
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'fixed top-4 right-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded z-50';
            loadingDiv.textContent = 'Saving...';
            document.body.appendChild(loadingDiv);

            // Prepare form data
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('_method', 'PUT');
            formData.append(field, value);

            // Send AJAX request
            fetch('{{ route('network.ip-addresses.update', $ipAddress) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Remove loading state
                document.body.removeChild(loadingDiv);

                if (data.success) {
                    // Show success message
                    const successDiv = document.createElement('div');
                    successDiv.className = 'fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50';
                    successDiv.textContent = data.message || 'Updated successfully';
                    document.body.appendChild(successDiv);
                    setTimeout(() => document.body.removeChild(successDiv), 3000);
                } else {
                    // Show error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50';
                    errorDiv.textContent = data.message || 'Update failed';
                    document.body.appendChild(errorDiv);
                    setTimeout(() => document.body.removeChild(errorDiv), 5000);
                }
            })
            .catch(error => {
                // Remove loading state
                document.body.removeChild(loadingDiv);

                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50';
                errorDiv.textContent = 'An error occurred. Please try again.';
                document.body.appendChild(errorDiv);
                setTimeout(() => document.body.removeChild(errorDiv), 5000);
            });
        }
    </script>
    @endif
</x-app-layout>
