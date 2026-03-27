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
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">LDAP-Synchronisation</h3>
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            @if ($lastSync)
                                Letzter Sync: <strong>{{ \Carbon\Carbon::parse($lastSync)->format('d.m.Y H:i') }} Uhr</strong>
                            @else
                                Noch kein Sync durchgeführt.
                            @endif
                            <p class="text-xs text-gray-400 mt-0.5">
                                OU: <span class="font-mono">OU=Server,OU=LRA-FS,DC=lra,DC=lan</span>
                                · Verwendet LDAP-Verbindung aus AD-Benutzer-Einstellungen
                            </p>
                        </div>
                        <form action="{{ route('server.sync') }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-amber-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-600">
                                LDAP Sync jetzt ausführen
                            </button>
                        </form>
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
