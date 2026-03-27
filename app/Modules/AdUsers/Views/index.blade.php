<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">AD-Benutzer</h2>
            <div class="flex items-center gap-3">
                @can('adusers.config')
                <a href="{{ route('adusers.settings') }}"
                   class="text-sm text-gray-500 hover:text-gray-700">Einstellungen</a>
                @endcan
                @can('adusers.sync')
                <form method="POST" action="{{ route('adusers.sync') }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-indigo-700 transition">
                        Jetzt synchronisieren
                    </button>
                </form>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 space-y-4"
             x-data="{
                 selected: [],
                 toggleAll(event) {
                     const checkboxes = document.querySelectorAll('.row-check');
                     this.selected = event.target.checked
                         ? Array.from(checkboxes).map(c => c.value)
                         : [];
                     checkboxes.forEach(c => c.checked = event.target.checked);
                 }
             }">

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

            {{-- Suche + Filter --}}
            <form method="GET" action="{{ route('adusers.index') }}"
                  class="flex flex-wrap items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Suche Name, Abteilung, E-Mail …"
                       class="flex-1 min-w-[200px] border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">

                <select name="status" class="border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Alle Status</option>
                    <option value="aktiv"        @selected(request('status')==='aktiv')>Aktiv</option>
                    <option value="deaktiviert"  @selected(request('status')==='deaktiviert')>Deaktiviert</option>
                    <option value="offboarding"  @selected(request('status')==='offboarding')>Im Offboarding</option>
                </select>

                <select name="vorhanden" class="border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">AD-Status (alle)</option>
                    <option value="ja"  @selected(request('vorhanden')==='ja')>Im AD vorhanden</option>
                    <option value="nein" @selected(request('vorhanden')==='nein')>Nicht mehr vorhanden</option>
                </select>

                <select name="inaktiv_seit" class="border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Letzte Sync (alle)</option>
                    <option value="30"  @selected(request('inaktiv_seit')==='30')>Nicht sync. > 30 Tage</option>
                    <option value="60"  @selected(request('inaktiv_seit')==='60')>Nicht sync. > 60 Tage</option>
                    <option value="{{ $settings->max_inactive_days }}"
                            @selected(request('inaktiv_seit')===(string)$settings->max_inactive_days)>
                        Nicht sync. > {{ $settings->max_inactive_days }} Tage
                    </option>
                </select>

                <x-primary-button type="submit">Suchen</x-primary-button>
                @if(request()->hasAny(['search','status','vorhanden','inaktiv_seit']))
                    <a href="{{ route('adusers.index') }}"
                       class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-600 hover:bg-gray-50">✕</a>
                @endif
            </form>

            {{-- Bulk-Delete --}}
            @can('adusers.delete')
            <div x-show="selected.length > 0" x-cloak>
                <form method="POST" action="{{ route('adusers.bulk-delete') }}"
                      onsubmit="return confirm(selected.length + ' Benutzer wirklich löschen?')">
                    @csrf
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-xs font-semibold rounded-md hover:bg-red-700 transition">
                        <span x-text="selected.length + ' Benutzer löschen'"></span>
                    </button>
                </form>
            </div>
            @endcan

            {{-- Tabelle --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                @can('adusers.delete')
                                <th class="px-3 py-3 w-8">
                                    <input type="checkbox" @change="toggleAll($event)"
                                           class="rounded border-gray-300 text-indigo-600">
                                </th>
                                @endcan
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Konto</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organisation</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Abteilung</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telefon</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Letzte Sync</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($users as $user)
                            @php $badge = $user->status_badge; @endphp
                            <tr class="hover:bg-gray-50 {{ !$user->ad_vorhanden ? 'opacity-60' : '' }}">
                                @can('adusers.delete')
                                <td class="px-3 py-3">
                                    @if(!$user->ad_vorhanden || $user->istVeraltet($settings->max_inactive_days))
                                    <input type="checkbox" value="{{ $user->id }}"
                                           class="row-check rounded border-gray-300 text-indigo-600"
                                           @change="$event.target.checked ? selected.push('{{ $user->id }}') : selected = selected.filter(i => i !== '{{ $user->id }}')">
                                    @endif
                                </td>
                                @endcan
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $user->anzeigename_or_name }}</div>
                                    <div class="text-xs text-gray-400">{{ $user->email }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $user->samaccountname }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $user->organisation }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $user->abteilung }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $user->telefon }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badge['class'] }}">
                                            {{ $badge['label'] }}
                                        </span>
                                        @if ($offboardingSams->has($user->samaccountname))
                                            @php $obStatus = $offboardingSams[$user->samaccountname]; @endphp
                                            <a href="{{ route('adusers.offboarding.index', ['search' => $user->samaccountname]) }}"
                                               class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-orange-100 text-orange-800 hover:bg-orange-200"
                                               title="Offboarding-Vorgang anzeigen">
                                                ⚠ {{ \App\Modules\AdUsers\Models\OffboardingRecord::STATUS_LABELS[$obStatus] ?? 'Offboarding' }}
                                            </a>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">
                                    {{ $user->letzter_import_at?->format('d.m.Y H:i') ?? '–' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('adusers.show', $user) }}"
                                       class="inline-flex items-center justify-center w-7 h-7 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors"
                                       title="Details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                                    Keine Benutzer gefunden.
                                    @if(!$settings->server)
                                        <a href="{{ route('adusers.settings') }}" class="text-indigo-600 underline ml-1">
                                            Bitte zuerst LDAP konfigurieren.
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($users->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>

            <p class="text-xs text-gray-400">{{ $users->total() }} Benutzer gesamt</p>
        </div>
    </div>
</x-app-layout>
