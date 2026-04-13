<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('server.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Server – Einstellungen</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                     class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            {{-- LDAP Sync --}}
            @can('server.sync')
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">LDAP-Synchronisation</h3>
                        <form action="{{ route('server.sync') }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-amber-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-600">
                                LDAP Sync jetzt ausführen
                            </button>
                        </form>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-500 mb-1">
                            @if ($lastSync)
                                Letzter Sync: <strong>{{ \Carbon\Carbon::parse($lastSync)->format('d.m.Y H:i') }} Uhr</strong>
                            @else
                                Noch kein Sync durchgeführt.
                            @endif
                        </p>
                        <p class="text-xs text-gray-400 mb-4">Verwendet LDAP-Verbindung aus AD-Benutzer-Einstellungen.</p>

                        {{-- OU-Liste --}}
                        <div class="mb-4 space-y-1">
                            @forelse ($syncOus as $ou)
                                <div class="flex items-center justify-between py-1.5 px-3 bg-gray-50 rounded">
                                    <div class="flex items-center gap-2 min-w-0">
                                        {{-- Enabled-Indikator --}}
                                        <span class="flex-shrink-0 w-2 h-2 rounded-full {{ $ou->enabled ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                        <div class="min-w-0">
                                            @if ($ou->label)
                                                <span class="text-sm font-medium text-gray-800">{{ $ou->label }}</span>
                                                <span class="text-xs text-gray-400 ml-1 font-mono">{{ $ou->distinguished_name }}</span>
                                            @else
                                                <span class="text-sm font-mono text-gray-800">{{ $ou->distinguished_name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0 ml-3">
                                        {{-- Toggle --}}
                                        <form action="{{ route('server.settings.sync-ous.toggle', $ou) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                    class="text-xs {{ $ou->enabled ? 'text-gray-500 hover:text-gray-700' : 'text-green-600 hover:text-green-800' }}">
                                                {{ $ou->enabled ? 'Deaktivieren' : 'Aktivieren' }}
                                            </button>
                                        </form>
                                        {{-- Löschen --}}
                                        <form action="{{ route('server.settings.sync-ous.destroy', $ou) }}" method="POST"
                                              onsubmit="return confirm('OU wirklich löschen?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs text-red-500 hover:text-red-700">Löschen</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-amber-600">Keine OUs konfiguriert – Sync wird fehlschlagen.</p>
                            @endforelse
                        </div>

                        {{-- Neue OU hinzufügen --}}
                        @can('server.config')
                            <form action="{{ route('server.settings.sync-ous.store') }}" method="POST"
                                  class="flex gap-2 items-end mt-3">
                                @csrf
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-500 mb-1">Distinguished Name (DN)</label>
                                    <input type="text" name="distinguished_name"
                                           placeholder="OU=Server,DC=example,DC=lan" required
                                           class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm font-mono">
                                </div>
                                <div class="w-48">
                                    <label class="block text-xs text-gray-500 mb-1">Bezeichnung (optional)</label>
                                    <input type="text" name="label" placeholder="z.B. Produktiv-Server"
                                           class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                </div>
                                <button type="submit"
                                        class="px-3 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700 whitespace-nowrap">
                                    OU hinzufügen
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            @endcan

            {{-- Erweiterbare Optionen --}}
            @foreach ($options as $category => $categoryOptions)
                @php
                    $catLabel = \App\Modules\Server\Models\ServerOption::CATEGORY_LABELS[$category] ?? $category;
                @endphp
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $catLabel }}</h3>
                    </div>
                    <div class="p-6">

                        {{-- Bestehende Optionen --}}
                        <div class="mb-4 space-y-1">
                            @forelse ($categoryOptions as $opt)
                                <div class="flex items-center justify-between py-1.5 px-3 bg-gray-50 rounded">
                                    <span class="text-sm text-gray-800">{{ $opt->label }}</span>
                                    <form action="{{ route('server.settings.options.destroy', $opt) }}" method="POST"
                                          onsubmit="return confirm('Option \"{{ $opt->label }}\" wirklich löschen?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Löschen</button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400">Noch keine Optionen vorhanden.</p>
                            @endforelse
                        </div>

                        {{-- Neue Option hinzufügen --}}
                        <form action="{{ route('server.settings.options.store') }}" method="POST"
                              class="flex gap-2 items-end mt-3">
                            @csrf
                            <input type="hidden" name="category" value="{{ $category }}">
                            <div class="flex-1">
                                <input type="text" name="label" placeholder="Neue Option…" required
                                       class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            </div>
                            <button type="submit"
                                    class="px-3 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700 whitespace-nowrap">
                                Hinzufügen
                            </button>
                        </form>

                    </div>
                </div>
            @endforeach

        </div>
    </div>
</x-app-layout>
