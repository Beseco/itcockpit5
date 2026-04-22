<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h2 class="font-semibold text-xl text-gray-800">Backup</h2>
                <a href="{{ route('backup.help') }}" title="Hilfe & Anleitung"
                   class="inline-flex items-center justify-center w-7 h-7 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-full transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                </a>
            </div>
            <div class="flex items-center gap-3">
                @can('backup.config')
                <a href="{{ route('backup.settings') }}"
                   class="text-sm text-gray-500 hover:text-gray-700">Einstellungen</a>
                @endcan
                <form method="POST" action="{{ route('backup.store') }}"
                      onsubmit="return confirm('Jetzt ein Backup erstellen?')">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Backup jetzt erstellen
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-md text-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Info-Box --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <strong>Automatisches Backup:</strong>
                        täglich um {{ $settings->schedule_time }} Uhr &nbsp;·&nbsp;
                        Aufbewahrung: {{ $settings->retention_count }} Backup{{ $settings->retention_count !== 1 ? 's' : '' }}
                        &nbsp;·&nbsp;
                        Inhalt:
                        {{ collect(['Datenbank' => $settings->backup_db, 'Dateien' => $settings->backup_files])->filter()->keys()->implode(', ') ?: '–' }}
                    </div>
                </div>
            </div>

            {{-- Backup-Liste --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Datum</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Datenbank</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Dateien</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($backups as $backup)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-gray-700">
                                {{ $backup['created_at'] ? $backup['created_at']->format('d.m.Y H:i:s') : $backup['name'] }}
                            </td>
                            <td class="px-4 py-3">
                                @if($backup['has_db'])
                                    <a href="{{ route('backup.download', [$backup['name'], 'db']) }}"
                                       class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        {{ number_format($backup['db_size'] / 1024 / 1024, 1) }} MB
                                    </a>
                                @else
                                    <span class="text-gray-300">–</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($backup['has_files'])
                                    <a href="{{ route('backup.download', [$backup['name'], 'files']) }}"
                                       class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        {{ number_format($backup['files_size'] / 1024 / 1024, 1) }} MB
                                    </a>
                                @else
                                    <span class="text-gray-300">–</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST"
                                      action="{{ route('backup.destroy', $backup['name']) }}"
                                      onsubmit="return confirm('Backup \'{{ $backup['name'] }}\' wirklich löschen?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-red-500 hover:text-red-700 text-xs">
                                        Löschen
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                                Noch keine Backups vorhanden. Erstelle jetzt das erste Backup.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
