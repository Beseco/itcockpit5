<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Backup – Einstellungen</h2>
            <a href="{{ route('backup.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Zur Übersicht</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-5">Backup-Konfiguration</h3>

                <form method="POST" action="{{ route('backup.settings.update') }}" class="space-y-5">
                    @csrf

                    {{-- Zeitplan --}}
                    <div>
                        <x-input-label for="schedule_time" value="Tägliche Backup-Zeit *" />
                        <x-text-input id="schedule_time" name="schedule_time" type="time"
                                      class="mt-1 block w-40"
                                      value="{{ old('schedule_time', $settings->schedule_time) }}" required />
                        <p class="mt-1 text-xs text-gray-400">Backup wird täglich zu dieser Uhrzeit automatisch erstellt.</p>
                        <x-input-error :messages="$errors->get('schedule_time')" class="mt-1" />
                    </div>

                    {{-- Aufbewahrung --}}
                    <div>
                        <x-input-label for="retention_count" value="Aufbewahrung (Anzahl Backups) *" />
                        <div class="mt-1 flex items-center gap-3">
                            <x-text-input id="retention_count" name="retention_count" type="number"
                                          min="1" max="365" class="block w-32"
                                          value="{{ old('retention_count', $settings->retention_count) }}" required />
                            <span class="text-sm text-gray-500">älteste werden automatisch gelöscht</span>
                        </div>
                        <x-input-error :messages="$errors->get('retention_count')" class="mt-1" />
                    </div>

                    {{-- Inhalt --}}
                    <div class="space-y-2">
                        <x-input-label value="Backup-Inhalt" />
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="backup_db" name="backup_db" value="1"
                                   @checked(old('backup_db', $settings->backup_db))
                                   class="rounded border-gray-300 text-indigo-600">
                            <label for="backup_db" class="text-sm text-gray-700">Datenbank (SQL-Dump, gzip-komprimiert)</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="backup_files" name="backup_files" value="1"
                                   @checked(old('backup_files', $settings->backup_files))
                                   class="rounded border-gray-300 text-indigo-600">
                            <label for="backup_files" class="text-sm text-gray-700">Dateien (storage/app/public, tar.gz)</label>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-gray-100 flex justify-end">
                        <x-primary-button type="submit">Einstellungen speichern</x-primary-button>
                    </div>
                </form>
            </div>

            {{-- Speicherort --}}
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-600">
                <strong>Speicherort:</strong>
                <code class="ml-1 text-xs bg-gray-100 px-1.5 py-0.5 rounded">storage/app/backups/</code>
                auf dem Server. Backups liegen lokal – für externe Sicherung bitte manuell herunterladen.
            </div>

        </div>
    </div>
</x-app-layout>
