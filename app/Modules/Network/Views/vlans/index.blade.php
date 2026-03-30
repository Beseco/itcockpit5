<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Network / VLAN Management') }}
        </h2>
    </x-slot>

    <div x-data="exportModal()" class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Toolbar -->
            <div class="mb-4 flex items-center gap-3">
                @if(auth()->user()->hasModulePermission('network', 'edit'))
                <a href="{{ route('network.vlans.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    {{ __('Create New VLAN') }}
                </a>
                @endif

                <button @click="startExport()" class="inline-flex items-center px-4 py-2 bg-green-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    {{ __('Excel Export') }}
                </button>
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

            <!-- Search Bar -->
            @include('network::components.search-bar')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- VLANs Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    @php
                                        $sortColumn = request('sort', 'vlan_id');
                                        $sortDirection = request('direction', 'asc');
                                        
                                        // Helper function to generate sort URL
                                        $getSortUrl = function($column) use ($sortColumn, $sortDirection) {
                                            if ($column === $sortColumn) {
                                                // Toggle direction or reset to default
                                                if ($sortDirection === 'asc') {
                                                    return route('network.index', ['sort' => $column, 'direction' => 'desc']);
                                                } elseif ($sortDirection === 'desc') {
                                                    return route('network.index'); // Reset to default
                                                }
                                            }
                                            // First click: ascending
                                            return route('network.index', ['sort' => $column, 'direction' => 'asc']);
                                        };
                                        
                                        // Helper function to get sort indicator
                                        $getSortIndicator = function($column) use ($sortColumn, $sortDirection) {
                                            if ($column !== $sortColumn) {
                                                return '';
                                            }
                                            return $sortDirection === 'asc' 
                                                ? '<svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>'
                                                : '<svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>';
                                        };
                                    @endphp
                                    
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="{{ $getSortUrl('vlan_id') }}" class="flex items-center hover:text-gray-700">
                                            {{ __('VLAN ID') }}
                                            {!! $getSortIndicator('vlan_id') !!}
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="{{ $getSortUrl('vlan_name') }}" class="flex items-center hover:text-gray-700">
                                            {{ __('Name') }}
                                            {!! $getSortIndicator('vlan_name') !!}
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="{{ $getSortUrl('network_address') }}" class="flex items-center hover:text-gray-700">
                                            {{ __('Network') }}
                                            {!! $getSortIndicator('network_address') !!}
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Gateway') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="{{ $getSortUrl('online_count') }}" class="flex items-center hover:text-gray-700">
                                            {{ __('Online Count') }}
                                            {!! $getSortIndicator('online_count') !!}
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($vlans as $vlan)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                {{ str_pad($vlan->vlan_id, 3, '0', STR_PAD_LEFT) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <a href="{{ route('network.vlans.show', $vlan) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                                    {{ $vlan->vlan_name }}
                                                </a>
                                                @if($vlan->description)
                                                    <span class="text-xs text-gray-500 mt-1">
                                                        {{ Str::limit(Str::before($vlan->description . "\n", "\n"), 60) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="whitespace-nowrap">{{ $vlan->network_address }}/{{ $vlan->cidr_suffix }}</span>
                                                @php
                                                    $firstIp = $vlan->ipAddresses()->orderByRaw('INET_ATON(ip_address)')->first();
                                                    $lastIp = $vlan->ipAddresses()->orderByRaw('INET_ATON(ip_address) DESC')->first();
                                                @endphp
                                                @if($firstIp && $lastIp)
                                                    <span class="text-xs text-gray-500 mt-1 whitespace-nowrap">
                                                        ({{ $firstIp->ip_address }} - {{ $lastIp->ip_address }})
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $vlan->gateway ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-gray-900">
                                                {{ $vlan->ipAddresses()->where('is_online', true)->count() }} / {{ $vlan->ipAddresses()->count() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <!-- DHCP Status -->
                                                @if($vlan->dhcp_from && $vlan->dhcp_to)
                                                    <span class="inline-flex items-center" title="DHCP: {{ $vlan->dhcp_from }} - {{ $vlan->dhcp_to }}">
                                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                                                        </svg>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center" title="DHCP: Nicht konfiguriert">
                                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                                        </svg>
                                                    </span>
                                                @endif

                                                <!-- Internes Netz Status -->
                                                @if($vlan->internes_netz)
                                                    <span class="inline-flex items-center" title="Internes Netz: Ja">
                                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                        </svg>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center" title="Internes Netz: Nein">
                                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                                                        </svg>
                                                    </span>
                                                @endif

                                                <!-- IP Scan Status -->
                                                @if($vlan->ipscan)
                                                    <span class="inline-flex items-center" title="IP Scanning: Aktiviert">
                                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center" title="IP Scanning: Deaktiviert">
                                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center gap-2">
                                                <!-- View Button -->
                                                <a href="{{ route('network.vlans.show', $vlan) }}" 
                                                   class="inline-flex items-center justify-center w-8 h-8 text-indigo-600 hover:text-indigo-900 hover:bg-indigo-50 rounded transition-colors"
                                                   title="{{ __('View') }}">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </a>
                                                
                                                @if(auth()->user()->hasModulePermission('network', 'edit'))
                                                    <!-- Edit Button -->
                                                    <a href="{{ route('network.vlans.edit', $vlan) }}" 
                                                       class="inline-flex items-center justify-center w-8 h-8 text-yellow-600 hover:text-yellow-900 hover:bg-yellow-50 rounded transition-colors"
                                                       title="{{ __('Edit') }}">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                            {{ __('No VLANs configured. Create your first VLAN to get started.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Progress Modal (fixed, innerhalb des Alpine-Scopes) -->
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-gray-900 bg-opacity-60"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md mx-4 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Excel Export</h3>
                <p class="text-sm text-gray-600 mb-3" x-text="message"></p>
                <div class="w-full bg-gray-200 rounded-full h-4 mb-4 overflow-hidden">
                    <div class="h-4 rounded-full transition-all duration-300 ease-out"
                         :class="error ? 'bg-red-500' : (done ? 'bg-green-500' : 'bg-blue-600')"
                         :style="'width: ' + percent + '%'"></div>
                </div>
                <p class="text-xs text-gray-400 text-right mb-4" x-text="percent + '%'"></p>
                <div x-show="error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700" x-text="errorMessage"></div>
                <div class="flex justify-end gap-3">
                    <button x-show="done || error" @click="open = false"
                            class="px-4 py-2 text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition">
                        Schließen
                    </button>
                    <a x-show="done" :href="downloadUrl" @click="open = false"
                       class="px-4 py-2 text-sm bg-green-600 hover:bg-green-700 text-white rounded-md transition inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Herunterladen
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>



<script>
function exportModal() {
    return {
        open: false,
        percent: 0,
        message: '',
        done: false,
        error: false,
        errorMessage: '',
        downloadUrl: '',
        eventSource: null,

        startExport() {
            this.open = true;
            this.percent = 0;
            this.done = false;
            this.error = false;
            this.errorMessage = '';
            this.downloadUrl = '';
            this.message = 'Export wird gestartet...';

            if (this.eventSource) {
                this.eventSource.close();
            }

            this.eventSource = new EventSource('{{ route('network.export.stream') }}');

            this.eventSource.addEventListener('progress', (e) => {
                const data = JSON.parse(e.data);
                this.percent = data.percent;
                this.message = data.message;
            });

            this.eventSource.addEventListener('done', (e) => {
                const data = JSON.parse(e.data);
                this.percent = 100;
                this.message = 'Export abgeschlossen!';
                this.done = true;
                this.downloadUrl = '{{ route('network.export.download', '') }}/' + data.token;
                this.eventSource.close();

                // Automatisch herunterladen
                window.location.href = this.downloadUrl;
            });

            // Benanntes 'error'-Event vom Server (PHP-Exception)
            this.eventSource.addEventListener('error', (e) => {
                if (this.done) return;
                if (e.data) {
                    const data = JSON.parse(e.data);
                    this.errorMessage = data.message;
                } else {
                    this.errorMessage = 'Verbindungsfehler – bitte erneut versuchen.';
                }
                this.error = true;
                this.message = 'Fehler beim Export.';
                this.eventSource.close();
            });

            // Generischer Verbindungsabbruch (z.B. nach Server-Close)
            this.eventSource.onerror = () => {
                if (this.done) return;
                // Noch kein done empfangen – echter Fehler
                if (!this.error) {
                    this.errorMessage = 'Verbindung zum Server unterbrochen.';
                    this.error = true;
                    this.message = 'Fehler beim Export.';
                }
                this.eventSource.close();
            };
        }
    }
}
</script>
