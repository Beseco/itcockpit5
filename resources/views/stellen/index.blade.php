<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Stellen</h2>
            @can('base.stellen.edit')
                <a href="{{ route('stellen.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Neue Stelle
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                 class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-md text-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">St.-Nr.</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Stellenbeschreibung</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Gruppe</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Stelleninhaber</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">HH-Bewertung</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Belegung %</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($stellen as $stelle)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700 font-mono text-xs">{{ $stelle->stellennummer }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $stelle->stellenbeschreibung?->bezeichnung ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $stelle->gruppe?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if($stelle->stelleninhaber)
                                    <span class="text-gray-900">{{ $stelle->stelleninhaber->name }}</span>
                                @else
                                    <span class="text-amber-600 text-xs font-medium">FREI</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $stelle->haushalt_bewertung ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                @if($stelle->belegung !== null)
                                    {{ number_format($stelle->belegung, 0) }} %
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @can('base.stellen.edit')
                                    <a href="{{ route('stellen.edit', $stelle) }}"
                                       class="text-xs text-indigo-600 hover:text-indigo-800 border border-indigo-200 hover:border-indigo-400 rounded px-2.5 py-1">
                                        Bearbeiten
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">Noch keine Stellen vorhanden.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($stellen->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">{{ $stellen->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
