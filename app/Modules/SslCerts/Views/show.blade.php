<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">{{ $cert->name }}</h2>
            <div class="flex items-center gap-2">
                @can('sslcerts.edit')
                <a href="{{ route('sslcerts.edit', $cert) }}"
                   class="inline-flex items-center px-3 py-2 bg-indigo-50 text-indigo-700 text-xs font-medium rounded-md hover:bg-indigo-100 border border-indigo-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Bearbeiten
                </a>
                @endcan
                <a href="{{ route('sslcerts.index') }}"
                   class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Zurück
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif


        @php
            $color     = $cert->getExpiryColor();
            $days      = $cert->getDaysRemaining();
            $colorMap  = [
                'red'    => ['bg' => 'bg-red-50',    'border' => 'border-red-300',    'text' => 'text-red-700',    'bar' => 'bg-red-400'],
                'yellow' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-300', 'text' => 'text-yellow-700', 'bar' => 'bg-yellow-400'],
                'green'  => ['bg' => 'bg-green-50',  'border' => 'border-green-300',  'text' => 'text-green-700',  'bar' => 'bg-green-400'],
            ];
            $c = $colorMap[$color];
        @endphp

        {{-- Laufzeit-Banner --}}
        <div class="rounded-lg border p-5 {{ $c['bg'] }} {{ $c['border'] }}">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide {{ $c['text'] }} mb-1">Gültigkeit</div>
                    <div class="text-lg font-bold {{ $c['text'] }}">
                        @if($days < 0)
                            Abgelaufen seit {{ abs($days) }} Tagen
                        @elseif($days === 0)
                            Läuft heute ab
                        @else
                            Noch {{ $days }} Tage gültig
                        @endif
                    </div>
                    <div class="text-sm {{ $c['text'] }} mt-0.5">
                        {{ $cert->valid_from?->format('d.m.Y') ?? '?' }} – {{ $cert->valid_to?->format('d.m.Y') ?? '?' }}
                    </div>
                </div>
                @if($cert->valid_from && $cert->valid_to)
                @php
                    $total    = $cert->valid_from->diffInDays($cert->valid_to);
                    $elapsed  = $cert->valid_from->diffInDays(now());
                    $pct      = $total > 0 ? min(100, max(0, round($elapsed / $total * 100))) : 100;
                @endphp
                <div class="flex-1 min-w-[200px]">
                    <div class="text-xs {{ $c['text'] }} mb-1">{{ $pct }}% der Laufzeit verbraucht</div>
                    <div class="w-full h-3 bg-white bg-opacity-60 rounded-full overflow-hidden border {{ $c['border'] }}">
                        <div class="h-full {{ $c['bar'] }} rounded-full transition-all" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Zusatzinfos --}}
        @if($cert->description || $cert->responsibleUser || $cert->doc_url || $cert->servers->isNotEmpty())
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Informationen</h3>
            </div>
            <div class="p-5 space-y-4">

                @if($cert->description)
                <div>
                    <div class="text-xs text-gray-500 mb-1">Beschreibung</div>
                    <div class="text-sm text-gray-800 whitespace-pre-wrap">{{ $cert->description }}</div>
                </div>
                @endif

                @if($cert->responsibleUser)
                <div>
                    <div class="text-xs text-gray-500 mb-1">Verantwortlicher</div>
                    <div class="text-sm text-gray-800">{{ $cert->responsibleUser->name }}</div>
                </div>
                @endif

                @if($cert->servers->isNotEmpty())
                <div>
                    <div class="text-xs text-gray-500 mb-1">Verwendete Server</div>
                    <div class="flex flex-wrap gap-2 mt-1">
                        @foreach($cert->servers as $server)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-gray-100 text-gray-700 text-xs border border-gray-200">
                                <svg class="w-3 h-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                                </svg>
                                {{ $server->name }}
                                @if($server->dns_hostname)
                                    <span class="text-gray-400 font-mono ml-1">{{ $server->dns_hostname }}</span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($cert->doc_url)
                <div>
                    <div class="text-xs text-gray-500 mb-1">Dokumentation</div>
                    <a href="{{ $cert->doc_url }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center text-sm text-indigo-600 hover:underline break-all">
                        <svg class="w-3.5 h-3.5 mr-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        {{ $cert->doc_url }}
                    </a>
                </div>
                @endif

            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            {{-- Subject --}}
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
                    <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Zertifikatsinhaber (Subject)</h3>
                </div>
                <div class="p-5 space-y-3">
                    <div>
                        <div class="text-xs text-gray-500">Common Name (CN)</div>
                        <div class="text-sm font-mono text-gray-800 mt-0.5">{{ $cert->subject_cn ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Organisation (O)</div>
                        <div class="text-sm text-gray-800 mt-0.5">{{ $cert->subject_o ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Organisationseinheit (OU)</div>
                        <div class="text-sm text-gray-800 mt-0.5">{{ $cert->subject_ou ?? '—' }}</div>
                    </div>
                </div>
            </div>

            {{-- Issuer --}}
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
                    <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Aussteller (Issuer)</h3>
                </div>
                <div class="p-5 space-y-3">
                    <div>
                        <div class="text-xs text-gray-500">Common Name (CN)</div>
                        <div class="text-sm font-mono text-gray-800 mt-0.5">{{ $cert->issuer_cn ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Organisation (O)</div>
                        <div class="text-sm text-gray-800 mt-0.5">{{ $cert->issuer_o ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Seriennummer</div>
                        <div class="text-xs font-mono text-gray-600 mt-0.5 break-all">{{ $cert->serial_number ?? '—' }}</div>
                    </div>
                </div>
            </div>

        </div>

        {{-- SANs --}}
        @if($cert->san && count($cert->san) > 0)
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">
                    Subject Alternative Names (SANs) — {{ count($cert->san) }}
                </h3>
            </div>
            <div class="p-5">
                <div class="flex flex-wrap gap-2">
                    @foreach($cert->san as $san)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 text-xs font-mono border border-indigo-100">
                            {{ $san }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Fingerprints --}}
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Fingerprints</h3>
            </div>
            <div class="p-5 space-y-3">
                <div>
                    <div class="text-xs text-gray-500 mb-1">SHA-1</div>
                    <div class="text-xs font-mono text-gray-700 bg-gray-50 rounded px-3 py-2 break-all">
                        {{ $cert->fingerprint_sha1 ?? '—' }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 mb-1">SHA-256</div>
                    <div class="text-xs font-mono text-gray-700 bg-gray-50 rounded px-3 py-2 break-all">
                        {{ $cert->fingerprint_sha256 ?? '—' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Downloads & Löschen --}}
        <div class="bg-white shadow rounded-lg p-5 flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('sslcerts.download', [$cert, 'cert']) }}"
                   class="inline-flex items-center px-3 py-2 bg-indigo-50 text-indigo-700 text-sm font-medium rounded-md hover:bg-indigo-100 border border-indigo-200">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    PEM-Zertifikat
                </a>
                @if($cert->private_key)
                <a href="{{ route('sslcerts.download', [$cert, 'key']) }}"
                   class="inline-flex items-center px-3 py-2 bg-amber-50 text-amber-700 text-sm font-medium rounded-md hover:bg-amber-100 border border-amber-200">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    Private Key
                </a>
                @endif
            </div>
            @can('sslcerts.delete')
            <form method="POST" action="{{ route('sslcerts.destroy', $cert) }}"
                  onsubmit="return confirm('Zertifikat „{{ addslashes($cert->name) }}" wirklich löschen?')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center px-3 py-2 bg-red-50 text-red-700 text-sm font-medium rounded-md hover:bg-red-100 border border-red-200">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Zertifikat löschen
                </button>
            </form>
            @endcan
        </div>

    </div>
</x-app-layout>
