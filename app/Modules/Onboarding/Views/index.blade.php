<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">AD-Benutzer</h2>
            <div class="flex items-center gap-3">
                @can('module.onboarding.edit')
                    <a href="{{ route('onboarding.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                        + Neuer Mitarbeiter
                    </a>
                    <a href="{{ route('onboarding.vorlagen.index') }}"
                       class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                        Vorlagen
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    @include('adusers::_subnav')

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-md text-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Schnellstart --}}
            @if($vorlagen->isNotEmpty())
                @php
                    $schnellstart = $vorlagen->map(fn($v) => [
                        'id'   => $v->id,
                        'name' => $v->name,
                        'abt'  => $v->abteilung?->name ?? '',
                    ])->values();
                @endphp
                <div class="bg-white shadow-sm sm:rounded-lg p-4"
                     x-data="{
                        q: '',
                        open: false,
                        baseUrl: '{{ route('onboarding.create') }}',
                        vorlagen: {{ \Illuminate\Support\Js::from($schnellstart) }},
                        get filtered() {
                            const q = this.q.toLowerCase().trim();
                            const list = !q ? this.vorlagen
                                : this.vorlagen.filter(v => (v.name + ' ' + v.abt).toLowerCase().includes(q));
                            return list.slice(0, 50);
                        },
                        go(id) { window.location = this.baseUrl + '?vorlage_id=' + id; }
                     }">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h3 class="text-sm font-semibold text-gray-700 shrink-0">Schnellstart – Vorlage wählen</h3>
                        <div class="relative flex-1 min-w-[16rem] max-w-md">
                            <input type="text" x-model="q" @focus="open = true" @click="open = true"
                                   placeholder="Vorlage / OE suchen …"
                                   class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <div x-show="open" x-cloak @click.away="open = false"
                                 class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-72 overflow-y-auto">
                                <template x-for="v in filtered" :key="v.id">
                                    <button type="button" @click="go(v.id)"
                                            class="w-full text-left px-4 py-2 text-sm hover:bg-indigo-50 flex items-center gap-2">
                                        <span class="font-medium text-gray-800" x-text="v.name"></span>
                                        <span class="text-xs text-gray-400" x-text="v.abt"></span>
                                    </button>
                                </template>
                                <p x-show="filtered.length === 0" class="px-4 py-2 text-sm text-gray-400">Keine Treffer.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Letzte Onboarding-Vorgänge --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700">Letzte Onboarding-Vorgänge</h3>
                    <a href="{{ route('onboarding.records.index') }}"
                       class="text-xs text-indigo-600 hover:text-indigo-800">Alle anzeigen →</a>
                </div>

                @if($records->isEmpty())
                    <p class="p-6 text-sm text-gray-400">Noch keine Benutzer angelegt.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Benutzername</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vorlage</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
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
                                        <td class="px-4 py-3 text-gray-600 font-mono text-xs">
                                            {{ $record->samaccountname }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">
                                            {{ $record->vorlage?->name ?? '–' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $record->status_badge['class'] }}">
                                                {{ $record->status_badge['label'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 text-xs">
                                            {{ $record->created_at->format('d.m.Y H:i') }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 text-xs">
                                            {{ $record->createdBy?->name ?? '–' }}
                                        </td>
                                        <td class="px-4 py-3 text-right space-x-3">
                                            <a href="{{ route('onboarding.records.show', $record) }}"
                                               class="text-xs text-indigo-600 hover:text-indigo-800">Details</a>
                                            @can('module.onboarding.edit')
                                                <form method="POST" action="{{ route('onboarding.records.destroy', $record) }}"
                                                      class="inline"
                                                      onsubmit="return confirm('Onboarding-Vorgang für {{ $record->samaccountname }} wirklich löschen? (AD-Benutzer bleibt bestehen)')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs text-red-600 hover:text-red-800">Löschen</button>
                                                </form>
                                            @endcan
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
