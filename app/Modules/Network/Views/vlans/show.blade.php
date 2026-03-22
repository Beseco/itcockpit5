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
                                    @if($vlan->dhcp_from && $vlan->dhcp_to)
                                        {{ $vlan->dhcp_from }} - {{ $vlan->dhcp_to }}
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
