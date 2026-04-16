<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">SSL-Zertifikate</h2>
            @can('sslcerts.view')
            <a href="{{ route('sslcerts.create') }}"
               class="inline-flex items-center px-3 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Zertifikat importieren
            </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Legende --}}
        <div class="flex items-center gap-4 text-xs text-gray-500">
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-yellow-300 border border-yellow-400 inline-block"></span>
                Läuft in &lt; 30 Tagen ab
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-red-300 border border-red-400 inline-block"></span>
                Läuft in &lt; 14 Tagen ab / abgelaufen
            </span>
        </div>

        @if($certs->isEmpty())
            <div class="bg-white shadow rounded-lg p-10 text-center text-gray-400 text-sm">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                </svg>
                Keine Zertifikate vorhanden. <a href="{{ route('sslcerts.create') }}" class="text-indigo-600 hover:underline">Jetzt importieren</a>
            </div>
        @else
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Name</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Common Name</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Aussteller</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Gültig ab</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Gültig bis</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">SANs</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Key</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($certs as $cert)
                        @php
                            $color = $cert->getExpiryColor();
                            $rowClass = match($color) {
                                'red'    => 'bg-red-50 hover:bg-red-100 border-l-4 border-red-400',
                                'yellow' => 'bg-yellow-50 hover:bg-yellow-100 border-l-4 border-yellow-400',
                                default  => 'hover:bg-gray-50',
                            };
                            $days = $cert->getDaysRemaining();
                            $daysLabel = $days < 0
                                ? '<span class="text-red-600 font-semibold">abgelaufen</span>'
                                : ($days === 0
                                    ? '<span class="text-red-600 font-semibold">heute</span>'
                                    : '<span>in ' . $days . ' Tagen</span>');
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="px-3 py-2 font-medium text-gray-800">
                                <a href="{{ route('sslcerts.show', $cert) }}" class="hover:text-indigo-600 hover:underline">
                                    {{ $cert->name }}
                                </a>
                            </td>
                            <td class="px-3 py-2 text-gray-700 font-mono text-xs">{{ $cert->subject_cn ?? '—' }}</td>
                            <td class="px-3 py-2 text-xs text-gray-600">{{ $cert->issuer_cn ?? '—' }}</td>
                            <td class="px-3 py-2 text-xs text-gray-500 whitespace-nowrap">
                                {{ $cert->valid_from?->format('d.m.Y') ?? '—' }}
                            </td>
                            <td class="px-3 py-2 text-xs whitespace-nowrap">
                                <div>{{ $cert->valid_to?->format('d.m.Y') ?? '—' }}</div>
                                <div class="text-xs mt-0.5">{!! $daysLabel !!}</div>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-600 max-w-xs">
                                @if($cert->san)
                                    @foreach(array_slice($cert->san, 0, 2) as $san)
                                        <span class="inline-block bg-gray-100 text-gray-600 rounded px-1.5 py-0.5 text-xs mb-0.5">{{ $san }}</span>
                                    @endforeach
                                    @if(count($cert->san) > 2)
                                        <span class="text-gray-400 text-xs">+{{ count($cert->san) - 2 }} weitere</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center">
                                @if($cert->private_key)
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-100 text-green-600" title="Private Key vorhanden">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                        </svg>
                                    </span>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <a href="{{ route('sslcerts.show', $cert) }}"
                                   class="text-xs text-indigo-600 hover:underline mr-3">Details</a>
                                <form method="POST" action="{{ route('sslcerts.destroy', $cert) }}" class="inline"
                                      onsubmit="return confirm('Zertifikat „{{ addslashes($cert->name) }}" wirklich löschen?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-500 hover:text-red-700 hover:underline">Löschen</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="text-xs text-gray-400">{{ $certs->count() }} Zertifikat{{ $certs->count() !== 1 ? 'e' : '' }}</div>
        @endif

    </div>
</x-app-layout>
