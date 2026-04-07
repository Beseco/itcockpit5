<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Network/VLAN Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Top: Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Left Column (2/3): Details + Kommentare + IP-Status -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Details Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="px-6 py-4 bg-gray-100 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-700">{{ __('Details') }}</h3>
                        </div>
                        <div class="p-6 space-y-2">
                            @php
                                $firstIp = $vlan->ipAddresses()->orderByRaw('INET_ATON(ip_address)')->first();
                                $lastIp  = $vlan->ipAddresses()->orderByRaw('INET_ATON(ip_address) DESC')->first();
                                $totalIps = $vlan->ipAddresses()->count();
                            @endphp
                            <div>
                                <span class="text-sm font-medium text-gray-700">VLAN-ID:</span>
                                <span class="text-sm text-gray-900 ml-2">{{ str_pad($vlan->vlan_id, 3, '0', STR_PAD_LEFT) }}</span>
                                @php
                                    $statusBadge = match($vlan->status ?? 'produktiv') {
                                        'geplant'   => 'bg-blue-100 text-blue-700',
                                        'produktiv' => 'bg-green-100 text-green-700',
                                        'eol'       => 'bg-yellow-100 text-yellow-700',
                                        'geloescht' => 'bg-red-100 text-red-700',
                                        default     => 'bg-gray-100 text-gray-700',
                                    };
                                    $statusLabel = match($vlan->status ?? 'produktiv') {
                                        'geplant'   => 'Geplant',
                                        'produktiv' => 'Produktiv',
                                        'eol'       => 'EOL',
                                        'geloescht' => 'Gelöscht',
                                        default     => $vlan->status,
                                    };
                                @endphp
                                <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded {{ $statusBadge }}">{{ $statusLabel }}</span>
                                <span class="text-xs text-gray-500 ml-1">({{ number_format($totalIps, 0, ',', '.') }} IPs)</span>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-700">Name:</span>
                                <span class="text-sm text-gray-900 ml-2">{{ $vlan->vlan_name }}</span>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-700">Netz:</span>
                                <span class="text-sm text-gray-900 ml-2">{{ $vlan->network_address }}/{{ $vlan->cidr_suffix }}</span>
                                @if($firstIp && $lastIp)
                                    <span class="text-xs text-gray-500 ml-1">({{ $firstIp->ip_address }} - {{ $lastIp->ip_address }})</span>
                                @endif
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-700">Gateway:</span>
                                <span class="text-sm text-gray-900 ml-2">{{ $vlan->gateway ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-700">DHCP:</span>
                                <span class="text-sm text-gray-900 ml-2">
                                    @if($vlan->dhcp_enabled)
                                        @php
                                            $dhcpColorMap = ['blue'=>'text-blue-600','red'=>'text-red-600','green'=>'text-green-600','yellow'=>'text-yellow-600','purple'=>'text-purple-600'];
                                            $dhcpSvgPaths = [
                                                'server'   => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01',
                                                'firewall' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                                                'router'   => 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0',
                                                'switch'   => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                                                'cloud'    => 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z',
                                            ];
                                            $srv = $vlan->dhcpServer;
                                        @endphp
                                        <span class="inline-flex items-center gap-1.5">
                                            @if($srv)
                                                <svg class="w-4 h-4 {{ $dhcpColorMap[$srv->color] ?? 'text-blue-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $dhcpSvgPaths[$srv->symbol] ?? $dhcpSvgPaths['server'] }}"/>
                                                </svg>
                                                <span class="font-medium">{{ $srv->name }}</span>
                                            @endif
                                            @if($vlan->dhcp_from && $vlan->dhcp_to)
                                                <span class="text-gray-500">({{ $vlan->dhcp_from }} – {{ $vlan->dhcp_to }})</span>
                                            @endif
                                        </span>
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>
                            @if($vlan->description)
                            <div>
                                <span class="text-sm font-medium text-gray-700">Beschreibung:</span>
                                <span class="text-sm text-gray-900 ml-2">{{ $vlan->description }}</span>
                            </div>
                            @endif
                            <div>
                                <span class="text-sm font-medium text-gray-700">Intern:</span>
                                <span class="text-sm text-gray-900 ml-2">{{ $vlan->internes_netz ? 'Ja' : 'Nein' }}</span>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-700">IP-Scan:</span>
                                <span class="text-sm text-gray-900 ml-2">{{ $vlan->ipscan ? 'Ja' : 'Nein' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Kommentar-Verlauf -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="px-6 py-4 bg-gray-100 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-700">{{ __('Kommentar-Verlauf') }}</h3>
                        </div>
                        <div class="p-6">
                            <form action="{{ route('network.vlans.comments.store', $vlan) }}" method="POST" class="mb-4">
                                @csrf
                                <textarea name="comment" rows="3"
                                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                          placeholder="{{ __('Neuen Kommentar schreiben...') }}"
                                          required></textarea>
                                @error('comment')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <div class="mt-2">
                                    <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                        {{ __('Kommentar speichern') }}
                                    </button>
                                </div>
                            </form>
                            <div class="space-y-3">
                                @forelse ($vlan->comments->sortByDesc('created_at') as $comment)
                                    <div class="bg-gray-50 p-4 rounded border border-gray-200">
                                        <div class="flex justify-between items-start mb-1">
                                            <span class="text-xs font-semibold text-gray-700">{{ $comment->user->name }}</span>
                                            <span class="text-xs text-gray-500">{{ $comment->created_at->format('Y-m-d H:i:s') }}</span>
                                        </div>
                                        <p class="text-sm text-gray-900">{{ $comment->comment }}</p>
                                        @if($comment->user_id === auth()->id() || auth()->user()->isSuperAdmin())
                                            <form action="{{ route('network.comments.destroy', $comment) }}" method="POST" class="mt-2">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-red-600 hover:text-red-900">
                                                    {{ __('Löschen') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">{{ __('Keine Kommentare vorhanden.') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column (1/3): IP-Status + Aktionen + Änderungshistorie -->
                <div class="lg:col-span-1 space-y-6">

                    <!-- IP-Status -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="px-6 py-4 bg-gray-100 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-700">{{ __('IP-Status') }}</h3>
                        </div>
                        <div class="p-6">
                            @php
                                $onlineCount = $vlan->ipAddresses()->where('is_online', true)->count();
                                $totalCount  = $vlan->ipAddresses()->count();
                                $percentage  = $totalCount > 0 ? round(($onlineCount / $totalCount) * 100, 1) : 0;
                            @endphp
                            <div class="mb-4">
                                <div class="w-full bg-gray-200 rounded-full h-6 overflow-hidden">
                                    <div class="bg-green-500 h-6 flex items-center justify-center text-xs font-semibold text-white"
                                         style="width: {{ $percentage }}%">
                                        @if($percentage > 10){{ $percentage }}%@endif
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <span class="text-sm font-medium text-gray-700">Online:</span>
                                <span class="text-sm text-gray-900 ml-2">{{ $onlineCount }} / {{ $totalCount }}</span>
                            </div>
                            <a href="{{ route('network.vlans.ips', $vlan) }}"
                               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                {{ __('IP-Adressen anzeigen') }}
                            </a>
                        </div>
                    </div>

                    <!-- Aktionen -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="px-6 py-4 bg-gray-100 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-700">{{ __('Aktionen') }}</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            @if(auth()->user()->hasModulePermission('network', 'edit'))
                                <a href="{{ route('network.vlans.edit', $vlan) }}"
                                   class="block w-full text-center px-4 py-2 bg-blue-600 border border-blue-600 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                    {{ __('Bearbeiten') }}
                                </a>
                            @endif
                            <a href="{{ route('network.index') }}"
                               class="block w-full text-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                {{ __('Zurück') }}
                            </a>
                        </div>
                    </div>

                    <!-- Änderungshistorie -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="px-6 py-4 bg-gray-100 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-700">{{ __('Änderungshistorie') }}</h3>
                        </div>
                        <div class="p-6">
                            @php
                                $auditLogs = \App\Models\AuditLog::where('module', 'network')
                                    ->where('payload->vlan_id', $vlan->id)
                                    ->orderBy('created_at', 'desc')
                                    ->limit(5)
                                    ->get();
                            @endphp
                            <div class="space-y-3">
                                @forelse($auditLogs as $log)
                                    <div class="bg-gray-50 p-3 rounded border border-gray-200">
                                        <div class="text-xs font-semibold text-gray-700 mb-1">
                                            {{ $log->user->name ?? 'System' }}
                                            <span class="text-gray-500 font-normal ml-1">{{ $log->created_at->format('Y-m-d H:i:s') }}</span>
                                        </div>
                                        <p class="text-sm text-gray-900">{{ $log->action }}</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">{{ __('Keine Änderungen vorhanden.') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>
</x-app-layout>
