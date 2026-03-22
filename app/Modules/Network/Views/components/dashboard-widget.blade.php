@php
    use App\Modules\Network\Models\IpAddress;
    use App\Modules\Network\Models\Vlan;
    
    // Calculate online device count across all VLANs
    $onlineCount = IpAddress::where('is_online', true)->count();
    
    // Calculate total monitored IP count
    $totalIpCount = IpAddress::count();
    
    // Check if any VLANs are configured
    $hasVlans = Vlan::count() > 0;
@endphp

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Network Status</h3>
            <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
            </svg>
        </div>
        
        @if($hasVlans)
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-sm text-gray-700">Online Devices</span>
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                        {{ $onlineCount }}
                    </span>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-sm text-gray-700">Monitored IPs</span>
                    <span class="text-sm font-medium text-gray-900">{{ $totalIpCount }}</span>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('network.index') }}" 
                   class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded transition-colors">
                    View Networks
                </a>
            </div>
        @else
            <p class="text-gray-600 text-sm mb-4">
                No network data
            </p>

            <div class="mt-4">
                @if(auth()->user()->role === 'super-admin' || auth()->user()->can('network.edit'))
                    <a href="{{ route('network.vlans.create') }}" 
                       class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded transition-colors">
                        Create VLAN
                    </a>
                @else
                    <a href="{{ route('network.index') }}" 
                       class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded transition-colors">
                        View Networks
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
