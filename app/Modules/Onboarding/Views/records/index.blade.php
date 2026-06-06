<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('onboarding.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800">Onboarding-History</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @if($records->isEmpty())
                    <p class="p-8 text-center text-sm text-gray-400">Noch keine Onboarding-Vorgänge vorhanden.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Benutzername</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">UPN</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vorlage</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mails</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Angelegt</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Von</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($records as $record)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900">
                                            {{ $record->vorname }} {{ $record->nachname }}
                                        </td>
                                        <td class="px-4 py-3 font-mono text-xs text-gray-600">
                                            {{ $record->samaccountname }}
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-600">
                                            {{ $record->upn }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">
                                            {{ $record->vorlage?->name ?? '–' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $record->status_badge['class'] }}">
                                                {{ $record->status_badge['label'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-400">
                                            @if($record->welcome_mail_sent_at || $record->supervisor_mail_sent_at)
                                                <span class="text-green-600">✓</span>
                                                {{ collect([$record->welcome_mail_sent_at ? 'User' : null, $record->supervisor_mail_sent_at ? 'Vorgesetzter' : null])->filter()->implode(', ') }}
                                            @else
                                                –
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500">
                                            {{ $record->created_at->format('d.m.Y H:i') }}
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500">
                                            {{ $record->createdBy?->name ?? '–' }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('onboarding.records.show', $record) }}"
                                               class="text-xs text-indigo-600 hover:text-indigo-800">Details</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $records->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
