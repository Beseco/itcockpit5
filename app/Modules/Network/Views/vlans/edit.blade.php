<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('VLAN bearbeiten') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('network.vlans.update', $vlan) }}">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Left Column (2/3): Form Fields -->
                    <div class="lg:col-span-2 space-y-6">

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="px-6 py-4 bg-gray-100 border-b border-gray-200">
                                <h3 class="font-semibold text-gray-700">{{ __('VLAN Daten') }}</h3>
                            </div>
                            <div class="p-6 space-y-4">

                                {{-- Hidden inputs für read-only Felder die trotzdem submitted werden müssen --}}
                                <input type="hidden" name="vlan_id" value="{{ $vlan->vlan_id }}">
                                <input type="hidden" name="network_address" value="{{ $vlan->network_address }}">
                                <input type="hidden" name="cidr_suffix" value="{{ $vlan->cidr_suffix }}">

                                <!-- VLAN ID (read-only) -->
                                <div>
                                    <x-input-label :value="__('VLAN ID')" />
                                    <div class="mt-1 flex items-center gap-2">
                                        <div class="flex-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-600 font-mono">
                                            {{ str_pad($vlan->vlan_id, 3, '0', STR_PAD_LEFT) }}
                                        </div>
                                        <span class="text-xs text-gray-400 italic">nicht änderbar</span>
                                    </div>
                                </div>

                                <!-- VLAN Name -->
                                <div>
                                    <x-input-label for="vlan_name" :value="__('VLAN Name')" />
                                    <x-text-input id="vlan_name" class="block mt-1 w-full" type="text" name="vlan_name" :value="old('vlan_name', $vlan->vlan_name)" required autofocus />
                                    <x-input-error :messages="$errors->get('vlan_name')" class="mt-2" />
                                </div>

                                <!-- Network Address + CIDR (read-only) -->
                                <div>
                                    <x-input-label :value="__('Netzwerkadresse / CIDR')" />
                                    <div class="mt-1 flex items-center gap-2">
                                        <div class="flex-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-600 font-mono">
                                            {{ $vlan->network_address }}
                                        </div>
                                        <div class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-600 font-mono">
                                            /{{ $vlan->cidr_suffix }}
                                        </div>
                                        <span class="text-xs text-gray-400 italic">nicht änderbar</span>
                                    </div>
                                </div>

                                <!-- Gateway -->
                                <div>
                                    <x-input-label for="gateway" :value="__('Gateway (optional)')" />
                                    <x-text-input id="gateway" class="block mt-1 w-full" type="text" name="gateway" :value="old('gateway', $vlan->gateway)" placeholder="z.B. 192.168.1.1" oninput="validateGateway()" />
                                    <p id="gateway-error" class="mt-1 text-sm text-red-600 hidden"></p>
                                    <x-input-error :messages="$errors->get('gateway')" class="mt-2" />
                                </div>

                                <!-- DHCP Bereich -->
                                <div x-data="dhcpSection()" class="space-y-3">
                                    {{-- Checkbox: DHCP Bereich anlegen --}}
                                    <input type="hidden" name="dhcp_enabled" value="0">
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" x-model="enabled" name="dhcp_enabled" value="1"
                                               {{ old('dhcp_enabled', $vlan->dhcp_enabled) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="text-sm font-medium text-gray-700">DHCP Bereich anlegen</span>
                                    </label>

                                    <div x-show="enabled" x-cloak class="border border-blue-100 bg-blue-50 rounded-lg p-4 space-y-3">

                                        {{-- DHCP-Server Verwaltung --}}
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-semibold text-blue-700 uppercase tracking-wide">DHCP-Server</span>
                                            <button type="button" @click="showManage = !showManage"
                                                    class="text-xs text-blue-600 hover:text-blue-800 underline">
                                                DHCP-Server verwalten
                                            </button>
                                        </div>

                                        {{-- Server-Verwaltung Panel --}}
                                        <div x-show="showManage" x-cloak class="bg-white border border-blue-200 rounded-lg p-3 space-y-2">
                                            <p class="text-xs font-semibold text-gray-600 mb-2">DHCP-Server anlegen</p>
                                            <div class="grid grid-cols-2 gap-2">
                                                <input type="text" x-model="newServer.name" placeholder="Name *"
                                                       class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <input type="text" x-model="newServer.description" placeholder="Beschreibung"
                                                       class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                            <div class="grid grid-cols-2 gap-2">
                                                {{-- Symbol --}}
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">Symbol</label>
                                                    <div class="flex gap-1.5 flex-wrap">
                                                        <template x-for="sym in symbols" :key="sym.key">
                                                            <button type="button" @click="newServer.symbol = sym.key"
                                                                    :class="newServer.symbol === sym.key ? 'ring-2 ring-indigo-500 bg-indigo-50' : 'bg-white'"
                                                                    class="p-1.5 border border-gray-200 rounded hover:bg-gray-50" :title="sym.label">
                                                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="sym.path"/>
                                                                </svg>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                                {{-- Farbe --}}
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">Farbe</label>
                                                    <div class="flex gap-1.5">
                                                        <template x-for="col in colors" :key="col.key">
                                                            <button type="button" @click="newServer.color = col.key"
                                                                    :class="[col.bg, newServer.color === col.key ? 'ring-2 ring-offset-1 ring-gray-600' : '']"
                                                                    class="w-7 h-7 rounded-full border border-gray-200" :title="col.label">
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button type="button" @click="addServer()"
                                                        class="px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700">
                                                    Server anlegen
                                                </button>
                                                <span x-show="manageMsg" x-text="manageMsg" class="text-xs text-green-600"></span>
                                                <span x-show="manageErr" x-text="manageErr" class="text-xs text-red-600"></span>
                                            </div>

                                            {{-- Bestehende Server --}}
                                            <div x-show="servers.length > 0" class="mt-2 divide-y divide-gray-100">
                                                <template x-for="srv in servers" :key="srv.id">
                                                    <div class="flex items-center justify-between py-1.5">
                                                        <div class="flex items-center gap-2">
                                                            <svg class="w-4 h-4" :class="colorClass(srv.color)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="symbolPath(srv.symbol)"/>
                                                            </svg>
                                                            <span class="text-sm font-medium text-gray-800" x-text="srv.name"></span>
                                                            <span class="text-xs text-gray-400" x-text="srv.description"></span>
                                                        </div>
                                                        <button type="button" @click="deleteServer(srv.id)"
                                                                class="text-xs text-red-500 hover:text-red-700">Löschen</button>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        {{-- DHCP-Server Auswahl --}}
                                        <div x-show="servers.length > 0">
                                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                                DHCP-Server <span class="text-red-500">*</span>
                                            </label>
                                            <select name="dhcp_server_id" x-model="selectedServerId"
                                                    :disabled="!enabled"
                                                    class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <option value="">— Server auswählen —</option>
                                                <template x-for="srv in servers" :key="srv.id">
                                                    <option :value="srv.id" x-text="srv.name"></option>
                                                </template>
                                            </select>
                                            <x-input-error :messages="$errors->get('dhcp_server_id')" class="mt-1" />
                                        </div>

                                        {{-- DHCP Bereich --}}
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">DHCP Bereich</label>
                                            <div class="flex gap-2 items-center">
                                                <input id="dhcp_from" type="text" name="dhcp_from" value="{{ old('dhcp_from', $vlan->dhcp_from) }}" placeholder="Von z.B. 192.168.1.100" oninput="validateDhcp()" x-bind:disabled="!enabled" x-bind:required="enabled"
                                                       class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                                <span class="text-gray-500 text-sm">–</span>
                                                <input id="dhcp_to" type="text" name="dhcp_to" value="{{ old('dhcp_to', $vlan->dhcp_to) }}" placeholder="Bis z.B. 192.168.1.200" oninput="validateDhcp()" x-bind:disabled="!enabled" x-bind:required="enabled"
                                                       class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                            </div>
                                            <p id="dhcp-error" class="mt-1 text-sm text-red-600 hidden"></p>
                                            <x-input-error :messages="$errors->get('dhcp_from')" class="mt-1" />
                                            <x-input-error :messages="$errors->get('dhcp_to')" class="mt-1" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div>
                                    <x-input-label for="description" :value="__('Beschreibung (optional)')" />
                                    <textarea id="description" name="description" rows="3"
                                              class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('description', $vlan->description) }}</textarea>
                                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                </div>

                                <!-- Checkboxen -->
                                <div class="flex gap-6">
                                    <label class="inline-flex items-center">
                                        <input id="internes_netz" type="checkbox" name="internes_netz" value="1" {{ old('internes_netz', $vlan->internes_netz) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-600">{{ __('Internes Netz') }}</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input id="ipscan" type="checkbox" name="ipscan" value="1" {{ old('ipscan', $vlan->ipscan) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-600">{{ __('IP Scanning aktivieren') }}</span>
                                    </label>
                                </div>

                                <!-- Scan Interval -->
                                <div>
                                    <x-input-label for="scan_interval_minutes" :value="__('Scan-Intervall (Minuten)')" />
                                    <x-text-input id="scan_interval_minutes" class="block mt-1 w-32" type="number" name="scan_interval_minutes" :value="old('scan_interval_minutes', $vlan->scan_interval_minutes)" min="1" placeholder="60" />
                                    <x-input-error :messages="$errors->get('scan_interval_minutes')" class="mt-2" />
                                </div>

                            </div>
                        </div>

                    </div>

                    <!-- Right Column (1/3): Netzrechner + Aktionen -->
                    <div class="lg:col-span-1 space-y-6">

                        <!-- Aktionen -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="px-6 py-4 bg-gray-100 border-b border-gray-200">
                                <h3 class="font-semibold text-gray-700">{{ __('Aktionen') }}</h3>
                            </div>
                            <div class="p-6 space-y-3">
                                <button type="submit"
                                        class="block w-full text-center px-4 py-2 bg-blue-600 border border-blue-600 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                    {{ __('Änderungen speichern') }}
                                </button>
                                <a href="{{ route('network.vlans.show', $vlan) }}"
                                   class="block w-full text-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                    {{ __('Abbrechen') }}
                                </a>

                                <div x-data="{ showDeleteConfirm: false }" class="pt-2 border-t border-gray-200">
                                    <button @click="showDeleteConfirm = true" type="button"
                                            class="block w-full text-center px-4 py-2 bg-white border border-red-300 rounded-md font-semibold text-xs text-red-600 uppercase tracking-widest hover:bg-red-50">
                                        {{ __('VLAN löschen') }}
                                    </button>

                                    <div x-show="showDeleteConfirm" x-cloak @click.away="showDeleteConfirm = false"
                                         class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
                                        <div class="flex items-center justify-center min-h-screen px-4">
                                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
                                            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                                                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('VLAN löschen') }}</h3>
                                                <p class="text-sm text-gray-500 mb-4">
                                                    Soll das VLAN <strong>{{ $vlan->vlan_name }}</strong> wirklich gelöscht werden? Alle zugehörigen IP-Adressen und Kommentare werden ebenfalls gelöscht. Diese Aktion kann nicht rückgängig gemacht werden.
                                                </p>
                                                <div class="flex justify-end space-x-3">
                                                    <button @click="showDeleteConfirm = false" type="button"
                                                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium rounded">
                                                        {{ __('Abbrechen') }}
                                                    </button>
                                                    <button type="button" onclick="document.getElementById('delete-vlan-form').submit()"
                                                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded">
                                                        {{ __('Löschen') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Netzrechner (mit fixen Werten aus dem VLAN) -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="px-6 py-4 bg-blue-50 border-b border-blue-100">
                                <h3 class="font-semibold text-gray-700">{{ __('Netzrechner') }}</h3>
                            </div>
                            <div class="p-6 space-y-2" id="network-calc">
                                <div id="calc-results" class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="font-medium text-gray-600">Netzadresse:</span>
                                        <span id="calc-network" class="text-gray-900 font-mono"></span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="font-medium text-gray-600">Broadcast:</span>
                                        <span id="calc-broadcast" class="text-gray-900 font-mono"></span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="font-medium text-gray-600">Erste IP:</span>
                                        <span id="calc-first" class="text-gray-900 font-mono"></span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="font-medium text-gray-600">Letzte IP:</span>
                                        <span id="calc-last" class="text-gray-900 font-mono"></span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="font-medium text-gray-600">Subnetzmaske:</span>
                                        <span id="calc-mask" class="text-gray-900 font-mono"></span>
                                    </div>
                                    <div class="flex justify-between text-sm border-t border-gray-200 pt-2 mt-2">
                                        <span class="font-medium text-gray-600">Verfügbare IPs:</span>
                                        <span id="calc-available" class="text-gray-900 font-semibold"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </form>

            {{-- Delete-Formular außerhalb des Update-Formulars (nested forms sind invalid HTML) --}}
            <form id="delete-vlan-form" action="{{ route('network.vlans.destroy', $vlan) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>

<script>
const NETWORK_IP = '{{ $vlan->network_address }}';
const CIDR = {{ $vlan->cidr_suffix }};

function ip2long(ip) {
    const parts = ip.split('.');
    if (parts.length !== 4) return null;
    for (const p of parts) {
        if (isNaN(p) || p < 0 || p > 255) return null;
    }
    return ((parseInt(parts[0]) << 24) | (parseInt(parts[1]) << 16) | (parseInt(parts[2]) << 8) | parseInt(parts[3])) >>> 0;
}

function long2ip(long) {
    return [(long >>> 24) & 255, (long >>> 16) & 255, (long >>> 8) & 255, long & 255].join('.');
}

function getSubnetBounds() {
    const ipLong = ip2long(NETWORK_IP);
    const mask = CIDR === 0 ? 0 : (0xFFFFFFFF << (32 - CIDR)) >>> 0;
    const network = (ipLong & mask) >>> 0;
    const broadcast = (network | (~mask >>> 0)) >>> 0;
    return { network, broadcast, mask };
}

function calculateNetwork() {
    const { network, broadcast, mask } = getSubnetBounds();
    const first = CIDR < 31 ? network + 1 : network;
    const last  = CIDR < 31 ? broadcast - 1 : broadcast;
    const available = CIDR >= 31 ? (CIDR === 32 ? 1 : 2) : broadcast - network - 1;

    document.getElementById('calc-network').textContent   = long2ip(network);
    document.getElementById('calc-broadcast').textContent = long2ip(broadcast);
    document.getElementById('calc-first').textContent     = long2ip(first);
    document.getElementById('calc-last').textContent      = long2ip(last);
    document.getElementById('calc-mask').textContent      = long2ip(mask);
    document.getElementById('calc-available').textContent = available.toLocaleString('de-DE');
}

function validateGateway() {
    const gw = document.getElementById('gateway').value.trim();
    const err = document.getElementById('gateway-error');
    if (!gw) { err.classList.add('hidden'); return; }
    const gwLong = ip2long(gw);
    if (gwLong === null) {
        err.textContent = 'Keine gültige IP-Adresse.';
        err.classList.remove('hidden');
        return;
    }
    const { network, broadcast } = getSubnetBounds();
    if (gwLong < network || gwLong > broadcast) {
        err.textContent = 'Gateway liegt nicht im Netzbereich.';
        err.classList.remove('hidden');
    } else {
        err.classList.add('hidden');
    }
}

function validateDhcp() {
    const from = document.getElementById('dhcp_from').value.trim();
    const to   = document.getElementById('dhcp_to').value.trim();
    const err  = document.getElementById('dhcp-error');
    if (!from && !to) { err.classList.add('hidden'); return; }

    const fromLong = ip2long(from);
    const toLong   = ip2long(to);
    const { network, broadcast } = getSubnetBounds();

    if (from && fromLong === null) { err.textContent = 'DHCP Von: Keine gültige IP.'; err.classList.remove('hidden'); return; }
    if (to   && toLong   === null) { err.textContent = 'DHCP Bis: Keine gültige IP.'; err.classList.remove('hidden'); return; }
    if (fromLong && (fromLong < network || fromLong > broadcast)) { err.textContent = 'DHCP Von liegt nicht im Netzbereich.'; err.classList.remove('hidden'); return; }
    if (toLong   && (toLong   < network || toLong   > broadcast)) { err.textContent = 'DHCP Bis liegt nicht im Netzbereich.'; err.classList.remove('hidden'); return; }
    if (fromLong && toLong && fromLong > toLong) { err.textContent = 'DHCP Von muss kleiner oder gleich DHCP Bis sein.'; err.classList.remove('hidden'); return; }

    err.classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', calculateNetwork);

// Alpine.js DHCP Section
function dhcpSection() {
    const SYMBOL_PATHS = {
        server:   'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01',
        firewall: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        router:   'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0',
        switch:   'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        cloud:    'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z',
    };
    const COLOR_CLASSES = {
        blue:   { bg: 'bg-blue-500',   icon: 'text-blue-600' },
        red:    { bg: 'bg-red-500',    icon: 'text-red-600' },
        green:  { bg: 'bg-green-500',  icon: 'text-green-600' },
        yellow: { bg: 'bg-yellow-400', icon: 'text-yellow-600' },
        purple: { bg: 'bg-purple-500', icon: 'text-purple-600' },
    };

    return {
        enabled: {{ old('dhcp_enabled', $vlan->dhcp_enabled) ? 'true' : 'false' }},
        showManage: false,
        servers: @json($dhcpServers ?? []),
        selectedServerId: '{{ old('dhcp_server_id', $vlan->dhcp_server_id ?? '') }}',
        newServer: { name: '', symbol: 'server', color: 'blue', description: '' },
        manageMsg: '', manageErr: '',
        symbols: [
            { key: 'server',   label: 'Server',   path: SYMBOL_PATHS.server },
            { key: 'firewall', label: 'Firewall', path: SYMBOL_PATHS.firewall },
            { key: 'router',   label: 'Router',   path: SYMBOL_PATHS.router },
            { key: 'switch',   label: 'Switch',   path: SYMBOL_PATHS.switch },
            { key: 'cloud',    label: 'Cloud',    path: SYMBOL_PATHS.cloud },
        ],
        colors: [
            { key: 'blue',   label: 'Blau',   bg: 'bg-blue-500' },
            { key: 'red',    label: 'Rot',    bg: 'bg-red-500' },
            { key: 'green',  label: 'Grün',   bg: 'bg-green-500' },
            { key: 'yellow', label: 'Gelb',   bg: 'bg-yellow-400' },
            { key: 'purple', label: 'Lila',   bg: 'bg-purple-500' },
        ],
        symbolPath(sym) { return SYMBOL_PATHS[sym] || SYMBOL_PATHS.server; },
        colorClass(col) { return (COLOR_CLASSES[col] || COLOR_CLASSES.blue).icon; },
        addServer() {
            this.manageMsg = ''; this.manageErr = '';
            if (!this.newServer.name.trim()) { this.manageErr = 'Name ist Pflicht.'; return; }
            fetch('{{ route("network.dhcp-servers.store") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify(this.newServer),
            }).then(r => r.json()).then(data => {
                if (data.errors) { this.manageErr = Object.values(data.errors).flat().join(' '); return; }
                this.servers.push(data);
                this.newServer = { name: '', symbol: 'server', color: 'blue', description: '' };
                this.manageMsg = 'Server angelegt.';
                setTimeout(() => this.manageMsg = '', 2000);
            }).catch(() => { this.manageErr = 'Fehler beim Speichern.'; });
        },
        deleteServer(id) {
            fetch('{{ url("network/dhcp-servers") }}/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            }).then(r => r.json()).then(() => {
                this.servers = this.servers.filter(s => s.id !== id);
            });
        },
    };
}
</script>

</x-app-layout>
