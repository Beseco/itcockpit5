<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h2 class="font-semibold text-xl text-gray-800">Vertragsmanagement</h2>
                <a href="{{ route('vertragsmanagement.help') }}" title="Hilfe &amp; Anleitung"
                   class="inline-flex items-center justify-center w-7 h-7 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-full transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </a>
            </div>
            <div class="flex items-center gap-3">
                @can('vertragsmanagement.config')
                    <a href="{{ route('vertragsmanagement.settings') }}"
                       class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                        Einstellungen
                    </a>
                @endcan
                @can('vertragsmanagement.edit')
                    <a href="{{ route('vertragsmanagement.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        + Neuer Vertrag
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filter --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <form method="GET" action="{{ route('vertragsmanagement.index') }}" class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Suche</label>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Vertragsname, Dienstleister …"
                               class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                        <select name="status" onchange="this.form.submit()"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle Status</option>
                            @foreach(\App\Modules\Vertragsmanagement\Models\Vertrag::STATUS as $key => $label)
                                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-primary-button type="submit">Filtern</x-primary-button>
                    @if($search || $status)
                        <a href="{{ route('vertragsmanagement.index') }}" class="text-xs text-indigo-600 hover:underline pb-2">Zurücksetzen</a>
                    @endif
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @if($vertraege->isEmpty())
                    <p class="p-8 text-center text-sm text-gray-400">Keine Verträge gefunden.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vertrag</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dienstleister</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Beginn</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ende</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kündigung bis</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($vertraege as $vertrag)
                                    <tr class="hover:bg-gray-50 {{ $vertrag->isInErinnerungsphase() ? 'bg-amber-50/50' : '' }}">
                                        <td class="px-4 py-3 font-medium text-gray-900">
                                            <a href="{{ route('vertragsmanagement.show', $vertrag) }}" class="hover:text-indigo-600">{{ $vertrag->name }}</a>
                                            @if($vertrag->isInErinnerungsphase())
                                                <span class="ml-1 text-xs text-amber-600" title="In Erinnerungsphase">⏰</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ $vertrag->dienstleister?->firmenname ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $vertrag->vertragsbeginn?->format('d.m.Y') ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $vertrag->vertragsende?->format('d.m.Y') ?? 'unbefristet' }}</td>
                                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $vertrag->getKuendigungsstichtag()?->format('d.m.Y') ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $vertrag->getStatusColor() }}">
                                                {{ $vertrag->getStatusLabel() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('vertragsmanagement.show', $vertrag) }}" class="text-xs text-indigo-600 hover:text-indigo-800">Details</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $vertraege->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
