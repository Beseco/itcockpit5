<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Erinnerungsmails</h2>
            <a href="{{ route('reminders.help') }}" title="Hilfe & Anleitung" class="inline-flex items-center justify-center w-7 h-7 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-full transition"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg></a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                     class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Aktionsleiste --}}
            <div class="flex items-center justify-between mb-4">
                <div class="flex gap-2">
                    <a href="{{ route('reminders.index') }}"
                       class="text-sm font-medium {{ !request()->routeIs('reminders.log') ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }} pb-1">
                        Übersicht
                    </a>
                    <a href="{{ route('reminders.log') }}"
                       class="text-sm font-medium {{ request()->routeIs('reminders.log') ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }} pb-1 ml-4">
                        Logfile
                    </a>
                </div>
                @can('reminders.create')
                <a href="{{ route('reminders.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Neue Erinnerung
                </a>
                @endcan
            </div>

            {{-- Scheduler-Status --}}
            <div class="mb-4 flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium
                {{ $schedulerActive ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' }}">
                <span class="inline-block w-2 h-2 rounded-full flex-shrink-0
                    {{ $schedulerActive ? 'bg-green-500' : 'bg-red-500' }}"></span>
                @if ($schedulerActive)
                    Scheduler aktiv &ndash; letztes Signal {{ $lastHeartbeat->created_at->diffForHumans() }}
                @elseif ($lastHeartbeat)
                    Scheduler nicht aktiv &ndash; letztes Signal: {{ $lastHeartbeat->created_at->format('d.m.Y H:i:s') }}
                @else
                    Scheduler nicht aktiv &ndash; noch kein Heartbeat empfangen. Windows Task Scheduler prüfen:
                    <code class="ml-1 font-mono text-xs bg-red-100 px-1 rounded">php artisan schedule:run</code>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Betreff</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nächste Mail</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Intervall</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mail an</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($reminders as $reminder)
                                <tr class="hover:bg-gray-50 {{ $reminder->status ? '' : 'opacity-50' }}">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <form action="{{ route('reminders.toggle', $reminder) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                    class="px-2 py-1 text-xs font-semibold rounded-full
                                                        {{ $reminder->status
                                                            ? 'bg-green-100 text-green-800 hover:bg-green-200'
                                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                                {{ $reminder->status ? 'Aktiv' : 'Inaktiv' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $reminder->titel }}</td>
                                    <td class="px-4 py-3 text-sm whitespace-nowrap
                                        {{ $reminder->nextsend->isPast() ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                                        {{ $reminder->nextsend->format('d.m.Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        {{ $reminder->intervall_label }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $reminder->mailto_label }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium" x-data="{ showDelete: false }">
                                        @php
                                            $canEdit   = auth()->user()->hasModulePermission('reminders', 'edit')   || (auth()->user()->hasModulePermission('reminders', 'create') && $reminder->user_id === auth()->id());
                                            $canDelete = auth()->user()->hasModulePermission('reminders', 'delete') || (auth()->user()->hasModulePermission('reminders', 'create') && $reminder->user_id === auth()->id());
                                        @endphp
                                        <div class="inline-flex items-center gap-1">
                                        @if($canEdit)
                                        <a href="{{ route('reminders.edit', $reminder) }}"
                                           class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200"
                                           title="Bearbeiten">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        @endif
                                        @if($canDelete)
                                        <button @click="showDelete = true" type="button"
                                                class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-red-50 text-red-600 hover:bg-red-100 border border-red-200"
                                                title="Löschen">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>

                                        <div x-show="showDelete" x-cloak @click.away="showDelete = false"
                                             class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
                                            <div class="flex items-center justify-center min-h-screen px-4">
                                                <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
                                                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Erinnerung löschen</h3>
                                                    <p class="text-sm text-gray-500 mb-4">
                                                        Soll „{{ $reminder->titel }}" wirklich gelöscht werden?
                                                    </p>
                                                    <div class="flex justify-end gap-3">
                                                        <button @click="showDelete = false" type="button"
                                                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                                            Abbrechen
                                                        </button>
                                                        <form action="{{ route('reminders.destroy', $reminder) }}" method="POST" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                                                Löschen
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                        Noch keine Erinnerungen angelegt.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
