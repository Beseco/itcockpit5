<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('orders.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bestellungen importieren</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 6000)"
                     class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Upload --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">CSV-Datei importieren</h3>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-500 mb-4">
                        Unterstütztes Format: Infoma/Fibo-Export (Semikolon-getrennt, UTF-8).<br>
                        Importierte Einträge erhalten den Status <strong>angeordnet</strong> und können als Batch wieder gelöscht werden.
                    </p>

                    <form action="{{ route('orders.import.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="flex items-end gap-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">CSV-Datei</label>
                                <input type="file" name="file" accept=".csv,.txt"
                                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                @error('file')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                Importieren
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Vorhandene Batches --}}
            @if ($batches->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Importierte Batches</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch-ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quelle</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Einträge</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gesamtbetrag</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Importiert am</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($batches as $batch)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $batch->import_batch_id }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $batch->import_source ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right text-gray-700">{{ $batch->count }}</td>
                                        <td class="px-4 py-3 text-right text-gray-700 font-medium">
                                            {{ number_format($batch->total, 2, ',', '.') }} €
                                        </td>
                                        <td class="px-4 py-3 text-gray-500">{{ \Carbon\Carbon::parse($batch->imported_at)->format('d.m.Y H:i') }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <span x-data="{ open: false }">
                                                <button @click="open = true"
                                                        class="inline-flex items-center px-2 py-1 text-xs bg-red-50 text-red-600 border border-red-200 rounded hover:bg-red-100">
                                                    Batch löschen
                                                </button>
                                                <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" x-transition style="display:none">
                                                    <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
                                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Batch löschen?</h3>
                                                        <p class="text-sm text-gray-600 mb-1">
                                                            <strong>{{ $batch->count }} Einträge</strong> werden unwiderruflich gelöscht.
                                                        </p>
                                                        <p class="text-xs text-gray-400 mb-4 font-mono">{{ $batch->import_batch_id }}</p>
                                                        <div class="flex justify-end gap-3">
                                                            <button @click="open = false"
                                                                    class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-md">Abbrechen</button>
                                                            <form action="{{ route('orders.import.destroy', $batch->import_batch_id) }}" method="POST">
                                                                @csrf @method('DELETE')
                                                                <button type="submit"
                                                                        class="px-4 py-2 text-sm bg-red-600 text-white hover:bg-red-700 rounded-md">Löschen</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
