<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Erinnerungsmails — Logfile</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Navigation + Filter --}}
            <div class="flex items-center justify-between mb-4">
                <div class="flex gap-4">
                    <a href="{{ route('reminders.index') }}"
                       class="text-sm text-gray-500 hover:text-gray-700 pb-1">Übersicht</a>
                    <a href="{{ route('reminders.log') }}"
                       class="text-sm font-medium text-indigo-600 border-b-2 border-indigo-600 pb-1">Logfile</a>
                </div>

                <form action="{{ route('reminders.log') }}" method="GET" class="flex gap-2">
                    <select name="typ" onchange="this.form.submit()"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        <option value="">Alle Typen</option>
                        @foreach ($typen as $val => $label)
                            <option value="{{ $val }}" {{ $typ == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Typ</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zeit</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nachricht</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full
                                            {{ $log->typ === 1 ? 'bg-blue-100 text-blue-700' :
                                               ($log->typ === 2 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700') }}">
                                            {{ \App\Models\ReminderMailLog::TYPEN[$log->typ] ?? '–' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                                        {{ $log->created_at->format('d.m.Y H:i:s') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $log->nachricht }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-400">Keine Einträge.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
