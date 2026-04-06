<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create New VLAN') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('network.vlans.store') }}" id="vlan-form">
                @csrf
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Left Column (2/3): Form Fields -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Basis-Daten -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="px-6 py-4 bg-gray-100 border-b border-gray-200">
                                <h3 class="font-semibold text-gray-700">{{ __('VLAN Daten') }}</h3>
                            </div>
                            <div class="p-6 space-y-4">

                                <!-- Freie VLAN-ID Suche -->
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide mb-2">Freie VLAN-IDs suchen</p>
                                    <div class="flex items-center gap-2">
                                        <input type="number" id="free-from" min="1" max="4094" placeholder="von" value="300"
                                               class="w-24 text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <span class="text-gray-500 text-sm">–</span>
                                        <input type="number" id="free-to" min="1" max="4094" placeholder="bis" value="400"
                                               class="w-24 text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <button type="button" onclick="searchFreeVlans()"
                                                class="px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700">
                                            Suchen
                                        </button>
                                    </div>
                                    <div id="free-vlan-result" class="mt-3 hidden">
                                        <p id="free-vlan-count" class="text-xs text-gray-600 mb-2"></p>
                                        <div id="free-vlan-chips" class="flex flex-wrap gap-1.5"></div>
                                    </div>
                                    <p id="free-vlan-error" class="mt-2 text-xs text-red-600 hidden"></p>
                                </div>

                                <!-- VLAN ID -->
                                <div>
                                    <x-input-label for="vlan_id" :value="__('VLAN ID')" />
                                    <x-text-input id="vlan_id" class="block mt-1 w-full" type="number" name="vlan_id" :value="old('vlan_id')" required autofocus min="1" max="4094" placeholder="1 - 4094" />
                                    <div id="vlan-id-status" class="mt-1 text-sm hidden"></div>
                                    <x-input-error :messages="$errors->get('vlan_id')" class="mt-2" />
                                </div>

                                <!-- VLAN Name -->
                                <div>
                                    <x-input-label for="vlan_name" :value="__('VLAN Name')" />
                                    <x-text-input id="vlan_name" class="block mt-1 w-full" type="text" name="vlan_name" :value="old('vlan_name')" required placeholder="z.B. Management Network" />
                                    <x-input-error :messages="$errors->get('vlan_name')" class="mt-2" />
                                </div>

                                <!-- Network Address + CIDR in einer Zeile -->
                                <div>
                                    <x-input-label :value="__('Netzwerkadresse / CIDR')" />
                                    <div class="flex gap-2 mt-1">
                                        <x-text-input id="network_address" class="flex-1" type="text" name="network_address" :value="old('network_address')" required placeholder="z.B. 192.168.1.0" oninput="calculateNetwork()" onblur="checkNetworkOverlap()" />
                                        <select id="cidr_suffix" name="cidr_suffix" required onchange="calculateNetwork(); checkNetworkOverlap();"
                                                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                            @for($i = 1; $i <= 32; $i++)
                                                <option value="{{ $i }}" {{ old('cidr_suffix', 24) == $i ? 'selected' : '' }}>/{{ $i }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <x-input-error :messages="$errors->get('network_address')" class="mt-2" />
                                    <x-input-error :messages="$errors->get('cidr_suffix')" class="mt-2" />

                                    {{-- Netzwerk-Überlappungs-Warnung --}}
                                    <div id="network-overlap-warning" class="hidden mt-3 p-3 bg-amber-50 border border-amber-300 rounded-lg">
                                        <div class="flex items-start gap-2">
                                            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            <div>
                                                <p class="text-sm font-semibold text-amber-800" id="network-overlap-title"></p>
                                                <ul id="network-overlap-list" class="mt-1 text-xs text-amber-700 space-y-0.5 list-disc list-inside"></ul>
                                                <label class="inline-flex items-center mt-2 gap-2 cursor-pointer">
                                                    <input type="checkbox" id="overlap_confirmed" name="overlap_confirmed" value="1"
                                                           {{ old('overlap_confirmed') ? 'checked' : '' }}
                                                           onchange="updateSubmitState()"
                                                           class="rounded border-amber-400 text-amber-600 shadow-sm focus:ring-amber-500">
                                                    <span class="text-xs font-medium text-amber-800">Ich bin mir bewusst, dass dieser Netzbereich bereits verwendet wird, und möchte trotzdem speichern.</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Exakt-Duplikat-Fehler --}}
                                    <div id="network-exact-warning" class="hidden mt-3 p-3 bg-red-50 border border-red-300 rounded-lg">
                                        <div class="flex items-start gap-2">
                                            <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <div>
                                                <p class="text-sm font-semibold text-red-700">Dieses Netz existiert bereits:</p>
                                                <ul id="network-exact-list" class="mt-1 text-xs text-red-600 space-y-0.5 list-disc list-inside"></ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Gateway -->
                                <div>
                                    <x-input-label for="gateway" :value="__('Gateway (optional)')" />
                                    <x-text-input id="gateway" class="block mt-1 w-full" type="text" name="gateway" :value="old('gateway')" placeholder="z.B. 192.168.1.1" oninput="validateGateway()" />
                                    <p id="gateway-error" class="mt-1 text-sm text-red-600 hidden"></p>
                                    <x-input-error :messages="$errors->get('gateway')" class="mt-2" />
                                </div>

                                <!-- DHCP Bereich -->
                                <div x-data="dhcpSection()" class="space-y-3">
                                    {{-- Checkbox: DHCP Bereich anlegen --}}
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" x-model="enabled" name="dhcp_enabled" value="1"
                                               {{ old('dhcp_enabled') ? 'checked' : '' }}
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
                                            <select name="dhcp_server_id"
                                                    class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <option value="">— Server auswählen —</option>
                                                <template x-for="srv in servers" :key="srv.id">
                                                    <option :value="srv.id"
                                                            :selected="srv.id == {{ old('dhcp_server_id', 0) }}"
                                                            x-text="srv.name"></option>
                                                </template>
                                            </select>
                                            <x-input-error :messages="$errors->get('dhcp_server_id')" class="mt-1" />
                                        </div>

                                        {{-- DHCP Bereich --}}
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">DHCP Bereich</label>
                                            <div class="flex gap-2 items-center">
                                                <x-text-input id="dhcp_from" class="flex-1" type="text" name="dhcp_from" :value="old('dhcp_from')" placeholder="Von z.B. 192.168.1.100" oninput="validateDhcp()" />
                                                <span class="text-gray-500 text-sm">–</span>
                                                <x-text-input id="dhcp_to" class="flex-1" type="text" name="dhcp_to" :value="old('dhcp_to')" placeholder="Bis z.B. 192.168.1.200" oninput="validateDhcp()" />
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
                                              class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                              placeholder="Beschreibung für dieses VLAN">{{ old('description') }}</textarea>
                                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                </div>

                                <!-- Checkboxen -->
                                <div class="flex gap-6">
                                    <label class="inline-flex items-center">
                                        <input id="internes_netz" type="checkbox" name="internes_netz" value="1" {{ old('internes_netz') ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-600">{{ __('Internes Netz') }}</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input id="ipscan" type="checkbox" name="ipscan" value="1" {{ old('ipscan') ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-600">{{ __('IP Scanning aktivieren') }}</span>
                                    </label>
                                </div>

                                <!-- Scan Interval -->
                                <div>
                                    <x-input-label for="scan_interval_minutes" :value="__('Scan-Intervall (Minuten)')" />
                                    <x-text-input id="scan_interval_minutes" class="block mt-1 w-32" type="number" name="scan_interval_minutes" :value="old('scan_interval_minutes', 60)" min="1" placeholder="60" />
                                    <x-input-error :messages="$errors->get('scan_interval_minutes')" class="mt-2" />
                                </div>

                            </div>
                        </div>

                    </div>

                    <!-- Right Column (1/3): VLAN Rechner + Aktionen -->
                    <div class="lg:col-span-1 space-y-6">

                        <!-- Aktionen -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="px-6 py-4 bg-gray-100 border-b border-gray-200">
                                <h3 class="font-semibold text-gray-700">{{ __('Aktionen') }}</h3>
                            </div>
                            <div class="p-6 space-y-3">
                                <button type="submit"
                                        class="block w-full text-center px-4 py-2 bg-blue-600 border border-blue-600 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                    {{ __('VLAN erstellen') }}
                                </button>
                                <a href="{{ route('network.index') }}"
                                   class="block w-full text-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                    {{ __('Abbrechen') }}
                                </a>
                            </div>
                        </div>

                        <!-- VLAN Rechner -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="px-6 py-4 bg-blue-50 border-b border-blue-100">
                                <h3 class="font-semibold text-gray-700">{{ __('Netzrechner') }}</h3>
                            </div>
                            <div class="p-6 space-y-2" id="network-calc">
                                <div class="text-sm text-gray-400 italic" id="calc-placeholder">
                                    Netzwerkadresse und CIDR eingeben...
                                </div>
                                <div id="calc-results" class="hidden space-y-2">
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
                                    <div id="calc-warning" class="hidden mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-700"></div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>

<script>
function ip2long(ip) {
    const parts = ip.split('.');
    if (parts.length !== 4) return null;
    for (const p of parts) {
        if (isNaN(p) || p < 0 || p > 255) return null;
    }
    return ((parseInt(parts[0]) << 24) | (parseInt(parts[1]) << 16) | (parseInt(parts[2]) << 8) | parseInt(parts[3])) >>> 0;
}

function long2ip(long) {
    return [
        (long >>> 24) & 255,
        (long >>> 16) & 255,
        (long >>> 8) & 255,
        long & 255
    ].join('.');
}

function calculateNetwork() {
    const ip = document.getElementById('network_address').value.trim();
    const cidr = parseInt(document.getElementById('cidr_suffix').value);

    const placeholder = document.getElementById('calc-placeholder');
    const results = document.getElementById('calc-results');
    const warning = document.getElementById('calc-warning');

    const ipLong = ip2long(ip);
    if (ipLong === null || isNaN(cidr) || cidr < 1 || cidr > 32) {
        placeholder.classList.remove('hidden');
        results.classList.add('hidden');
        return;
    }

    const mask = cidr === 0 ? 0 : (0xFFFFFFFF << (32 - cidr)) >>> 0;
    const network = (ipLong & mask) >>> 0;
    const broadcast = (network | (~mask >>> 0)) >>> 0;
    const first = cidr < 31 ? network + 1 : network;
    const last = cidr < 31 ? broadcast - 1 : broadcast;
    const available = cidr >= 31 ? (cidr === 32 ? 1 : 2) : broadcast - network - 1;

    // Subnetzmaske
    const maskIp = long2ip(mask);

    document.getElementById('calc-network').textContent = long2ip(network);
    document.getElementById('calc-broadcast').textContent = long2ip(broadcast);
    document.getElementById('calc-first').textContent = long2ip(first);
    document.getElementById('calc-last').textContent = long2ip(last);
    document.getElementById('calc-mask').textContent = maskIp;
    document.getElementById('calc-available').textContent = available.toLocaleString('de-DE');

    // Warnung wenn IP nicht die Netzadresse ist
    if (ipLong !== network) {
        warning.textContent = 'Hinweis: Die eingegebene IP ist kein Netzwerk-Start. Netzadresse wäre: ' + long2ip(network);
        warning.classList.remove('hidden');
    } else {
        warning.classList.add('hidden');
    }

    placeholder.classList.add('hidden');
    results.classList.remove('hidden');

    // Re-validate gateway and DHCP
    validateGateway();
    validateDhcp();
}

function getSubnetBounds() {
    const ip = document.getElementById('network_address').value.trim();
    const cidr = parseInt(document.getElementById('cidr_suffix').value);
    const ipLong = ip2long(ip);
    if (ipLong === null || isNaN(cidr)) return null;
    const mask = cidr === 0 ? 0 : (0xFFFFFFFF << (32 - cidr)) >>> 0;
    const network = (ipLong & mask) >>> 0;
    const broadcast = (network | (~mask >>> 0)) >>> 0;
    return { network, broadcast };
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

    const bounds = getSubnetBounds();
    if (bounds && (gwLong < bounds.network || gwLong > bounds.broadcast)) {
        err.textContent = 'Gateway liegt nicht im Netzbereich.';
        err.classList.remove('hidden');
    } else {
        err.classList.add('hidden');
    }
}

function validateDhcp() {
    const from = document.getElementById('dhcp_from').value.trim();
    const to = document.getElementById('dhcp_to').value.trim();
    const err = document.getElementById('dhcp-error');

    if (!from && !to) { err.classList.add('hidden'); return; }

    const fromLong = ip2long(from);
    const toLong = ip2long(to);

    if (from && fromLong === null) {
        err.textContent = 'DHCP Von: Keine gültige IP-Adresse.';
        err.classList.remove('hidden');
        return;
    }
    if (to && toLong === null) {
        err.textContent = 'DHCP Bis: Keine gültige IP-Adresse.';
        err.classList.remove('hidden');
        return;
    }

    const bounds = getSubnetBounds();
    if (bounds) {
        if (fromLong && (fromLong < bounds.network || fromLong > bounds.broadcast)) {
            err.textContent = 'DHCP Von liegt nicht im Netzbereich.';
            err.classList.remove('hidden');
            return;
        }
        if (toLong && (toLong < bounds.network || toLong > bounds.broadcast)) {
            err.textContent = 'DHCP Bis liegt nicht im Netzbereich.';
            err.classList.remove('hidden');
            return;
        }
    }

    if (fromLong && toLong && fromLong > toLong) {
        err.textContent = 'DHCP Von muss kleiner oder gleich DHCP Bis sein.';
        err.classList.remove('hidden');
        return;
    }

    err.classList.add('hidden');
}

var networkOverlapDetected = false;

function checkNetworkOverlap() {
    const network = document.getElementById('network_address').value.trim();
    const cidr    = document.getElementById('cidr_suffix').value;
    const overlapBox = document.getElementById('network-overlap-warning');
    const exactBox   = document.getElementById('network-exact-warning');

    if (!network || !cidr) return;

    fetch('{{ route("network.vlans.check-network") }}?network=' + encodeURIComponent(network) + '&cidr=' + cidr)
        .then(r => r.json())
        .then(data => {
            if (data.error) return;

            // Exakt-Treffer
            if (data.exact.length > 0) {
                const list = document.getElementById('network-exact-list');
                list.innerHTML = data.exact.map(v => `<li>VLAN ${v.vlan_id} – ${v.vlan_name} (${v.subnet})</li>`).join('');
                exactBox.classList.remove('hidden');
            } else {
                exactBox.classList.add('hidden');
            }

            // Überlappungen
            if (data.overlaps.length > 0) {
                networkOverlapDetected = true;
                const title = document.getElementById('network-overlap-title');
                const list  = document.getElementById('network-overlap-list');
                title.textContent = data.overlaps.length === 1
                    ? 'Dieses Netz überschneidet sich mit einem vorhandenen VLAN:'
                    : 'Dieses Netz überschneidet sich mit ' + data.overlaps.length + ' vorhandenen VLANs:';
                list.innerHTML = data.overlaps.map(v => `<li>VLAN ${v.vlan_id} – ${v.vlan_name} (${v.subnet})</li>`).join('');
                overlapBox.classList.remove('hidden');
            } else {
                networkOverlapDetected = false;
                overlapBox.classList.add('hidden');
            }

            updateSubmitState();
        })
        .catch(() => {});
}

function updateSubmitState() {
    const confirmed = document.getElementById('overlap_confirmed')?.checked;
    const submitBtn = document.querySelector('#vlan-form button[type="submit"]');
    if (!submitBtn) return;

    if (networkOverlapDetected && !confirmed) {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

// Init on page load if old values exist
document.addEventListener('DOMContentLoaded', function() {
    calculateNetwork();

    // Netzwerk-Prüfung bei old()-Werten nach Validation-Fehler
    if (document.getElementById('network_address').value) {
        checkNetworkOverlap();
    }

    // VLAN-ID Prüfung beim Verlassen des Feldes
    document.getElementById('vlan_id').addEventListener('blur', function() {
        const id = parseInt(this.value);
        const statusEl = document.getElementById('vlan-id-status');
        if (!id || id < 1 || id > 4094) {
            statusEl.classList.add('hidden');
            return;
        }
        fetch('{{ route("network.vlans.check-id") }}?id=' + id)
            .then(r => r.json())
            .then(data => {
                statusEl.classList.remove('hidden');
                if (data.taken) {
                    statusEl.className = 'mt-1 text-sm text-red-600';
                    statusEl.textContent = '✕ VLAN-ID ' + id + ' ist bereits vergeben.';
                } else {
                    statusEl.className = 'mt-1 text-sm text-green-600';
                    statusEl.textContent = '✓ VLAN-ID ' + id + ' ist verfügbar.';
                }
            })
            .catch(() => statusEl.classList.add('hidden'));
    });
});

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
        enabled: {{ old('dhcp_enabled') ? 'true' : 'false' }},
        showManage: false,
        servers: @json($dhcpServers ?? []),
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

// Freie VLAN-IDs suchen
function searchFreeVlans() {
    const from = parseInt(document.getElementById('free-from').value);
    const to   = parseInt(document.getElementById('free-to').value);
    const errEl = document.getElementById('free-vlan-error');
    const resEl = document.getElementById('free-vlan-result');

    errEl.classList.add('hidden');
    resEl.classList.add('hidden');

    if (!from || !to || from < 1 || to > 4094 || from > to) {
        errEl.textContent = 'Bitte einen gültigen Bereich eingeben (1–4094).';
        errEl.classList.remove('hidden');
        return;
    }

    fetch('{{ route("network.vlans.free-ids") }}?from=' + from + '&to=' + to)
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                errEl.textContent = data.error;
                errEl.classList.remove('hidden');
                return;
            }

            document.getElementById('free-vlan-count').textContent =
                data.free_count + ' von ' + data.total_in_range + ' IDs im Bereich ' + from + '–' + to + ' sind frei.';

            const chips = document.getElementById('free-vlan-chips');
            chips.innerHTML = '';

            if (data.next5.length === 0) {
                chips.innerHTML = '<span class="text-xs text-gray-500 italic">Keine freien IDs im Bereich.</span>';
            } else {
                data.next5.forEach(function(vid) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = vid;
                    btn.className = 'px-2.5 py-1 text-sm font-mono font-medium bg-white border border-blue-300 text-blue-700 rounded hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-colors';
                    btn.onclick = function() {
                        document.getElementById('vlan_id').value = vid;
                        // Direkt Status als verfügbar setzen
                        const s = document.getElementById('vlan-id-status');
                        s.className = 'mt-1 text-sm text-green-600';
                        s.textContent = '✓ VLAN-ID ' + vid + ' ist verfügbar.';
                        s.classList.remove('hidden');
                    };
                    chips.appendChild(btn);
                });
            }

            resEl.classList.remove('hidden');
        })
        .catch(() => {
            errEl.textContent = 'Fehler bei der Anfrage.';
            errEl.classList.remove('hidden');
        });
}
</script>

</x-app-layout>
