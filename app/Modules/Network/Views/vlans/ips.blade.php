<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('network.vlans.show', $vlan) }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                IP-Adressen &mdash; {{ str_pad($vlan->vlan_id, 3, '0', STR_PAD_LEFT) }} {{ $vlan->vlan_name }}
                <span class="text-sm font-normal text-gray-500 ml-2">{{ $vlan->network_address }}/{{ $vlan->cidr_suffix }}</span>
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    <!-- Live Search -->
                    <div class="mb-4">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"></path>
                            </svg>
                            <input type="text" id="live-search"
                                   placeholder="Suche nach IP, DNS, MAC, Kommentar..."
                                   value="{{ $search }}"
                                   class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   autocomplete="off" />
                            <span id="search-spinner" class="absolute right-3 top-1/2 -translate-y-1/2 hidden">
                                <svg class="animate-spin w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                </svg>
                            </span>
                        </div>
                        <div class="mt-1 text-xs text-gray-400" id="result-count"></div>
                    </div>

                    <!-- Filter Panel -->
                    <div x-data="{ open: {{ count(request()->only(['status', 'dhcp', 'has_dns', 'has_comment'])) > 0 ? 'true' : 'false' }} }" class="mb-4" id="filter-panel">
                        <button @click="open = !open" type="button"
                                class="flex items-center justify-between w-full px-4 py-2 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors">
                            <span class="font-medium text-gray-700 text-sm">
                                Filter
                                @if(count(request()->only(['status', 'dhcp', 'has_dns', 'has_comment'])) > 0)
                                    <span class="ml-2 px-2 py-0.5 text-xs bg-indigo-600 text-white rounded-full">{{ count(request()->only(['status', 'dhcp', 'has_dns', 'has_comment'])) }}</span>
                                @endif
                            </span>
                            <svg :class="{ 'rotate-180': open }" class="w-4 h-4 transition-transform text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="open" x-transition class="mt-2 p-4 bg-white border border-gray-200 rounded-md shadow-sm">
                            <form method="GET" class="flex flex-wrap gap-4 items-end">
                                @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                                @if(request('direction'))<input type="hidden" name="direction" value="{{ request('direction') }}">@endif
                                @if($search)<input type="hidden" name="q" value="{{ $search }}">@endif
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                                    <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        <option value="">Alle</option>
                                        <option value="online"  {{ request('status') === 'online'  ? 'selected' : '' }}>Online</option>
                                        <option value="offline" {{ request('status') === 'offline' ? 'selected' : '' }}>Offline</option>
                                    </select>
                                </div>
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" name="dhcp" value="1" {{ request('dhcp') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    In DHCP Range
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" name="has_dns" value="1" {{ request('has_dns') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    Hat DNS-Name
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" name="has_comment" value="1" {{ request('has_comment') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    Hat Kommentar
                                </label>
                                <div class="flex gap-2">
                                    <button type="submit" class="px-3 py-1.5 bg-gray-800 text-white text-xs rounded-md hover:bg-gray-700">Anwenden</button>
                                    <a href="{{ route('network.vlans.ips', $vlan) }}" class="px-3 py-1.5 bg-gray-200 text-gray-700 text-xs rounded-md hover:bg-gray-300">Zurücksetzen</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    @php
                                        $sortLink = fn($col) => route('network.vlans.ips', array_merge(
                                            ['vlan' => $vlan->id],
                                            request()->only(['q','status','dhcp','has_dns','has_comment']),
                                            ['sort' => $col, 'direction' => $sortColumn === $col && $sortDirection === 'asc' ? 'desc' : 'asc']
                                        ));
                                        $sortIcon = fn($col) => $sortColumn === $col
                                            ? ($sortDirection === 'asc' ? '▲' : '▼')
                                            : '';
                                    @endphp
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="{{ $sortLink('ip_address') }}" class="hover:text-gray-700">IP-Adresse {!! $sortIcon('ip_address') !!}</a>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="{{ $sortLink('dns_name') }}" class="hover:text-gray-700">DNS Name {!! $sortIcon('dns_name') !!}</a>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">MAC</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="{{ $sortLink('is_online') }}" class="hover:text-gray-700">Status {!! $sortIcon('is_online') !!}</a>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="{{ $sortLink('last_scanned_at') }}" class="hover:text-gray-700">Letzter Scan {!! $sortIcon('last_scanned_at') !!}</a>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="{{ $sortLink('ping_ms') }}" class="hover:text-gray-700">Ping {!! $sortIcon('ping_ms') !!}</a>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kommentar</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="ip-tbody">
                                @forelse($ipAddresses as $ip)
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap font-mono text-sm">
                                            <a href="{{ route('network.ip-addresses.show', $ip) }}" class="text-indigo-600 hover:text-indigo-900">{{ $ip->ip_address }}</a>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">{{ $ip->dns_name ?? '-' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap font-mono text-xs text-gray-500">{{ $ip->mac_address ?? '-' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            @if($ip->last_scanned_at === null)
                                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600">Nicht gescannt</span>
                                            @elseif($ip->is_online)
                                                <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800">Online</span>
                                            @else
                                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-300 text-gray-700">Offline</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-500">{{ $ip->last_scanned_at ? $ip->last_scanned_at->diffForHumans() : '-' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-500">{{ $ip->is_online && $ip->ping_ms ? number_format($ip->ping_ms, 1).' ms' : '-' }}</td>
                                        <td class="px-4 py-2 text-xs text-gray-500">{{ $ip->comment ? Str::limit($ip->comment, 40) : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">Keine IP-Adressen gefunden.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($ipAddresses->hasPages())
                        <div class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4" id="pagination-wrap">
                            <div class="text-sm text-gray-500">
                                {{ $ipAddresses->firstItem() }} – {{ $ipAddresses->lastItem() }} von {{ $ipAddresses->total() }}
                            </div>
                            <div class="flex gap-2">
                                @if(!$ipAddresses->onFirstPage())
                                    <a href="{{ $ipAddresses->previousPageUrl() }}" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50">Zurück</a>
                                @endif
                                @if($ipAddresses->hasMorePages())
                                    <a href="{{ $ipAddresses->nextPageUrl() }}" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50">Weiter</a>
                                @endif
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

<script>
const searchUrl = '{{ route('network.vlans.ips.search', $vlan) }}';
const detailBase = '{{ url('network/ip-addresses') }}/';
let debounceTimer = null;

const input      = document.getElementById('live-search');
const tbody      = document.getElementById('ip-tbody');
const spinner    = document.getElementById('search-spinner');
const countEl    = document.getElementById('result-count');
const pagination = document.getElementById('pagination-wrap');
const filterPanel = document.getElementById('filter-panel');

function timeAgo(dateStr) {
    if (!dateStr) return '-';
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)   return 'gerade eben';
    if (diff < 3600) return Math.floor(diff/60) + ' Min. ago';
    if (diff < 86400) return Math.floor(diff/3600) + ' Std. ago';
    return Math.floor(diff/86400) + ' Tage ago';
}

function statusBadge(ip) {
    if (!ip.last_scanned_at) return '<span class="px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600">Nicht gescannt</span>';
    if (ip.is_online)        return '<span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800">Online</span>';
    return '<span class="px-2 py-0.5 text-xs rounded-full bg-gray-300 text-gray-700">Offline</span>';
}

function renderRows(data) {
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">Keine Ergebnisse.</td></tr>';
        countEl.textContent = '0 Ergebnisse';
        return;
    }
    tbody.innerHTML = data.map(ip => `
        <tr>
            <td class="px-4 py-2 whitespace-nowrap font-mono text-sm">
                <a href="${detailBase}${ip.id}" class="text-indigo-600 hover:text-indigo-900">${ip.ip_address}</a>
            </td>
            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">${ip.dns_name ?? '-'}</td>
            <td class="px-4 py-2 whitespace-nowrap font-mono text-xs text-gray-500">${ip.mac_address ?? '-'}</td>
            <td class="px-4 py-2 whitespace-nowrap">${statusBadge(ip)}</td>
            <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-500">${timeAgo(ip.last_scanned_at)}</td>
            <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-500">${ip.is_online && ip.ping_ms ? ip.ping_ms.toFixed(1)+' ms' : '-'}</td>
            <td class="px-4 py-2 text-xs text-gray-500">${ip.comment ? ip.comment.substring(0, 40) : '-'}</td>
        </tr>
    `).join('');
    countEl.textContent = data.length + (data.length === 200 ? '+ Ergebnisse (max. 200)' : ' Ergebnisse');
}

input.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    const q = this.value.trim();

    if (!q) {
        countEl.textContent = '';
        if (pagination) pagination.style.display = '';
        if (filterPanel) filterPanel.style.display = '';
        location.href = '{{ route('network.vlans.ips', $vlan) }}';
        return;
    }

    debounceTimer = setTimeout(() => {
        spinner.classList.remove('hidden');
        if (pagination)  pagination.style.display  = 'none';
        if (filterPanel) filterPanel.style.display = 'none';

        fetch(`${searchUrl}?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => { renderRows(data); spinner.classList.add('hidden'); })
            .catch(() => spinner.classList.add('hidden'));
    }, 250);
});
</script>

</x-app-layout>
