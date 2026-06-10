<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('abteilungen.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">Organisationseinheiten</a>
            <span class="text-gray-300">/</span>
            <h2 class="font-semibold text-xl text-gray-800">{{ $abteilung->name }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6"
             x-data="{ onlyIssues: false }">

            {{-- Kopf: Aktionen --}}
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500">Detailansicht &amp; Soll/Ist-Abgleich gegen die Vorlage</p>
                <div class="flex items-center gap-2">
                    @can('abteilungen.edit')
                        <a href="{{ route('abteilungen.edit', $abteilung) }}"
                           class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">OE bearbeiten</a>
                    @endcan
                    @if($vorlage)
                        <a href="{{ route('onboarding.vorlagen.edit', $vorlage) }}"
                           class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">Vorlage bearbeiten</a>
                    @endif
                </div>
            </div>

            {{-- Abweichungs-Banner --}}
            @if($userInfos->isNotEmpty())
                @if($issueCount > 0)
                    <div class="bg-amber-50 border border-amber-300 rounded-lg p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-amber-500 text-xl">⚠</span>
                            <p class="text-sm text-amber-800">
                                <strong>{{ $issueCount }}</strong> von {{ $userInfos->count() }} Benutzer(n) weichen von der Vorlage ab
                                (fehlende Gruppen oder abweichender Vorgesetzter).
                            </p>
                        </div>
                        <label class="flex items-center gap-2 text-xs text-amber-800 shrink-0">
                            <input type="checkbox" x-model="onlyIssues" class="rounded border-amber-300 text-amber-600">
                            Nur Abweichungen
                        </label>
                    </div>
                @else
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
                        <span class="text-green-500 text-xl">✓</span>
                        <p class="text-sm text-green-800">Alle {{ $userInfos->count() }} Benutzer entsprechen der Vorlage.</p>
                    </div>
                @endif
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- OE-Infos --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Organisationseinheit</h3>
                    <dl class="divide-y divide-gray-100 text-sm">
                        <div class="flex py-2 gap-4"><dt class="w-40 font-medium text-gray-500">Name</dt><dd>{{ $abteilung->name }}</dd></div>
                        @if($abteilung->kurzzeichen)
                            <div class="flex py-2 gap-4"><dt class="w-40 font-medium text-gray-500">Kurzzeichen</dt><dd class="font-mono">{{ $abteilung->kurzzeichen }}</dd></div>
                        @endif
                        @if($abteilung->kuerzel)
                            <div class="flex py-2 gap-4"><dt class="w-40 font-medium text-gray-500">Kürzel</dt><dd class="font-mono">{{ $abteilung->kuerzel }}</dd></div>
                        @endif
                        @if($abteilung->parent)
                            <div class="flex py-2 gap-4"><dt class="w-40 font-medium text-gray-500">Übergeordnet</dt><dd>{{ $abteilung->parent->name }}</dd></div>
                        @endif
                        <div class="flex py-2 gap-4"><dt class="w-40 font-medium text-gray-500">Vorgesetzter</dt><dd>{{ $abteilung->vorgesetzter?->anzeigename ?? '–' }}</dd></div>
                        <div class="flex py-2 gap-4"><dt class="w-40 font-medium text-gray-500">Stellvertreter</dt><dd>{{ $abteilung->stellvertreter?->anzeigename ?? '–' }}</dd></div>
                        @if($abteilung->ad_member_count !== null)
                            <div class="flex py-2 gap-4"><dt class="w-40 font-medium text-gray-500">AD-Mitarbeiter</dt><dd>👤 {{ $abteilung->ad_member_count }}</dd></div>
                        @endif
                        @if($abteilung->revision_date)
                            <div class="flex py-2 gap-4"><dt class="w-40 font-medium text-gray-500">Revision</dt><dd>{{ $abteilung->revision_date->format('d.m.Y') }}</dd></div>
                        @endif
                        <div class="flex py-2 gap-4"><dt class="w-40 font-medium text-gray-500">AD-Pfad</dt>
                            <dd class="font-mono text-xs text-gray-600 break-all">{{ $abteilung->ad_path ?? '– kein AD-Pfad –' }}</dd></div>
                    </dl>
                </div>

                {{-- Vorlage-Infos --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700">Vorlage</h3>
                        @if($vorlage)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $vorlage->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $vorlage->is_active ? 'Aktiv' : 'Inaktiv' }}
                            </span>
                        @endif
                    </div>

                    @if(!$vorlage)
                        <p class="text-sm text-amber-600">Für diese OE existiert keine Vorlage.</p>
                    @else
                        <dl class="text-sm space-y-3">
                            <div>
                                <dt class="font-medium text-gray-500 mb-1">Standard-Vorgesetzter</dt>
                                <dd>{{ $templateSupervisorName ?? '– keiner –' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500 mb-1">Standard-Gruppen ({{ $templateGroups->count() }})</dt>
                                <dd>
                                    @if($templateGroups->isEmpty())
                                        <span class="text-gray-400">– keine –</span>
                                    @else
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($templateGroups as $g)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-purple-50 text-purple-700"
                                                      title="{{ $g['dn'] }}">{{ $g['name'] }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    @endif
                </div>
            </div>

            {{-- Benutzer in der OU --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700">Benutzer in dieser OU ({{ $userInfos->count() }})</h3>
                </div>

                @if($userInfos->isEmpty())
                    <p class="p-8 text-center text-sm text-gray-400">
                        @if(!$abteilung->ad_path)
                            Kein AD-Pfad hinterlegt – Benutzer können nicht ermittelt werden.
                        @else
                            Keine Benutzer in dieser OU gefunden.
                        @endif
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Benutzer</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fehlende Vorlagen-Gruppen</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vorgesetzter</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($userInfos as $info)
                                    @php $u = $info['user']; @endphp
                                    <tr class="hover:bg-gray-50 {{ $info['has_issues'] ? 'bg-amber-50/40' : '' }}"
                                        x-show="!onlyIssues || {{ $info['has_issues'] ? 'true' : 'false' }}">
                                        <td class="px-4 py-3">
                                            @if(\Illuminate\Support\Facades\Route::has('adusers.show'))
                                                <a href="{{ route('adusers.show', $u) }}" class="font-medium text-indigo-600 hover:text-indigo-800">{{ $u->anzeigename_or_name }}</a>
                                            @else
                                                <span class="font-medium text-gray-800">{{ $u->anzeigename_or_name }}</span>
                                            @endif
                                            <p class="text-xs text-gray-400 font-mono">{{ $u->samaccountname }}</p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $u->status_badge['class'] }}">
                                                {{ $u->status_badge['label'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($info['missing_groups']->isEmpty())
                                                <span class="text-xs text-green-600">✓ vollständig</span>
                                            @else
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach($info['missing_groups'] as $g)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-700"
                                                              title="{{ $g['dn'] }}">{{ $g['name'] }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($info['supervisor_mismatch'])
                                                <span class="text-red-700">{{ $info['manager_name'] ?? '– keiner –' }}</span>
                                                <p class="text-xs text-red-500 mt-0.5">≠ Vorlage: {{ $templateSupervisorName }}</p>
                                            @else
                                                <span class="text-gray-600">{{ $info['manager_name'] ?? '–' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
