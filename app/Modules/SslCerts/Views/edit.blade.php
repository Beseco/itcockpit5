<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Zertifikat bearbeiten</h2>
            <a href="{{ route('sslcerts.show', $cert) }}"
               class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Zurück
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        {{-- Zertifikats-Info (readonly) --}}
        @php
            $color    = $cert->getExpiryColor();
            $colorMap = [
                'red'    => 'bg-red-50 border-red-300 text-red-700',
                'yellow' => 'bg-yellow-50 border-yellow-300 text-yellow-700',
                'green'  => 'bg-green-50 border-green-300 text-green-700',
            ];
            $days = $cert->getDaysRemaining();
        @endphp
        <div class="rounded-lg border p-4 {{ $colorMap[$color] }} text-sm">
            <div class="font-semibold">{{ $cert->subject_cn ?? $cert->name }}</div>
            <div class="text-xs mt-0.5 opacity-80">
                {{ $cert->valid_from?->format('d.m.Y') ?? '?' }} – {{ $cert->valid_to?->format('d.m.Y') ?? '?' }}
                &middot;
                @if($days < 0) Abgelaufen seit {{ abs($days) }} Tagen
                @elseif($days === 0) Läuft heute ab
                @else Noch {{ $days }} Tage gültig
                @endif
            </div>
        </div>

        {{-- Formular --}}
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">Informationen bearbeiten</h3>
            </div>

            <form action="{{ route('sslcerts.update', $cert) }}" method="POST" class="p-6 space-y-5">
                @csrf @method('PUT')

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Bezeichnung <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name"
                           value="{{ old('name', $cert->name) }}"
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                           required>
                    @error('name')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Beschreibung --}}
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Beschreibung</label>
                    <textarea name="description" id="description" rows="3"
                              placeholder="Wofür wird dieses Zertifikat verwendet?"
                              class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('description', $cert->description) }}</textarea>
                </div>

                {{-- Verantwortlicher --}}
                <div>
                    <label for="responsible_user_id" class="block text-sm font-medium text-gray-700 mb-1">Verantwortlicher</label>
                    <select name="responsible_user_id" id="responsible_user_id"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">— kein Verantwortlicher —</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}"
                                {{ old('responsible_user_id', $cert->responsible_user_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Server --}}
                @if($servers->isNotEmpty())
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Server (Mehrfachauswahl)</label>
                    <div class="border border-gray-300 rounded-md max-h-48 overflow-y-auto divide-y divide-gray-100">
                        @foreach($servers as $server)
                        <label class="flex items-center px-3 py-1.5 hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="servers[]" value="{{ $server->id }}"
                                   {{ in_array($server->id, old('servers', $serverIds)) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600 mr-2.5">
                            <span class="text-sm text-gray-700">{{ $server->name }}</span>
                            @if($server->dns_hostname)
                                <span class="text-xs text-gray-400 ml-1.5 font-mono">{{ $server->dns_hostname }}</span>
                            @endif
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Dokumentations-Link --}}
                <div>
                    <label for="doc_url" class="block text-sm font-medium text-gray-700 mb-1">Dokumentations-Link</label>
                    <input type="url" name="doc_url" id="doc_url"
                           value="{{ old('doc_url', $cert->doc_url) }}"
                           placeholder="https://wiki.example.com/ssl/..."
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('doc_url')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                        Speichern
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
