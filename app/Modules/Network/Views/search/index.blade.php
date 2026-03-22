<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Search Results') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Search Bar (pre-filled) -->
            @include('network::components.search-bar')

            @if($validationMessage)
                <!-- Validation Message -->
                <div class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                    {{ $validationMessage }}
                </div>
            @elseif($query === '')
                <!-- Empty Query - Show message -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-500">
                        {{ __('Enter a search term to find VLANs and IP addresses.') }}
                    </div>
                </div>
            @else
                <!-- Results Summary -->
                <div class="mb-4 text-gray-700">
                    @if($vlans->isEmpty() && $ipAddresses->isEmpty())
                        <p class="text-lg">{{ __('No results found for') }} <strong>"{{ $query }}"</strong></p>
                    @else
                        <p class="text-lg">
                            {{ __('Found') }} 
                            @if($vlans->isNotEmpty())
                                <strong>{{ $vlans->count() }}</strong> {{ Str::plural('VLAN', $vlans->count()) }}
                            @endif
                            @if($vlans->isNotEmpty() && $ipAddresses->isNotEmpty())
                                {{ __('and') }}
                            @endif
                            @if($ipAddresses->isNotEmpty())
                                <strong>{{ $ipAddresses->count() }}</strong> {{ Str::plural('IP address', $ipAddresses->count()) }}
                            @endif
                            {{ __('for') }} <strong>"{{ $query }}"</strong>
                        </p>
                    @endif
                </div>

                @if($vlans->isEmpty() && $ipAddresses->isEmpty())
                    <!-- No Results Message -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No results found') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ __('Try adjusting your search terms or searching for something else.') }}
                            </p>
                        </div>
                    </div>
                @else
                    <!-- VLAN Results Section -->
                    @if($vlans->isNotEmpty())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    {{ __('VLANs') }}
                                    @if($vlans->count() >= 50)
                                        <span class="ml-2 text-sm font-normal text-yellow-600">
                                            ({{ __('More results available - showing first 50') }})
                                        </span>
                                    @endif
                                </h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('VLAN ID') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Name') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Network') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Online') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Total IPs') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($vlans as $vlan)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <a href="{{ route('network.vlans.show', $vlan) }}" class="text-indigo-600 hover:text-indigo-900">
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                                {!! str_ireplace($query, '<mark class="bg-yellow-200">' . $query . '</mark>', $vlan->vlan_id) !!}
                                                            </span>
                                                        </a>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <a href="{{ route('network.vlans.show', $vlan) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                                            {!! str_ireplace($query, '<mark class="bg-yellow-200">' . $query . '</mark>', e($vlan->vlan_name)) !!}
                                                        </a>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        {!! str_ireplace($query, '<mark class="bg-yellow-200">' . $query . '</mark>', e($vlan->network_address)) !!}/{{ $vlan->cidr_suffix }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="text-green-600 font-semibold">{{ $vlan->online_count }}</span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        {{ $vlan->total_ip_count }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- IP Address Results Section -->
                    @if($ipAddresses->isNotEmpty())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    {{ __('IP Addresses') }}
                                    @if($ipAddresses->count() >= 50)
                                        <span class="ml-2 text-sm font-normal text-yellow-600">
                                            ({{ __('More results available - showing first 50') }})
                                        </span>
                                    @endif
                                </h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('IP Address') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('DNS Name') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('MAC Address') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('VLAN') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($ipAddresses as $ip)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <a href="{{ route('network.ip-addresses.show', $ip) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                                            {!! str_ireplace($query, '<mark class="bg-yellow-200">' . $query . '</mark>', e($ip->ip_address)) !!}
                                                        </a>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        @if($ip->dns_name)
                                                            {!! str_ireplace($query, '<mark class="bg-yellow-200">' . $query . '</mark>', e($ip->dns_name)) !!}
                                                        @else
                                                            <span class="text-gray-400">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap font-mono text-sm">
                                                        @if($ip->getFormattedMacAddress())
                                                            @php
                                                                $normalizedQuery = str_replace([':', '-'], '', $query);
                                                                $normalizedMac = str_replace([':', '-'], '', $ip->getFormattedMacAddress());
                                                                $highlighted = stripos($normalizedMac, $normalizedQuery) !== false;
                                                            @endphp
                                                            @if($highlighted)
                                                                <mark class="bg-yellow-200">{{ $ip->getFormattedMacAddress() }}</mark>
                                                            @else
                                                                {{ $ip->getFormattedMacAddress() }}
                                                            @endif
                                                        @else
                                                            <span class="text-gray-400">{{ __('Not resolved') }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        @if($ip->is_online)
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                                {{ __('Online') }}
                                                            </span>
                                                        @else
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                                {{ __('Offline') }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <a href="{{ route('network.vlans.show', $ip->vlan) }}" class="text-indigo-600 hover:text-indigo-900">
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                                {{ $ip->vlan->vlan_id }}
                                                            </span>
                                                            <span class="ml-1">{{ $ip->vlan->vlan_name }}</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
